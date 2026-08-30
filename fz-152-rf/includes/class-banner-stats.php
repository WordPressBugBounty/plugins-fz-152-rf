<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
final class BannerStats {

	private const DB_VERSION = '1';

	private const REST_NAMESPACE = 'f152/v1';
	private const REST_ROUTE     = '/banner-stats';

	private const ALLOWED_ACTIONS = [ 'show', 'accept', 'reject', 'custom' ];

	private const BOT_SIGNATURES = [
		'bot', 'crawl', 'crawler', 'spider', 'slurp', 'googlebot', 'bingbot',
		'bingpreview', 'yandex', 'baidu', 'duckduckbot', 'ahrefs', 'semrush',
		'dotbot', 'petalbot', 'mj12', 'facebookexternalhit', 'twitterbot',
		'linkedinbot', 'telegrambot', 'whatsapp', 'headless', 'phantom',
		'selenium', 'puppeteer', 'playwright', 'wget', 'curl', 'python-requests',
	];

	private const CLEANUP_HOOK    = 'f152_banner_stats_cleanup';
	private const CLEANUP_BATCH   = 500;

	public static function init() : void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		add_action( self::CLEANUP_HOOK, [ __CLASS__, 'do_cleanup' ] );

		if ( is_admin() && function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			add_action( 'wp_dashboard_setup', [ __CLASS__, 'register_dashboard_widget' ] );
		}
	}

	public static function get_table_name() : string {
		global $wpdb;
		return $wpdb->prefix . 'f152_banner_stats';
	}

	public static function table_exists() : bool {
		global $wpdb;
		$table_name = self::get_table_name();
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
	}

	public static function get_db_version() : string {
		return self::DB_VERSION;
	}

	public static function create_table() : void {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- dbDelta() requires a raw SQL string.
		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_hash CHAR(64) NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'pending',
			shown_at_utc DATETIME NOT NULL,
			decided_at_utc DATETIME NULL DEFAULT NULL,
			updated_at_utc DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY session_hash (session_hash),
			KEY status_shown (status, shown_at_utc)
		) {$charset_collate};";
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'f152_banner_stats_db_version', self::DB_VERSION );
	}

	public static function maybe_upgrade() : void {
		$current_version = get_option( 'f152_banner_stats_db_version', '' );

		if ( $current_version === self::DB_VERSION && self::table_exists() ) {
			self::ensure_salt();
			self::ensure_cron();
			return;
		}

		self::create_table();
		self::ensure_salt();
		self::ensure_cron();

		update_option( 'f152_banner_stats_db_version', self::DB_VERSION );
	}

	public static function ensure_salt() : void {
		$salt = get_option( 'f152_banner_stats_salt', '' );
		if ( is_string( $salt ) && $salt !== '' ) {
			return;
		}

		$new_salt = function_exists( 'wp_generate_password' )
			? wp_generate_password( 64, true, true )
			: bin2hex( random_bytes( 32 ) );

		update_option( 'f152_banner_stats_salt', $new_salt, false );
	}

	private static function get_salt() : string {
		$salt = get_option( 'f152_banner_stats_salt', '' );
		if ( ! is_string( $salt ) || $salt === '' ) {
			self::ensure_salt();
			$salt = get_option( 'f152_banner_stats_salt', '' );
		}
		return is_string( $salt ) ? $salt : '';
	}

	public static function ensure_cron() : void {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	public static function clear_cron() : void {
		wp_clear_scheduled_hook( self::CLEANUP_HOOK );
	}

	public static function register_routes() : void {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			[
				'methods'             => [ 'POST' ],
				'callback'            => [ __CLASS__, 'handle_request' ],
				'permission_callback' => '__return_true',
				'args'                => [],
			]
		);
	}

	public static function handle_request( \WP_REST_Request $request ) {
		static $processed = false;
		if ( $processed ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$content_length = (int) $request->get_header( 'content_length' );
		if ( $content_length > 0 && $content_length > 8192 ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$action = is_string( $request->get_param( 'action' ) ) ? $request->get_param( 'action' ) : '';
		if ( ! in_array( $action, self::ALLOWED_ACTIONS, true ) ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$session_token = is_string( $request->get_param( 'session_token' ) ) ? $request->get_param( 'session_token' ) : '';
		if ( ! self::is_valid_session_token( $session_token ) ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		if ( ! self::is_same_origin() ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$ua = self::get_user_agent();
		if ( self::is_bot( $ua ) ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$stats_salt = self::get_salt();
		if ( $stats_salt === '' ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$fingerprint = self::build_fingerprint( $stats_salt );

		if ( ! self::rate_limit_allowed( $fingerprint ) ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		if ( ! self::table_exists() ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		$processed = true;

		$session_hash = hash_hmac( 'sha256', $session_token, $stats_salt );

		$previous_show_errors = self::suppress_db_errors();

		if ( $action === 'show' ) {
			self::record_show( $session_hash );
		} else {
			self::record_decision( $session_hash, $action );
		}

		self::restore_db_errors( $previous_show_errors );

		return rest_ensure_response( [ 'success' => true ] );
	}

	private static function is_valid_session_token( string $token ) : bool {
		$length = strlen( $token );
		if ( $length < 32 || $length > 128 ) {
			return false;
		}
		return preg_match( '/^[0-9a-fA-F\-]+$/', $token ) === 1;
	}

	private static function suppress_db_errors() : bool {
		global $wpdb;
		$previous = (bool) $wpdb->show_errors;
		$wpdb->hide_errors();
		return $previous;
	}

	private static function restore_db_errors( bool $previous ) : void {
		global $wpdb;
		if ( $previous ) {
			$wpdb->show_errors();
		} else {
			$wpdb->hide_errors();
		}
	}

	private static function record_show( string $session_hash ) : void {
		global $wpdb;

		$table_name = esc_sql( self::get_table_name() );
		$now_utc    = current_time( 'mysql', true );

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped with esc_sql(); INSERT IGNORE is atomic.
				"INSERT IGNORE INTO `{$table_name}` (session_hash, status, shown_at_utc, updated_at_utc) VALUES (%s, 'pending', %s, %s)",
				$session_hash,
				$now_utc,
				$now_utc
			)
		);
	}

	private static function record_decision( string $session_hash, string $action ) : void {
		global $wpdb;

		$table_name = esc_sql( self::get_table_name() );
		$now_utc    = current_time( 'mysql', true );

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped with esc_sql(); ON DUPLICATE KEY UPDATE is atomic.
				"INSERT INTO `{$table_name}` (session_hash, status, shown_at_utc, decided_at_utc, updated_at_utc)
				VALUES (%s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
					status = IF(status = 'pending', VALUES(status), status),
					decided_at_utc = IF(status = 'pending', VALUES(decided_at_utc), decided_at_utc),
					shown_at_utc = shown_at_utc,
					updated_at_utc = VALUES(updated_at_utc)",
				$session_hash,
				$action,
				$now_utc,
				$now_utc,
				$now_utc
			)
		);
	}

	private static function get_user_agent() : string {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	public static function is_bot( string $ua ) : bool {
		if ( $ua === '' ) {
			return true;
		}
		$ua_lower = strtolower( $ua );
		foreach ( self::BOT_SIGNATURES as $signature ) {
			if ( strpos( $ua_lower, $signature ) !== false ) {
				return true;
			}
		}
		return false;
	}

	private static function is_same_origin() : bool {
		$home_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		$origin = '';
		if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
			$origin = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
		}

		if ( $origin !== '' ) {
			$origin_host = (string) wp_parse_url( $origin, PHP_URL_HOST );
			if ( $origin_host !== '' && strtolower( $origin_host ) !== strtolower( $home_host ) ) {
				return false;
			}
		}

		$referer = '';
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}

		if ( $referer !== '' ) {
			$referer_host = (string) wp_parse_url( $referer, PHP_URL_HOST );
			if ( $referer_host !== '' && strtolower( $referer_host ) !== strtolower( $home_host ) ) {
				return false;
			}
		}

		return true;
	}

	private static function build_fingerprint( string $stats_salt ) : string {
		$ip = self::get_remote_ip();
		$ua = self::get_user_agent();

		return hash_hmac( 'sha256', $ip . '|' . $ua, $stats_salt );
	}

	private static function get_remote_ip() : string {
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}

	private static function rate_limit_allowed( string $fingerprint ) : bool {
		$window      = 5 * MINUTE_IN_SECONDS;
		$max_per_ip  = 30;

		if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
			$cache_key = 'f152_bs_rl:' . substr( $fingerprint, 0, 32 );
			$count     = (int) wp_cache_get( $cache_key, 'f152_banner_stats' );
			if ( $count >= $max_per_ip ) {
				return false;
			}
			wp_cache_incr( $cache_key, 1, 'f152_banner_stats', 1 );
			if ( $count === 0 ) {
				wp_cache_set( $cache_key, 1, 'f152_banner_stats', $window );
			}
			return true;
		}

		$bucket_key = 'f152_bs_rl_bucket';
		$bucket     = get_transient( $bucket_key );
		if ( ! is_array( $bucket ) ) {
			$bucket = [];
		}

		$now      = time();
		$max_keys = 2000;

		foreach ( $bucket as $fp => $data ) {
			if ( ! is_array( $data ) || ! isset( $data['exp'] ) || $data['exp'] < $now ) {
				unset( $bucket[ $fp ] );
			}
		}

		if ( isset( $bucket[ $fingerprint ] ) ) {
			$bucket[ $fingerprint ]['count']++;
			if ( $bucket[ $fingerprint ]['count'] > $max_per_ip ) {
				set_transient( $bucket_key, $bucket, $window );
				return false;
			}
			$bucket[ $fingerprint ]['exp'] = $now + $window;
		} else {
			if ( count( $bucket ) >= $max_keys ) {
				uasort( $bucket, function ( $a, $b ) {
					return ( $a['exp'] ?? 0 ) <=> ( $b['exp'] ?? 0 );
				} );
				$bucket = array_slice( $bucket, -1 * (int) floor( $max_keys * 0.75 ), null, true );
			}
			$bucket[ $fingerprint ] = [
				'count' => 1,
				'exp'   => $now + $window,
			];
		}

		set_transient( $bucket_key, $bucket, $window );
		return true;
	}

	public static function get_ignore_after_seconds() : int {
		$value = (int) apply_filters( 'f152_banner_stats_ignore_after', 30 * MINUTE_IN_SECONDS );
		return max( 60, min( 30 * DAY_IN_SECONDS, $value ) );
	}

	public static function get_retention_days() : int {
		$value = (int) apply_filters( 'f152_banner_stats_retention_days', 365 );
		return max( 7, min( 3650, $value ) );
	}

	public static function get_counts() : array {
		$zeroes = [
			'accept'  => 0,
			'reject'  => 0,
			'custom'  => 0,
			'ignored' => 0,
		];

		if ( ! self::table_exists() ) {
			return $zeroes;
		}

		global $wpdb;
		$table_name    = esc_sql( self::get_table_name() );
		$ignore_after  = self::get_ignore_after_seconds();

		$previous = self::suppress_db_errors();

		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is generated internally and escaped with esc_sql(); query contains no external values.
			"SELECT status, COUNT(*) AS cnt FROM `{$table_name}` WHERE status IN ('accept','reject','custom') GROUP BY status"
		);

		$counts = $zeroes;
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( isset( $row->status, $row->cnt ) && array_key_exists( $row->status, $counts ) ) {
					$counts[ $row->status ] = (int) $row->cnt;
				}
			}
		}

		$counts['ignored'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped with esc_sql().
				"SELECT COUNT(*) FROM `{$table_name}`
				WHERE status = 'pending'
				AND shown_at_utc <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d SECOND)",
				$ignore_after
			)
		);

		self::restore_db_errors( $previous );

		return $counts;
	}

	public static function do_cleanup() : void {
		if ( ! self::table_exists() ) {
			return;
		}

		global $wpdb;
		$table_name   = esc_sql( self::get_table_name() );
		$retention  = self::get_retention_days();
		$ignore_after = self::get_ignore_after_seconds();

		$previous = self::suppress_db_errors();

		$guard = 50;
		while ( $guard-- > 0 ) {
			$deleted = (int) $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally and escaped with esc_sql().
					"DELETE FROM `{$table_name}`
					WHERE shown_at_utc <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)
					AND (status != 'pending' OR shown_at_utc <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d SECOND))
					LIMIT %d",
					$retention,
					$ignore_after,
					self::CLEANUP_BATCH
				)
			);
			if ( $deleted < self::CLEANUP_BATCH ) {
				break;
			}
		}

		self::restore_db_errors( $previous );
	}

	public static function truncate() : bool {
		if ( ! self::table_exists() ) {
			return false;
		}
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );

		$previous = self::suppress_db_errors();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name internal/prefixed.
		$result = $wpdb->query( "TRUNCATE TABLE `{$table_name}`" );
		self::restore_db_errors( $previous );

		return $result !== false;
	}

	public static function drop_table() : void {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name internal/prefixed.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
	}

	public static function register_dashboard_widget() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'f152_banner_stats',
			__( 'Статистика cookie-баннера ФЗ-152', 'fz-152-rf' ),
			[ __CLASS__, 'render_widget' ]
		);
	}

	public static function render_widget() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$counts = self::get_counts();
		$items  = [
			'accept'  => __( 'Приняли всё', 'fz-152-rf' ),
			'reject'  => __( 'Отклонили всё', 'fz-152-rf' ),
			'custom'  => __( 'Настроили выборочно', 'fz-152-rf' ),
			'ignored' => __( 'Без решения', 'fz-152-rf' ),
		];

		echo '<ul style="margin:0;">';
		foreach ( $items as $key => $label ) {
			printf(
				'<li style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>%s</span><strong>%d</strong></li>',
				esc_html( $label ),
				(int) ( $counts[ $key ] ?? 0 )
			);
		}
		echo '</ul>';

		echo '<p style="margin-top:10px;color:#666;">';
		echo esc_html__( 'Без решения — сеансы, где решение не принято в течение 30 минут.', 'fz-152-rf' );
		echo '</p>';
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery
