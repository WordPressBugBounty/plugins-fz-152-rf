<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class API {

	public const DEFAULT_NONCE_ACTION = 'f152_external_consent';


	private static $context_option_map = [
		'comment'  => 'f152_comment_text',
		'reviews'  => 'f152_reviews_text',
		'checkout' => 'f152_checkout_text',
	];

	public static function is_enabled() : bool {
		return (bool) get_option( 'f152_enabled', 1 );
	}

	public static function get_policy_version() : string {
		$value = (string) get_option( 'f152_policy_version', '' );
		return sanitize_text_field( $value );
	}

	public static function get_policy_url() : string {
		$value = (string) get_option( 'f152_link_policy_pd', '' );
		$value = trim( $value );

		if ( $value === '' ) {
			return '';
		}

		return esc_url_raw( $value );
	}

	public static function get_consent_text( string $context = 'external' ) : string {
		$raw = '';

		if ( isset( self::$context_option_map[ $context ] ) ) {
			$opt = (string) get_option( self::$context_option_map[ $context ], '' );
			$raw = '' !== trim( $opt ) ? $opt : Helpers::default_personal_consent_text();
		} else {
			$opt = (string) get_option( 'f152_external_form_text', '' );
			$raw = '' !== trim( $opt ) ? $opt : Helpers::default_personal_consent_text();
		}

		$text = $raw;

		if ( class_exists( '\F152\Helpers' ) && method_exists( '\F152\Helpers', 'replace_common_macros' ) ) {
			$text = Helpers::replace_common_macros( $text );
		}

		if ( class_exists( '\F152\Helpers' ) && method_exists( '\F152\Helpers', 'kses_paragraph' ) ) {
			$text = Helpers::kses_paragraph( $text );
		}

		return $text;
	}

	public static function create_nonce( string $action = self::DEFAULT_NONCE_ACTION ) : string {
		return (string) wp_create_nonce( $action );
	}

	public static function verify_nonce( ?string $nonce, string $action = self::DEFAULT_NONCE_ACTION ) : bool {
		if ( $nonce === null || $nonce === '' ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $nonce ) );

		if ( $nonce === '' ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, $action );
	}

	public static function get_context_fields_html( array $context = [] ) : string {
		$context = self::prepare_context( $context );

		$policy_version = self::get_policy_version();
		$nonce          = self::create_nonce();
		$consent_hash   = self::build_consent_hash(
			self::get_consent_text( 'external' ),
			$policy_version,
			(string) ( $context['f152_source_type'] ?? '' ),
			(string) ( $context['f152_source_id'] ?? '' ),
			(string) ( $context['f152_form_id'] ?? '' )
		);

		$fields = [
			'f152_source_type'             => (string) ( $context['f152_source_type'] ?? '' ),
			'f152_source_id'               => (string) ( $context['f152_source_id'] ?? '' ),
			'f152_source_plugin'           => (string) ( $context['f152_source_plugin'] ?? '' ),
			'f152_source_label'            => (string) ( $context['f152_source_label'] ?? '' ),
			'f152_page_url'                => (string) ( $context['f152_page_url'] ?? '' ),
			'f152_form_id'                 => (string) ( $context['f152_form_id'] ?? '' ),
			'f152_form_title'              => (string) ( $context['f152_form_title'] ?? '' ),
			'f152_policy_version'          => $policy_version,
			'f152_consent_hash'            => $consent_hash,
			'f152_external_consent_nonce'  => $nonce,
		];

		$html = '';
		foreach ( $fields as $name => $value ) {
			$html .= sprintf(
				'<input type="hidden" name="%s" value="%s">' . "\n",
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		return $html;
	}

	public static function get_checkbox_html( array $args = [] ) : string {
		$name          = isset( $args['name'] ) && $args['name'] !== '' ? sanitize_text_field( (string) $args['name'] ) : 'f152_consent';
		$id            = isset( $args['id'] ) && $args['id'] !== '' ? sanitize_text_field( (string) $args['id'] ) : 'f152_consent';
		$context       = isset( $args['context'] ) && $args['context'] !== '' ? sanitize_text_field( (string) $args['context'] ) : 'external';
		$required      = isset( $args['required'] ) ? (bool) $args['required'] : true;
		$wrapper_class = isset( $args['wrapper_class'] ) && $args['wrapper_class'] !== ''
			? sanitize_html_class( (string) $args['wrapper_class'] )
			: 'f152-consent f152-consent--external';

		if ( isset( $args['text'] ) && is_string( $args['text'] ) && $args['text'] !== '' ) {
			$text = $args['text'];
			if ( class_exists( '\F152\Helpers' ) && method_exists( '\F152\Helpers', 'replace_common_macros' ) ) {
				$text = Helpers::replace_common_macros( $text );
			}
			if ( class_exists( '\F152\Helpers' ) && method_exists( '\F152\Helpers', 'kses_paragraph' ) ) {
				$text = Helpers::kses_paragraph( $text );
			}
		} else {
			$text = self::get_consent_text( $context );
		}

		$context_fields = self::get_context_fields_html( $args );

		$html = sprintf(
			'<div class="%1$s"><label><input type="checkbox" name="%2$s" id="%3$s" value="1"%4$s><span>%5$s</span></label>%6$s</div>',
			esc_attr( $wrapper_class ),
			esc_attr( $name ),
			esc_attr( $id ),
			$required ? ' required' : '',
			wp_kses_post( $text ),
			$context_fields
		);

		$filtered = apply_filters( 'f152/checkbox_html', $html, $args );
		return is_string( $filtered ) ? $filtered : $html;
	}

	public static function render_checkbox( array $args = [] ) : void {
		echo self::get_checkbox_html( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is built with escaping internally.
	}

	public static function verify_consent( array $request = [], array $args = [] ) : array {
		$result = [
			'valid'         => false,
			'consent_given' => false,
			'nonce_valid'   => false,
			'error'         => '',
		];

		if ( ! self::is_enabled() ) {
			$result['valid']         = true;
			$result['consent_given'] = true;
			$result['nonce_valid']   = true;
			return $result;
		}

		$field        = isset( $args['field'] ) && $args['field'] !== '' ? sanitize_text_field( (string) $args['field'] ) : 'f152_consent';
		$nonce_field  = isset( $args['nonce_field'] ) && $args['nonce_field'] !== '' ? sanitize_text_field( (string) $args['nonce_field'] ) : 'f152_external_consent_nonce';
		$nonce_action = isset( $args['nonce_action'] ) && $args['nonce_action'] !== '' ? sanitize_text_field( (string) $args['nonce_action'] ) : self::DEFAULT_NONCE_ACTION;

		if ( empty( $request ) ) {
			$request = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- intentional; nonce checked below.
		}

		$nonce_raw = isset( $request[ $nonce_field ] ) ? $request[ $nonce_field ] : '';
		$nonce     = is_string( $nonce_raw ) ? $nonce_raw : '';

		$result['nonce_valid'] = self::verify_nonce( $nonce, $nonce_action );

		if ( ! $result['nonce_valid'] ) {
			$result['valid'] = false;
			$result['error'] = __( 'Ошибка проверки безопасности. Обновите страницу и повторите попытку.', 'fz-152-rf' );
			return $result;
		}

		$consent_raw = isset( $request[ $field ] ) ? $request[ $field ] : '';
		$consent_val = is_string( $consent_raw ) ? sanitize_text_field( wp_unslash( $consent_raw ) ) : '';

		$result['consent_given'] = ( $consent_val === '1' );

		if ( ! $result['consent_given'] ) {
			$result['valid'] = false;
			$result['error'] = __( 'Для отправки требуется согласие на обработку персональных данных.', 'fz-152-rf' );
			return $result;
		}

		$result['valid'] = true;
		return $result;
	}

	public static function log_consent( array $data ) {
		if ( ! isset( $data['user_id'] ) ) {
			$data['user_id'] = get_current_user_id();
		}
		if ( ! isset( $data['ip_address'] ) ) {
			$data['ip_address'] = isset( $_SERVER['REMOTE_ADDR'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
				: '';
		}
		if ( ! isset( $data['user_agent'] ) ) {
			$data['user_agent'] = isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
				: '';
		}
		if ( ! isset( $data['policy_version'] ) ) {
			$data['policy_version'] = self::get_policy_version();
		}
		if ( ! isset( $data['policy_url'] ) ) {
			$data['policy_url'] = self::get_policy_url();
		}
		if ( ! isset( $data['consent_text'] ) ) {
			$data['consent_text'] = self::get_consent_text( 'external' );
		}

		$filtered = apply_filters( 'f152/consent_log_data', $data );
		if ( ! is_array( $filtered ) ) {
			$data['_f152_error'] = 'The f152/consent_log_data filter returned a non-array value.';
			do_action( 'f152/consent_log_failed', $data );
			return false;
		}
		$data = $filtered;

		if ( ! class_exists( '\F152\ConsentLog' ) || ! method_exists( '\F152\ConsentLog', 'log' ) ) {
			$data['_f152_error'] = 'ConsentLog class or ConsentLog::log() not available.';
			do_action( 'f152/consent_log_failed', $data );
			return false;
		}

		$ok = ConsentLog::log( $data );

		if ( ! $ok ) {
			$data['_f152_error'] = 'ConsentLog::log() rejected the data.';
			do_action( 'f152/consent_log_failed', $data );
			return false;
		}

		global $wpdb;
		$insert_id = isset( $wpdb->insert_id ) ? (int) $wpdb->insert_id : 0;

		if ( $insert_id <= 0 ) {
			$data['_f152_error'] = 'ConsentLog::log() reported success but no insert_id is available.';
			do_action( 'f152/consent_log_failed', $data );
			return false;
		}

		do_action( 'f152/consent_logged', $insert_id, $data );

		return $insert_id;
	}

	public static function get_consent_cookie() : array {
		if ( class_exists( '\F152\Consent' ) && method_exists( '\F152\Consent', 'read' ) ) {
			return Consent::read();
		}

		return [];
	}

	public static function get_cookie_consent_state() : array {
		if ( class_exists( '\F152\Consent' ) && method_exists( '\F152\Consent', 'state' ) ) {
			return Consent::state();
		}

		return [
			'exists'          => false,
			'current'         => false,
			'legacy'          => false,
			'current_version' => self::get_policy_version(),
			'consent_version' => null,
			'analytics'       => null,
			'marketing'       => null,
		];
	}

	public static function is_cookie_category_allowed( string $category ) : bool {
		if ( class_exists( '\F152\Consent' ) && method_exists( '\F152\Consent', 'is_category_allowed' ) ) {
			return Consent::is_category_allowed( $category );
		}

		return false;
	}

	public static function has_current_cookie_consent() : bool {
		$state = self::get_cookie_consent_state();

		return true === $state['current'];
	}

	public static function get_cookie_consent_version() : string {
		if ( class_exists( '\F152\Consent' ) && method_exists( '\F152\Consent', 'current_version' ) ) {
			return Consent::current_version();
		}

		return self::get_policy_version();
	}

	public static function get_capabilities() : array {
		$capabilities = [
			'external_consent_api'   => 1,
			'cookie_consent_api'     => 1,
			'banner_designer_hooks'  => 1,
			'services_bridge'        => 1,
			'service_catalog'        => 1,
			'service_extensions'     => 2,
			'embed_provider_extensions' => 2,
			'service_inventory'      => 1,
			'policy_services'        => 1,
			'policy_reflection'      => 2,
			'policy_ownership'       => 1,
			'free_policy_sync'        => 1,
			'policy_legacy_adoption' => 1,
		];

		$base_capabilities = $capabilities;
		$filtered = apply_filters( 'f152/capabilities', $capabilities );

		$normalized = [];
		foreach ( $base_capabilities as $name => $version ) {
			$name = sanitize_key( (string) $name );
			if ( '' !== $name ) {
				$normalized[ $name ] = max( 1, (int) $version );
			}
		}

		if ( ! is_array( $filtered ) ) {
			return $normalized;
		}

		foreach ( $filtered as $name => $version ) {
			$name = sanitize_key( (string) $name );
			if ( '' === $name ) {
				continue;
			}
			$version = max( 0, (int) $version );
			if ( ! isset( $normalized[ $name ] ) || $version > $normalized[ $name ] ) {
				$normalized[ $name ] = $version;
			}
		}
		return $normalized;
	}

	public static function has_capability( string $capability, int $min_version = 1 ) : bool {
		$capability = sanitize_key( $capability );
		if ( '' === $capability ) {
			return false;
		}
		$capabilities = self::get_capabilities();
		return isset( $capabilities[ $capability ] )
			&& (int) $capabilities[ $capability ] >= max( 1, $min_version );
	}

	public static function services_setting_updated( $old_value = null, $new_value = null ) : void {
		if ( class_exists( '\\F152\\Settings' ) && is_callable( [ '\\F152\\Settings', 'handle_services_setting_updated' ] ) ) {
			Settings::handle_services_setting_updated( $old_value, $new_value );
		}
	}

	public static function get_service_catalog() : array {
		return class_exists( '\\F152\\ServiceCatalog' ) && is_callable( [ '\\F152\\ServiceCatalog', 'all' ] )
			? ServiceCatalog::all()
			: [];
	}

	public static function get_service_definition( string $service_id ) : array {
		return class_exists( '\\F152\\ServiceCatalog' ) && is_callable( [ '\\F152\\ServiceCatalog', 'get' ] )
			? (array) ServiceCatalog::get( sanitize_key( $service_id ) )
			: [];
	}

	public static function get_service_inventory() : array {
		return class_exists( '\\F152\\ServiceInventory' ) && is_callable( [ '\\F152\\ServiceInventory', 'get' ] )
			? ServiceInventory::get()
			: [];
	}

	public static function get_policy_services_zone_version() : int {
		return defined( '\\F152\\PolicyServices::ZONE_VERSION' )
			? max( 1, (int) PolicyServices::ZONE_VERSION )
			: 0;
	}

	public static function render_policy_services_zone( string $document, ?array $service_ids = null ) : string {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'render_zone' ] )
			? PolicyServices::render_zone( $document, $service_ids )
			: '';
	}

	public static function get_generated_policy_page_id( string $document ) : int {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'generated_page_id' ] )
			? (int) PolicyServices::generated_page_id( $document )
			: 0;
	}

	public static function match_known_legacy_policy( string $document, string $content ) : array {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'known_legacy_match' ] )
			? (array) PolicyServices::known_legacy_match( $document, $content )
			: [];
	}

	public static function upgrade_known_legacy_policy( string $document, string $content ) : array {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'upgrade_known_legacy_content' ] )
			? (array) PolicyServices::upgrade_known_legacy_content( $document, $content )
			: [];
	}

	public static function policy_service_is_reflected( string $service_id ) : bool {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'reflected_in_generated_policies' ] )
			? (bool) PolicyServices::reflected_in_generated_policies( sanitize_key( $service_id ) )
			: false;
	}

	public static function get_policy_service_reflection_status( string $service_id ) : string {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'reflection_status' ] )
			? (string) PolicyServices::reflection_status( sanitize_key( $service_id ) )
			: ( self::policy_service_is_reflected( $service_id ) ? 'managed' : 'missing' );
	}

	public static function get_free_managed_policy_services() : array {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'free_managed_services' ] )
			? (array) PolicyServices::free_managed_services()
			: [];
	}

	public static function policy_has_future_service_markers( string $content ) : bool {
		return class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'has_future_service_markers' ] )
			? (bool) PolicyServices::has_future_service_markers( $content )
			: false;
	}

	public static function table_ready() : bool {
		if ( ! class_exists( '\F152\ConsentLog' ) || ! method_exists( '\F152\ConsentLog', 'maybe_create_table' ) ) {
			return false;
		}

		try {
			return (bool) ConsentLog::maybe_create_table();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private static function prepare_context( array $context ) : array {
		$aliases = [
			'source_type'   => 'f152_source_type',
			'source_id'     => 'f152_source_id',
			'source_plugin' => 'f152_source_plugin',
			'source_label'  => 'f152_source_label',
			'page_url'      => 'f152_page_url',
			'form_id'       => 'f152_form_id',
			'form_title'    => 'f152_form_title',
		];

		foreach ( $aliases as $short => $full ) {
			if ( isset( $context[ $short ] ) && ! isset( $context[ $full ] ) ) {
				$context[ $full ] = $context[ $short ];
			}
		}

		if ( empty( $context['f152_page_url'] ) ) {
			$context['f152_page_url'] = self::detect_current_url();
		}

		foreach ( [
			'f152_source_type',
			'f152_source_id',
			'f152_source_plugin',
			'f152_source_label',
			'f152_page_url',
			'f152_form_id',
			'f152_form_title',
		] as $key ) {
			if ( isset( $context[ $key ] ) ) {
				$val = is_scalar( $context[ $key ] ) ? (string) $context[ $key ] : '';
				$context[ $key ] = ( $key === 'f152_page_url' ) ? esc_url_raw( trim( $val ) ) : sanitize_text_field( $val );
			}
		}

		return $context;
	}

	private static function detect_current_url() : string {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( $host === '' ) {
			return '';
		}

		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	private static function build_consent_hash( string $consent_text, string $policy_version, string $source_type, string $source_id, string $form_id ) : string {
		if ( ! function_exists( 'hash' ) ) {
			return '';
		}

		$id_part = $source_id !== '' ? $source_id : $form_id;
		$payload = $consent_text . '|' . $policy_version . '|' . $source_type . '|' . $id_part;

		$hash = hash( 'sha256', $payload );

		return is_string( $hash ) ? $hash : '';
	}
}