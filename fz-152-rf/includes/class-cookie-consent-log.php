<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
 
final class CookieConsentLog {
	private const DB_VERSION = '2';
	private const CLEANUP_HOOK = 'f152_cookie_consent_log_cleanup';
	private const MAX_BODY_BYTES = 4096;
	private const CLEANUP_BATCH = 1000;
	private const CLEANUP_MAX_BATCHES = 20;
	private const RETENTION_DAYS = [ 0, 30, 90, 180, 365, 730 ];
	private const ACTION_SOURCES = [
		'accept_all'       => [ 'banner', 'preferences' ],
		'reject_optional'  => [ 'banner', 'preferences' ],
		'save_preferences' => [ 'preferences' ],
		'grant_category'   => [ 'embed', 'api' ],
	];

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		add_action( self::CLEANUP_HOOK, [ __CLASS__, 'do_cleanup' ] );
		add_action( 'update_option_f152_cookie_log_enabled', [ __CLASS__, 'handle_enabled_updated' ], 10, 2 );
		add_action( 'added_option', [ __CLASS__, 'handle_option_added' ], 10, 2 );
	}

	public static function ensure_defaults(): void {
		foreach ( [
			'f152_cookie_log_enabled'            => 0,
			'f152_cookie_log_capture_ip'         => 1,
			'f152_cookie_log_capture_user_agent' => 1,
			'f152_cookie_log_retention_days'     => 365,
		] as $option => $default ) {
			add_option( $option, $default );
		}
	}

	public static function register_settings(): void {
		foreach ( [ 'f152_cookie_log_enabled' => 0, 'f152_cookie_log_capture_ip' => 1, 'f152_cookie_log_capture_user_agent' => 1 ] as $option => $default ) {
			register_setting( 'f152_cookie_log', $option, [
				'type' => 'boolean', 'default' => $default,
				'sanitize_callback' => [ Settings::class, 'sanitize_checkbox' ],
			] );
		}
		register_setting( 'f152_cookie_log', 'f152_cookie_log_retention_days', [
			'type' => 'integer', 'default' => 365,
			'sanitize_callback' => [ __CLASS__, 'sanitize_retention_days' ],
		] );
	}

	public static function sanitize_retention_days( $value ): int {
		if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/D', (string) $value ) ) {
			return 365;
		}
		$days = (int) $value;
		return in_array( $days, self::RETENTION_DAYS, true ) ? $days : 365;
	}

	public static function is_enabled(): bool {
		return (bool) get_option( 'f152_enabled', 1 ) && (bool) get_option( 'f152_cookie_log_enabled', 0 );
	}

	public static function handle_enabled_updated( $old_value, $new_value ): void {
		if ( (bool) $old_value !== (bool) $new_value && class_exists( Assets::class ) ) {
			Assets::schedule_page_cache_purge();
		}
	}

	public static function handle_option_added( $option, $value ): void {
		if ( 'f152_cookie_log_enabled' === $option && $value && class_exists( Assets::class ) ) {
			Assets::schedule_page_cache_purge();
		}
	}

	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'f152_cookie_consent_log';
	}

	public static function table_exists(): bool {
		global $wpdb;
		$table = self::get_table_name();
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	public static function create_table(): bool {
		global $wpdb;
		$table = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id CHAR(36) NOT NULL,
			created_at_utc DATETIME NOT NULL,
			action VARCHAR(32) NOT NULL,
			source VARCHAR(32) NOT NULL,
			necessary TINYINT(1) NOT NULL DEFAULT 1,
			analytics TINYINT(1) NOT NULL DEFAULT 0,
			marketing TINYINT(1) NOT NULL DEFAULT 0,
			categories_json TEXT NULL,
			page_url TEXT NULL,
			ip_address VARCHAR(45) NULL,
			user_agent VARCHAR(512) NULL,
			policy_version VARCHAR(50) NULL,
			policy_url VARCHAR(500) NULL,
			language VARCHAR(20) NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_id (event_id),
			KEY created_at_utc (created_at_utc),
			KEY action (action),
			KEY source (source)
		) {$charset_collate};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$previous = $wpdb->suppress_errors( true );
		try {
			dbDelta( $sql );
			$ready = self::table_exists();
		} finally {
			$wpdb->suppress_errors( $previous );
		}
		if ( $ready ) {
			update_option( 'f152_cookie_consent_log_db_version', self::DB_VERSION, false );
		}
		return $ready;
	}

	public static function maybe_upgrade(): bool {
		self::ensure_cron();
		if ( self::DB_VERSION === get_option( 'f152_cookie_consent_log_db_version', '' ) && self::table_exists() ) {
			return true;
		}
		self::ensure_defaults();
		return self::create_table();
	}

	public static function ensure_cron(): void {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	public static function clear_cron(): void {
		wp_clear_scheduled_hook( self::CLEANUP_HOOK );
	}

	public static function register_routes(): void {
		register_rest_route( 'f152/v1', '/cookie-consent-log', [
			'methods' => [ 'POST' ],
			'callback' => [ __CLASS__, 'handle_request' ],
			'permission_callback' => '__return_true',
		] );
	}

	 
	public static function handle_request( \WP_REST_Request $request ) {
		$response = new \WP_REST_Response( null, 204 );
		if ( ! self::is_enabled() ) {
			return $response;
		}
		if ( (int) $request->get_header( 'content_length' ) > self::MAX_BODY_BYTES
			|| strlen( $request->get_body() ) > self::MAX_BODY_BYTES
			|| ! $request->is_json_content_type() || ! self::is_same_origin( $request ) ) {
			return $response;
		}
		$data = $request->get_json_params();
		if ( ! is_array( $data ) ) {
			return $response;
		}
		$action = $data['action'] ?? null;
		$source = $data['source'] ?? null;
		$event_id = $data['event_id'] ?? null;
		$analytics = $data['analytics'] ?? null;
		$marketing = $data['marketing'] ?? null;
		if ( ! is_string( $action ) || ! isset( self::ACTION_SOURCES[ $action ] )
			|| ! is_string( $source ) || ! in_array( $source, self::ACTION_SOURCES[ $action ], true )
			|| ! is_string( $event_id ) || ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $event_id )
			|| ! is_bool( $analytics ) || ! is_bool( $marketing ) ) {
			return $response;
		}
		if ( ( 'accept_all' === $action && ( ! $analytics || ! $marketing ) )
			|| ( 'reject_optional' === $action && ( $analytics || $marketing ) )
			|| ( 'grant_category' === $action && ! $analytics && ! $marketing ) ) {
			return $response;
		}
		$page_url = self::normalize_page_url( $data['page_url'] ?? null );
		$language_context = apply_filters( 'f152/multilingual/cookie_log_context', [], $data );
		if ( ! is_array( $language_context ) ) { $language_context = []; }
		$record_language = isset( $language_context['language'] ) && is_string( $language_context['language'] ) ? substr( sanitize_key( $language_context['language'] ), 0, 20 ) : null;
		$record_policy = isset( $language_context['policy_url'] ) && is_string( $language_context['policy_url'] )
			? esc_url_raw( $language_context['policy_url'] ) : \F152\Helpers::document_url( 'f152_link_policy_cookie' );
		$ua = self::get_user_agent();
		if ( null === $page_url || self::is_bot( $ua ) ) {
			return $response;
		}
		$ip = self::get_remote_ip( $request );
		if ( ! self::rate_limit_allowed( $ip, $ua ) ) {
			return $response;
		}
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			$wpdb->insert( self::get_table_name(), [
				'event_id' => strtolower( $event_id ),
				'created_at_utc' => current_time( 'mysql', true ),
				'action' => $action,
				'source' => $source,
				'necessary' => 1,
				'analytics' => (int) $analytics,
				'marketing' => (int) $marketing,
				'categories_json' => wp_json_encode( [ 'necessary' => true, 'analytics' => $analytics, 'marketing' => $marketing ] ),
				'page_url' => $page_url,
				'ip_address' => get_option( 'f152_cookie_log_capture_ip', 1 ) && '' !== $ip ? $ip : null,
				'user_agent' => get_option( 'f152_cookie_log_capture_user_agent', 1 ) ? $ua : null,
				'policy_version' => self::limit_text( API::get_policy_version(), 50 ),
				'policy_url' => self::limit_text( $record_policy, 500 ),
				'language' => $record_language,
			], [ '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ] );
			 
		} finally {
			$wpdb->suppress_errors( $previous );
		}
		return $response;
	}

	private static function origin( string $url ): ?string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] )
			|| isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return null;
		}
		$scheme = strtolower( $parts['scheme'] );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return null;
		}
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		return $scheme . '://' . strtolower( $parts['host'] ) . ':' . $port;
	}

	private static function is_same_origin( \WP_REST_Request $request ): bool {
		$home_origin = self::origin( home_url( '/' ) );
		if ( null === $home_origin || 'cross-site' === strtolower( (string) $request->get_header( 'sec_fetch_site' ) ) ) {
			return false;
		}
		$has_origin = false;
		foreach ( [ 'origin', 'referer' ] as $header ) {
			$value = (string) $request->get_header( $header );
			if ( '' === $value ) {
				continue;
			}
			if ( self::origin( $value ) !== $home_origin ) {
				return false;
			}
			$has_origin = true;
		}
		return $has_origin;
	}

	private static function normalize_page_url( $url ): ?string {
		if ( ! is_string( $url ) || strlen( $url ) > 2048 || preg_match( '/[\x00-\x20\x7f\\\\]/', $url )
			|| self::origin( $url ) !== self::origin( home_url( '/' ) ) ) {
			return null;
		}
		 
		$clean = preg_split( '/[?#]/', $url, 2 )[0];
		return esc_url_raw( $clean );
	}

	private static function limit_text( string $value, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}

	private static function get_user_agent(): string {
		$raw = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';
		return self::limit_text( $raw, 512 );
	}

	private static function is_bot( string $ua ): bool {
		 
		return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|bingpreview|ahrefs|semrush|dotbot|petalbot|mj12|facebookexternalhit|headless|phantom|selenium|puppeteer|playwright|wget|curl|python-requests|yandex(?:images|video|media|metrika|webmaster)/i', $ua );
	}

	private static function get_remote_ip( \WP_REST_Request $request ): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';
		$remote = trim( $remote );
		$candidates = [ $remote ];
		 
		if ( apply_filters( 'f152_cookie_log_trust_proxy_headers', false, $remote ) ) {
			$forwarded = explode( ',', (string) $request->get_header( 'x_forwarded_for' ) );
			$candidates = [ $request->get_header( 'cf_connecting_ip' ), $forwarded[0], $request->get_header( 'x_real_ip' ), $remote ];
		}
		foreach ( $candidates as $candidate ) {
			if ( is_string( $candidate ) && filter_var( trim( $candidate ), FILTER_VALIDATE_IP ) ) {
				return trim( $candidate );
			}
		}
		return '';
	}

	private static function rate_limit_allowed( string $ip, string $ua ): bool {
		$window = 5 * MINUTE_IN_SECONDS;
		$limit = 30;
		$fingerprint = hash_hmac( 'sha256', $ip . '|' . $ua, wp_salt( 'nonce' ) );
		if ( wp_using_ext_object_cache() ) {
			$key = 'rl:' . $fingerprint;
			if ( wp_cache_add( $key, 1, 'f152_cookie_log', $window ) ) {
				return true;
			}
			$count = wp_cache_incr( $key, 1, 'f152_cookie_log' );
			return false !== $count && $count <= $limit;
		}
		 
		$bucket = get_transient( 'f152_cookie_log_rl_bucket' );
		$bucket = is_array( $bucket ) ? $bucket : [];
		$now = time();
		foreach ( $bucket as $key => $entry ) {
			if ( ! is_array( $entry ) || ( $entry['exp'] ?? 0 ) <= $now ) {
				unset( $bucket[ $key ] );
			}
		}
		if ( isset( $bucket[ $fingerprint ] ) ) {
			if ( $bucket[ $fingerprint ]['count'] >= $limit ) {
				return false;
			}
			$bucket[ $fingerprint ]['count']++;
		} else {
			if ( count( $bucket ) >= 2000 ) {
				return false;
			}
			$bucket[ $fingerprint ] = [ 'count' => 1, 'exp' => $now + $window ];
		}
		set_transient( 'f152_cookie_log_rl_bucket', $bucket, $window );
		return true;
	}

	public static function browser_name( string $ua ): string {
		if ( '' === $ua ) {
			return '';
		}
		foreach ( [ 'YaBrowser' => 'Yandex Browser', 'OPR/' => 'Opera', 'Opera' => 'Opera', 'Edg' => 'Edge', 'Firefox' => 'Firefox', 'FxiOS' => 'Firefox', 'Chrome' => 'Chrome', 'CriOS' => 'Chrome', 'Safari' => 'Safari' ] as $token => $name ) {
			if ( false !== stripos( $ua, $token ) ) {
				return $name;
			}
		}
		return 'Other';
	}

	public static function local_datetime( string $utc ): string {
		$dt = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc, new \DateTimeZone( 'UTC' ) );
		return false === $dt ? '' : wp_date( 'Y-m-d H:i:s', $dt->getTimestamp(), wp_timezone() );
	}

	public static function get_logs( int $page = 1, int $per_page = 50 ): array {
		global $wpdb;
		$table = esc_sql( self::get_table_name() );
		$per_page = max( 1, min( 100, $per_page ) );
		$offset = ( max( 1, $page ) - 1 ) * $per_page;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by get_table_name() and escaped above; %i is unavailable on supported WP 6.0-6.1.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public static function get_total_count(): int {
		global $wpdb;
		$table = esc_sql( self::get_table_name() );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted internal table identifier; keep compatibility with WP 6.0-6.1.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
	}

	public static function get_table_size(): ?int {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			$row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( self::get_table_name() ) ), ARRAY_A );
			return is_array( $row ) && isset( $row['Data_length'], $row['Index_length'] ) ? (int) $row['Data_length'] + (int) $row['Index_length'] : null;
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	public static function get_export_max_id(): int {
		global $wpdb;
		$table = esc_sql( self::get_table_name() );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted internal table identifier; keep compatibility with WP 6.0-6.1.
		return (int) $wpdb->get_var( "SELECT MAX(id) FROM `{$table}`" );
	}

	public static function get_export_batch( int $after_id, int $max_id ): array {
		global $wpdb;
		$table = esc_sql( self::get_table_name() );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped; values still use placeholders.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id > %d AND id <= %d ORDER BY id ASC LIMIT 1000", $after_id, $max_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	 
	public static function cleanup_cutoff_utc( string $date ): ?string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $date ) ) {
			return null;
		}
		$dt = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $dt || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
			return null;
		}
		return $dt->modify( '+1 day' )->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	public static function delete_through_date( string $date ) {
		global $wpdb;
		$until = self::cleanup_cutoff_utc( $date );
		if ( null === $until || ! self::table_exists() ) {
			return false;
		}
		$table = esc_sql( self::get_table_name() );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped; cutoff uses a placeholder.
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE created_at_utc < %s", $until ) );
		return false === $result ? false : (int) $result;
	}

	public static function do_cleanup(): void {
		global $wpdb;
		$days = self::sanitize_retention_days( get_option( 'f152_cookie_log_retention_days', 365 ) );
		 
		if ( 0 === $days || ! self::table_exists() ) {
			return;
		}
		$table = esc_sql( self::get_table_name() );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		for ( $batch = 0; $batch < self::CLEANUP_MAX_BATCHES; $batch++ ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped; dynamic values use placeholders.
			$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE created_at_utc < %s ORDER BY created_at_utc ASC LIMIT %d", $cutoff, self::CLEANUP_BATCH ) );
			if ( false === $deleted || $deleted < self::CLEANUP_BATCH ) {
				break;
			}
		}
		 
		if ( self::CLEANUP_MAX_BATCHES === $batch ) {
			wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, self::CLEANUP_HOOK );
		}
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery
