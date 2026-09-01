<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;

final class Assets {

	private const OPTION_ASSET_SIGNATURE = 'f152_asset_signature';
	private const OPTION_SIGNATURE_CHECKED_AT = 'f152_asset_signature_checked_at';
	private const FRONT_SIGNATURE_CHECK_INTERVAL = 600;
	private const ADMIN_SIGNATURE_CHECK_INTERVAL = 60;
	private static $page_cache_purge_scheduled = false;

	public static function asset_version( string $relative_path ) : string {
		$relative_path = ltrim( str_replace( '\\', '/', $relative_path ), '/' );
		$file = F152_DIR . $relative_path;
		$mtime = is_file( $file ) ? filemtime( $file ) : false;
		return false !== $mtime && $mtime > 0
			? F152_VERSION . '.' . (string) $mtime
			: F152_VERSION;
	}

	public static function asset_signature() : string {
		$files = array_merge(
			glob( F152_DIR . 'assets/js/*.js' ) ?: [],
			glob( F152_DIR . 'assets/css/*.css' ) ?: [],
			glob( F152_DIR . 'includes/*.php' ) ?: []
		);
		if ( defined( 'F152_FILE' ) && is_file( F152_FILE ) ) {
			$files[] = F152_FILE;
		}
		sort( $files, SORT_STRING );

		$parts = [];
		foreach ( $files as $file ) {
			$mtime = is_file( $file ) ? filemtime( $file ) : false;
			$size  = is_file( $file ) ? filesize( $file ) : false;
			$parts[] = str_replace( F152_DIR, '', $file ) . ':' . (string) $mtime . ':' . (string) $size;
		}

		return hash( 'sha256', implode( '|', $parts ) );
	}

	public static function maybe_refresh_asset_cache( bool $force = false ) : void {
		$now = time();
		$last_checked = (int) get_option( self::OPTION_SIGNATURE_CHECKED_AT, 0 );
		$is_admin_request = function_exists( 'is_admin' ) && is_admin();
		$interval = $is_admin_request ? self::ADMIN_SIGNATURE_CHECK_INTERVAL : self::FRONT_SIGNATURE_CHECK_INTERVAL;
		if ( ! $force && $last_checked > 0 && ( $now - $last_checked ) < $interval ) {
			return;
		}
		update_option( self::OPTION_SIGNATURE_CHECKED_AT, $now, false );

		$current = self::asset_signature();
		$stored  = (string) get_option( self::OPTION_ASSET_SIGNATURE, '' );
		if ( '' !== $stored && hash_equals( $stored, $current ) ) {
			return;
		}

		update_option( self::OPTION_ASSET_SIGNATURE, $current, false );
		if ( class_exists( '\F152\ServiceScanner' ) && is_callable( [ '\F152\ServiceScanner', 'clear_cache' ] ) ) {
			ServiceScanner::clear_cache();
		}
		self::schedule_page_cache_purge();
		do_action( 'f152_asset_signature_changed', $stored, $current );
	}

	public static function handle_upgrader_process_complete( $upgrader, $hook_extra ): void {
		if ( ! is_array( $hook_extra ) || 'plugin' !== ( $hook_extra['type'] ?? '' ) ) {
			return;
		}
		self::maybe_refresh_asset_cache( true );
	}

	public static function schedule_page_cache_purge() : void {
		if ( self::$page_cache_purge_scheduled ) {
			return;
		}
		self::$page_cache_purge_scheduled = true;
		add_action( 'shutdown', [ __CLASS__, 'purge_page_cache' ], 999 );
	}

	public static function purge_page_cache() : void {
		self::$page_cache_purge_scheduled = false;
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	public static function init() : void {
		add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_front']);
		add_action('wp_print_footer_scripts', [__CLASS__, 'print_front_config'], 5);
		add_filter('script_loader_tag', [__CLASS__, 'script_loader_tag'], 10, 3);
	}

	public static function enqueue_front() : void {
		if ( ! get_option('f152_enabled', 1) ) return;

		if ( is_admin() ) return;

		add_filter('body_class', function($classes) {
			$theme_mode = Settings::sanitize_theme_mode( (string) get_option('f152_theme', 'light') );
			$classes[] = 'f152-theme-' . $theme_mode;
			return $classes;
		});

		wp_register_style(
			'f152',
			F152_URL . 'assets/css/152.css',
			[],
			self::asset_version( 'assets/css/152.css' )
		);
		wp_enqueue_style('f152');

		$css_vars = self::build_css_vars_inline();
		if ( $css_vars !== '' ) {
			wp_add_inline_style('f152', wp_strip_all_tags( $css_vars ) );
		}

		$custom_css = trim( (string) get_option( 'f152_custom_css', '' ) );
		if ( '' !== $custom_css ) {
			wp_add_inline_style( 'f152', $custom_css );
		}

		wp_register_script(
			'f152',
			F152_URL . 'assets/js/152.js',
			[],
			self::asset_version( 'assets/js/152.js' ),
			true
		);
		wp_enqueue_script('f152');

		self::enqueue_classic_checkout_consent();
		self::enqueue_block_checkout_consent();
	}


	private static function enqueue_classic_checkout_consent() : void {
		if ( ! class_exists( 'WooCommerce' ) || ! (bool) get_option( 'f152_checkout_enable', 1 ) ) {
			return;
		}

		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return;
		}

		if ( self::is_checkout_block_page() || ! class_exists( '\F152\Integrations' ) ) {
			return;
		}

		$html = Integrations::get_classic_checkout_checkbox_html();
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return;
		}

		wp_register_script(
			'f152-classic-checkout-consent',
			F152_URL . 'assets/js/checkout-classic-consent.js',
			[ 'jquery' ],
			self::asset_version( 'assets/js/checkout-classic-consent.js' ),
			true
		);
		wp_enqueue_script( 'f152-classic-checkout-consent' );

		$config = wp_json_encode(
			[
				'enabled' => true,
				'html'    => $html,
			],
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);

		if ( false !== $config ) {
			wp_add_inline_script(
				'f152-classic-checkout-consent',
				'window.F152ClassicCheckoutConsent = ' . $config . ';',
				'before'
			);
		}
	}

	private static function is_checkout_block_page() : bool {
		$utils = '\\Automattic\\WooCommerce\\Blocks\\Utils\\CartCheckoutUtils';
		if ( class_exists( $utils ) && method_exists( $utils, 'is_checkout_block_default' ) ) {
			try {
				if ( (bool) $utils::is_checkout_block_default() ) {
					return true;
				}
			} catch ( \Throwable $e ) {
				unset( $e );
			}
		}

		global $post;
		return $post instanceof \WP_Post && function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $post );
	}


	private static function enqueue_block_checkout_consent() : void {
		if ( ! class_exists( 'WooCommerce' ) || ! (bool) get_option( 'f152_checkout_enable', 1 ) ) {
			return;
		}

		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return;
		}

		if ( ! class_exists( '\F152\Integrations' ) ) {
			return;
		}

		$html = Integrations::get_block_checkout_label_html();
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return;
		}

		wp_register_script(
			'f152-block-checkout-consent',
			F152_URL . 'assets/js/checkout-block-consent.js',
			[],
			self::asset_version( 'assets/js/checkout-block-consent.js' ),
			true
		);
		wp_enqueue_script( 'f152-block-checkout-consent' );

		$config = wp_json_encode(
			[
				'enabled' => true,
				'html'    => $html,
				'hash'    => substr( md5( $html ), 0, 12 ),
			],
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);

		if ( false !== $config ) {
			wp_add_inline_script(
				'f152-block-checkout-consent',
				'window.F152BlockCheckoutConsent = ' . $config . ';',
				'before'
			);
		}
	}

	public static function print_front_config() : void {
		if ( ! wp_script_is( 'f152', 'enqueued' ) ) {
			return;
		}

		$config = wp_json_encode(
			self::build_js_config(),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		if ( false === $config ) {
			$config = '{}';
		}

		wp_print_inline_script_tag(
			'window.F152 = ' . $config . ';',
			[
				'id'               => 'f152-js-extra',
				'data-nowprocket' => true,
				'data-no-optimize' => true,
				'data-cfasync'     => 'false',
			]
		);
	}

	public static function script_loader_tag( string $tag, string $handle, string $src ) : string {
		unset( $src );
		$protected_handles = [ 'f152', 'f152-classic-checkout-consent', 'f152-block-checkout-consent' ];
		if ( ! in_array( $handle, $protected_handles, true ) || false !== strpos( $tag, 'data-nowprocket' ) ) {
			return $tag;
		}

		return preg_replace(
			'/^<script\b/i',
			'<script data-nowprocket data-no-optimize="1" data-cfasync="false"',
			$tag,
			1
		) ?: $tag;
	}

	private static function build_js_config() : array {
                $cookie_domain_raw = (string) get_option('f152_cookie_domain', '');
                $cookie_domain     = Settings::sanitize_cookie_domain( $cookie_domain_raw );

                $ym_id_raw   = (string) get_option('f152_ym_counter', '');
                $ym_id_clean = Helpers::sanitize_metrika_id( $ym_id_raw );

                $theme_mode = Settings::sanitize_theme_mode( (string) get_option('f152_theme', 'light') );

                return [
                        'enabled' => (bool) get_option('f152_enabled', 1),

                        'cookie' => [
                                'name'          => 'f152_consent',
                                'domain'        => $cookie_domain,
                                'days'          => 365,
                                'sameSite'      => 'Lax',
                                'secure'        => is_ssl(),
                                'version'       => Consent::current_version(),
                                'legacyVersion' => Consent::legacy_version(),
                        ],

                        'theme' => [
                                'mode' => $theme_mode,
                                'colors' => [
                                        'bg'       => Helpers::sanitize_hex_color_soft( get_option('f152_color_bg', '') ),
                                        'text'     => Helpers::sanitize_hex_color_soft( get_option('f152_color_text', '') ),
                                        'link'     => Helpers::sanitize_hex_color_soft( get_option('f152_color_link', '') ),
                                        'btn_bg'   => Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_bg', '') ),
                                        'btn_text' => Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_text', '') ),
                                ],
                        ],

                        'metrika' => [
                                'id' => $ym_id_clean,
                                'mode' => Settings::get_metrika_behavior(),
                        ],

                        'links' => [
                                'policy_pd'          => esc_url( (string) get_option('f152_link_policy_pd', '') ),
                                'consent_pd'         => esc_url( (string) get_option('f152_link_consent_pd', '') ),
                                'policy_cookie'      => esc_url( (string) get_option('f152_link_policy_cookie', '') ),
                                'consent_marketing'  => esc_url( (string) get_option('f152_link_consent_marketing', '') ),
                        ],

			'i18n' => [
				'accept'  => __('Принять', 'fz-152-rf'),
				'settings'=> __('Настроить', 'fz-152-rf'),
				'reject'  => __('Отклонить', 'fz-152-rf'),
				'save'    => __('Сохранить', 'fz-152-rf'),
				'close'   => __('Закрыть', 'fz-152-rf'),
				'embed_map_blocked' => __('Карта скрыта до разрешения аналитических cookie.', 'fz-152-rf'),
				'embed_video_blocked' => __('Видео скрыто до разрешения аналитических cookie.', 'fz-152-rf'),
				'embed_content_blocked' => __('Внешнее содержимое скрыто до разрешения аналитических cookie.', 'fz-152-rf'),
				'embed_map_blocked_marketing' => __('Карта скрыта до разрешения маркетинговых cookie.', 'fz-152-rf'),
				'embed_video_blocked_marketing' => __('Видео скрыто до разрешения маркетинговых cookie.', 'fz-152-rf'),
				'embed_content_blocked_marketing' => __('Внешнее содержимое скрыто до разрешения маркетинговых cookie.', 'fz-152-rf'),
				'embed_allow_map' => __('Разрешить и показать карту', 'fz-152-rf'),
				'embed_allow_video' => __('Разрешить и показать видео', 'fz-152-rf'),
				'embed_allow' => __('Разрешить и показать', 'fz-152-rf'),
				'required_message' => __('Для продолжения необходимо дать согласие на обработку персональных данных.', 'fz-152-rf'),
			],

			'bannerStats' => [
				'enabled'           => (bool) get_option('f152_enabled', 1),
				'endpointUrl'       => function_exists('rest_url') ? rest_url('f152/v1/banner-stats') : '',
				'ignoreAfterSeconds'=> class_exists('\\F152\\BannerStats') ? \F152\BannerStats::get_ignore_after_seconds() : 1800,
				'sessionStorageKey' => 'f152_bs_token',
			],
		];
	}

	private static function build_css_vars_inline() : string {
	               $bg       = Helpers::sanitize_hex_color_soft( get_option('f152_color_bg', '') );
	               $text     = Helpers::sanitize_hex_color_soft( get_option('f152_color_text', '') );
	               $link     = Helpers::sanitize_hex_color_soft( get_option('f152_color_link', '') );
	               $btn_bg   = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_bg', '') );
	               $btn_text = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_text', '') );
				$btn_radius = Settings::sanitize_radius( get_option('f152_btn_radius', '') );
	               $mode     = Settings::sanitize_theme_mode( (string) get_option('f152_theme', 'light') );

	               $acc_bg   = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_accept_bg', '') );
	               $acc_text = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_accept_text', '') );
	               $set_bg   = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_settings_bg', '') );
	               $set_text = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_settings_text', '') );
	               $rej_bg   = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_reject_bg', '') );
	               $rej_text = Helpers::sanitize_hex_color_soft( get_option('f152_color_btn_reject_text', '') );

	               $font_size_text = Settings::sanitize_font_size( get_option('f152_font_size_text', '') );
	               $font_size_btn  = Settings::sanitize_font_size( get_option('f152_font_size_btn', '') );

	               $vars = [];
	               if ( $bg !== '' )       $vars[] = sprintf( '--f152-bg: %s;', esc_attr( $bg ) );
	               if ( $text !== '' )     $vars[] = sprintf( '--f152-text: %s;', esc_attr( $text ) );
	               if ( $link !== '' )     $vars[] = sprintf( '--f152-link: %s;', esc_attr( $link ) );
	               if ( $btn_bg !== '' )   $vars[] = sprintf( '--f152-btn-bg: %s;', esc_attr( $btn_bg ) );
	               if ( $btn_text !== '' ) $vars[] = sprintf( '--f152-btn-text: %s;', esc_attr( $btn_text ) );
				if ( $btn_radius !== '' ) $vars[] = sprintf( '--f152-btn-radius: %spx;', esc_attr( $btn_radius ) );

	               if ( $font_size_text !== '' ) $vars[] = sprintf( '--f152-font-size-text: %spx;', esc_attr( $font_size_text ) );
	               if ( $font_size_btn !== '' )  $vars[] = sprintf( '--f152-font-size-btn: %spx;', esc_attr( $font_size_btn ) );

	               if ( $acc_bg !== '' )   $vars[] = sprintf( '--f152-btn-accept-bg: %s;', esc_attr( $acc_bg ) );
	               if ( $acc_text !== '' ) $vars[] = sprintf( '--f152-btn-accept-text: %s;', esc_attr( $acc_text ) );

	               if ( $set_bg !== '' )   $vars[] = sprintf( '--f152-btn-settings-bg: %s;', esc_attr( $set_bg ) );
	               if ( $set_text !== '' ) $vars[] = sprintf( '--f152-btn-settings-text: %s;', esc_attr( $set_text ) );

	               if ( $rej_bg !== '' )   $vars[] = sprintf( '--f152-btn-reject-bg: %s;', esc_attr( $rej_bg ) );
	               if ( $rej_text !== '' ) $vars[] = sprintf( '--f152-btn-reject-text: %s;', esc_attr( $rej_text ) );

		$selector = 'body';

		if ( empty($vars) && $mode === 'light' ) {
			return '';
		}

		              $css  = '';
		              $css .= sprintf( '%s.f152-theme-%s {%s', $selector, esc_attr( $mode ), "\n" );
                if ( ! empty($vars) ) {
                        $css .= '  ' . implode("\n  ", $vars) . "\n";
                }
                $css .= "}\n";

		return $css;
	}

}
