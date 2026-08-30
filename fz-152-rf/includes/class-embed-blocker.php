<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmbedBlocker {

	private const OPTION_YANDEX_MAPS_BEHAVIOR = 'f152_yandex_maps_behavior';
	private const ENGINE_VERSION              = '7';
	private const OPTION_ENGINE_VERSION       = 'f152_embed_blocker_version';

	private static $embed_counter = 0;

	public static function maybe_upgrade() : void {
		$stored = (string) get_option( self::OPTION_ENGINE_VERSION, '' );
		if ( self::ENGINE_VERSION === $stored ) {
			return;
		}

		update_option( self::OPTION_ENGINE_VERSION, self::ENGINE_VERSION, false );
		self::clear_scanner_cache();

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	public static function init() : void {
		if ( ! get_option( 'f152_enabled', 1 ) || ! self::has_enabled_provider() ) {
			return;
		}

		add_action( 'template_redirect', [ __CLASS__, 'start_buffer' ], -9999 );
	}

	public static function start_buffer() : void {
		if ( ! self::should_buffer_request() ) {
			return;
		}

		ob_start( [ __CLASS__, 'filter_html' ] );
	}

	public static function yandex_maps_behavior() : string {
		if ( class_exists( '\\F152\\Settings' ) ) {
			return Settings::get_yandex_maps_behavior();
		}

		$mode = sanitize_key( (string) get_option( self::OPTION_YANDEX_MAPS_BEHAVIOR, 'always' ) );
		return in_array( $mode, [ 'always', 'disable_on_reject', 'require_accept' ], true ) ? $mode : 'always';
	}

	public static function yandex_maps_enabled() : bool {
		return 'always' !== self::yandex_maps_behavior();
	}

	public static function providers() : array {
		$providers = [
			'yandex_maps' => [
				'label'        => 'Яндекс.Карты',
				'type'         => 'map',
				'category'     => 'analytics',
				'mode'         => self::yandex_maps_behavior(),
				'enabled'      => self::yandex_maps_enabled(),
				'url_patterns' => [
					'~^(?:https?:)?//api-maps\.yandex\.(?:ru|com)/frame/v1/~i',
					'~^(?:https?:)?//(?:www\.)?yandex\.(?:ru|com)/map-widget/~i',
				],
			],
		];

		$providers = apply_filters( 'f152_embed_providers', $providers );
		return is_array( $providers ) ? $providers : [];
	}

	public static function filter_html( string $html ) : string {
		if ( '' === $html ) {
			return $html;
		}

		if ( self::yandex_maps_enabled() && self::contains_yandex_constructor( $html ) ) {
			$html = self::protect_constructor_scripts( $html );
		}

		$html = self::apply_provider_html_filters( $html );

		if ( false === stripos( $html, '<iframe' ) ) {
			return $html;
		}

		$parts = preg_split(
			'~(<(?:script|style|textarea|template)\b[^>]*>.*?</(?:script|style|textarea|template)\s*>)~is',
			$html,
			-1,
			PREG_SPLIT_DELIM_CAPTURE
		);

		if ( ! is_array( $parts ) ) {
			return $html;
		}

		foreach ( $parts as $index => $part ) {
			if ( 1 === $index % 2 ) {
				continue;
			}
			$parts[ $index ] = self::filter_iframe_fragment( $part );
		}

		return implode( '', $parts );
	}


	private static function apply_provider_html_filters( string $html ) : string {
		foreach ( self::providers() as $provider ) {
			if ( ! is_array( $provider ) || empty( $provider['enabled'] ) || empty( $provider['html_filter'] ) || ! is_callable( $provider['html_filter'] ) ) {
				continue;
			}

			$filtered = call_user_func( $provider['html_filter'], $html );
			if ( is_string( $filtered ) ) {
				$html = $filtered;
			}
		}

		return $html;
	}

	private static function should_buffer_request() : bool {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_trackback() || is_robots() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}

		return true;
	}

	private static function has_enabled_provider() : bool {
		foreach ( self::providers() as $provider ) {
			if ( is_array( $provider ) && ! empty( $provider['enabled'] ) ) {
				return true;
			}
		}
		return false;
	}

	private static function contains_yandex_constructor( string $html ) : bool {
		return false !== stripos( $html, 'api-maps.yandex.' )
			&& false !== stripos( $html, '/services/constructor/' );
	}

	private static function protect_constructor_scripts( string $html ) : string {
		$result = preg_replace_callback(
			'~<script\b[^>]*\bsrc\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)[^>]*>\s*</script\s*>~is',
			static function ( array $matches ) : string {
				$tag = (string) $matches[0];
				$src = EmbedBlocker::get_attribute( $tag, 'src' );
				if ( ! EmbedBlocker::is_yandex_constructor_url( $src ) ) {
					return $tag;
				}

				return EmbedBlocker::protect_constructor_script( $src );
			},
			$html
		);

		return is_string( $result ) ? $result : $html;
	}

	private static function is_yandex_constructor_url( string $url ) : bool {
		$url = html_entity_decode( trim( $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( '' === $url ) {
			return false;
		}

		return 1 === preg_match(
			'~^(?:https?:)?//api-maps\.yandex\.(?:ru|com)/services/constructor/1\.0/js/~i',
			$url
		);
	}

	private static function protect_constructor_script( string $source ) : string {
		$source = html_entity_decode( trim( $source ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( '' === $source ) {
			return '';
		}

		$mode  = self::yandex_maps_behavior();
		$token = self::next_token( $source );
		$style = self::constructor_placeholder_style( $source );

		$placeholder = self::build_placeholder( 'yandex_maps', $token, 'map', 'analytics', $mode, $style );
		$marker      = sprintf(
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Inert text/plain data marker; it never executes as JavaScript and is activated later by the already-enqueued FZ-152 frontend script.
			'<script type="text/plain" data-f152-embed-script="yandex_maps" data-f152-embed-category="analytics" data-f152-embed-mode="%1$s" data-f152-embed-label="%2$s" data-f152-embed-type="map" data-f152-embed-src="%3$s" data-f152-embed-token="%4$s" data-f152-embed-active="0"></script>',
			esc_attr( $mode ),
			esc_attr__( 'Яндекс.Карты', 'fz-152-rf' ),
			esc_attr( base64_encode( $source ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Transport only; decoded in the browser.
			esc_attr( $token )
		);

		return $placeholder . $marker;
	}

	private static function filter_iframe_fragment( string $html ) : string {
		$providers = self::providers();
		if ( empty( $providers ) ) {
			return $html;
		}

		$result = preg_replace_callback(
			'~<iframe\b[^>]*>~i',
			static function ( array $matches ) use ( $providers ) : string {
				return EmbedBlocker::protect_iframe_tag( (string) $matches[0], $providers );
			},
			$html
		);

		return is_string( $result ) ? $result : $html;
	}

	private static function protect_iframe_tag( string $tag, array $providers ) : string {
		if ( false !== stripos( $tag, 'data-f152-embed=' ) ) {
			return $tag;
		}

		$url_attrs   = [ 'src', 'data-src', 'data-lazy-src', 'data-original', 'data-lazyload-src' ];
		$source      = '';
		$provider_id = '';
		$provider    = [];

		foreach ( $url_attrs as $attr ) {
			$value = self::get_attribute( $tag, $attr );
			if ( '' === $value ) {
				continue;
			}

			$matched = self::match_provider( $value, $providers );
			if ( null !== $matched ) {
				$source      = $value;
				$provider_id = $matched['id'];
				$provider    = $matched['provider'];
				break;
			}
		}

		if ( '' === $source || '' === $provider_id || empty( $provider ) ) {
			return $tag;
		}

		$source   = html_entity_decode( trim( $source ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$mode     = sanitize_key( (string) ( $provider['mode'] ?? 'require_accept' ) );
		$category = sanitize_key( (string) ( $provider['category'] ?? 'analytics' ) );
		$type     = sanitize_key( (string) ( $provider['type'] ?? 'content' ) );
		$token    = self::next_token( $source );
		$style    = self::iframe_placeholder_style( $tag );

		foreach ( $url_attrs as $attr ) {
			$tag = self::remove_attribute( $tag, $attr );
		}
		$tag = self::remove_attribute( $tag, 'srcdoc' );
		$tag = self::remove_attribute( $tag, 'hidden' );
		$tag = self::remove_attribute( $tag, 'aria-hidden' );

		$tag = self::append_class( $tag, 'f152-embed-frame' );
		$tag = self::append_attributes(
			$tag,
			[
				'data-f152-embed'          => $provider_id,
				'data-f152-embed-category' => $category,
				'data-f152-embed-mode'     => $mode,
				'data-f152-embed-label'    => sanitize_text_field( (string) ( $provider['label'] ?? '' ) ),
				'data-f152-embed-type'     => $type,
				'data-f152-embed-src'      => base64_encode( $source ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Transport only; decoded in the browser.
				'data-f152-embed-token'    => $token,
				'hidden'                   => null,
				'aria-hidden'              => 'true',
			]
		);

		return self::build_placeholder( $provider_id, $token, $type, $category, $mode, $style ) . $tag;
	}

	private static function build_placeholder( string $provider_id, string $token, string $type, string $category, string $mode, string $style = '' ) : string {
		$is_map   = 'map' === $type;
		$is_video = 'video' === $type;
		if ( $is_map ) {
			$text   = __( 'Карта скрыта до разрешения аналитических cookie.', 'fz-152-rf' );
			$button = __( 'Разрешить и показать карту', 'fz-152-rf' );
		} elseif ( $is_video ) {
			$text   = __( 'Видео скрыто до разрешения аналитических cookie.', 'fz-152-rf' );
			$button = __( 'Разрешить и показать видео', 'fz-152-rf' );
		} else {
			$text   = __( 'Внешнее содержимое скрыто до разрешения аналитических cookie.', 'fz-152-rf' );
			$button = __( 'Разрешить и показать', 'fz-152-rf' );
		}

		$hidden = 'require_accept' === $mode ? '' : ' hidden';
		$aria   = 'require_accept' === $mode ? 'false' : 'true';

		return sprintf(
			'<div class="f152-embed-placeholder" data-f152-embed-placeholder="%1$s" data-f152-embed-token="%2$s"%3$s aria-hidden="%4$s"%5$s><div class="f152-embed-placeholder__text">%6$s</div><button type="button" class="f152-btn f152-btn--accept f152-embed-placeholder__button" data-f152-embed-allow="%7$s">%8$s</button></div>',
			esc_attr( $provider_id ),
			esc_attr( $token ),
			$hidden,
			esc_attr( $aria ),
			'' !== $style ? ' style="' . esc_attr( $style ) . '"' : '',
			esc_html( $text ),
			esc_attr( $category ),
			esc_html( $button )
		);
	}

	private static function next_token( string $source ) : string {
		self::$embed_counter++;
		return 'f152-embed-' . substr( md5( $source . '|' . (string) self::$embed_counter ), 0, 12 );
	}

	private static function constructor_placeholder_style( string $source ) : string {
		$query = wp_parse_url( $source, PHP_URL_QUERY );
		if ( ! is_string( $query ) || '' === $query ) {
			return '';
		}

		$params = [];
		parse_str( html_entity_decode( $query, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $params );

		return self::build_dimension_style( $params['width'] ?? '', $params['height'] ?? '' );
	}

	private static function iframe_placeholder_style( string $tag ) : string {
		return self::build_dimension_style(
			self::get_attribute( $tag, 'width' ),
			self::get_attribute( $tag, 'height' )
		);
	}

	private static function build_dimension_style( $width_raw, $height_raw ) : string {
		$styles = [];
		$width  = self::sanitize_dimension( $width_raw, true );
		$height = self::sanitize_dimension( $height_raw, false );

		if ( '' !== $width ) {
			$styles[] = 'width:' . $width;
		}
		if ( '' !== $height ) {
			$styles[] = 'min-height:' . $height;
		}

		return implode( ';', $styles );
	}

	private static function sanitize_dimension( $value, bool $allow_percent ) : string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^\d+(?:\.\d+)?$/', $value ) ) {
			return $value . 'px';
		}

		$units = $allow_percent ? '(?:px|%|vw|vh|rem|em)' : '(?:px|vh|rem|em)';
		if ( preg_match( '/^\d+(?:\.\d+)?' . $units . '$/i', $value ) ) {
			return strtolower( $value );
		}

		return '';
	}

	private static function match_provider( string $url, array $providers ) : ?array {
		$url = html_entity_decode( trim( $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( '' === $url ) {
			return null;
		}

		foreach ( $providers as $id => $provider ) {
			if ( ! is_array( $provider ) || empty( $provider['enabled'] ) || empty( $provider['url_patterns'] ) || ! is_array( $provider['url_patterns'] ) ) {
				continue;
			}

			foreach ( $provider['url_patterns'] as $pattern ) {
				if ( is_string( $pattern ) && 1 === preg_match( $pattern, $url ) ) {
					return [
						'id'       => sanitize_key( (string) $id ),
						'provider' => $provider,
					];
				}
			}
		}

		return null;
	}

	private static function get_attribute( string $tag, string $name ) : string {
		$name = preg_quote( $name, '~' );
		if ( preg_match( '~\s' . $name . '\s*=\s*(["\'])(.*?)\1~is', $tag, $match ) ) {
			return (string) $match[2];
		}
		if ( preg_match( '~\s' . $name . '\s*=\s*([^\s>]+)~i', $tag, $match ) ) {
			return trim( (string) $match[1], "\"'" );
		}
		return '';
	}

	private static function remove_attribute( string $tag, string $name ) : string {
		$name = preg_quote( $name, '~' );
		$tag = preg_replace( '~\s+' . $name . '\s*=\s*(["\']).*?\1~is', '', $tag );
		$tag = preg_replace( '~\s+' . $name . '\s*=\s*[^\s>]+~i', '', (string) $tag );
		$tag = preg_replace( '~\s+' . $name . '(?=\s|/?>)~i', '', (string) $tag );
		return is_string( $tag ) ? $tag : '';
	}

	private static function append_class( string $tag, string $class_name ) : string {
		if ( preg_match( '~\sclass\s*=\s*(["\'])(.*?)\1~is', $tag, $match ) ) {
			$classes = trim( (string) $match[2] . ' ' . $class_name );
			return (string) preg_replace(
				'~\sclass\s*=\s*(["\'])(.*?)\1~is',
				' class="' . esc_attr( $classes ) . '"',
				$tag,
				1
			);
		}

		return self::append_attributes( $tag, [ 'class' => $class_name ] );
	}

	private static function append_attributes( string $tag, array $attributes ) : string {
		$markup = '';
		foreach ( $attributes as $name => $value ) {
			$name = sanitize_key( str_replace( '_', '-', (string) $name ) );
			if ( '' === $name ) {
				continue;
			}
			if ( null === $value ) {
				$markup .= ' ' . $name;
			} else {
				$markup .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
			}
		}

		return (string) preg_replace( '~\s*/?>$~', $markup . '>', $tag, 1 );
	}

	private static function clear_scanner_cache() : void {
		delete_transient( 'f152_service_scan_home_v2' );
		delete_transient( 'f152_service_scan_home_v3' );
		delete_transient( 'f152_service_scan_site_v4' );
		delete_transient( 'f152_service_scan_site_v5' );
		delete_transient( 'f152_service_scan_site_v6' );
		delete_transient( 'f152_service_scan_site_v7' );
		delete_transient( 'f152_service_scan_site_v8' );
		delete_transient( 'f152_service_scan_site_v9' );
	}

}
