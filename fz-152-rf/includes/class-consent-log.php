<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
final class ConsentLog {

	private const DB_VERSION = '3';

	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'f152_consent_log';
	}

	public static function table_exists(): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		return ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );
	}

	public static function create_table(): void {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- dbDelta() requires a raw SQL string.
		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			source_type VARCHAR(20) NOT NULL,
			source_id BIGINT UNSIGNED NOT NULL,
			email VARCHAR(255) DEFAULT NULL,
			full_name VARCHAR(255) DEFAULT NULL,
			phone VARCHAR(50) DEFAULT NULL,
			user_id BIGINT UNSIGNED DEFAULT 0,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent VARCHAR(512) DEFAULT NULL,
			consent_text TEXT DEFAULT NULL,
			policy_version VARCHAR(50) DEFAULT NULL,
			policy_url VARCHAR(500) DEFAULT NULL,
			source_uid VARCHAR(191) DEFAULT NULL,
			source_plugin VARCHAR(100) DEFAULT NULL,
			source_label VARCHAR(255) DEFAULT NULL,
			page_url TEXT NULL,
			form_id VARCHAR(100) DEFAULT NULL,
			form_title VARCHAR(255) DEFAULT NULL,
			consent_hash VARCHAR(64) DEFAULT NULL,
			context LONGTEXT NULL,
			PRIMARY KEY (id),
			KEY idx_source (source_type, source_id),
			KEY idx_created_at (created_at),
			KEY idx_email (email),
			KEY idx_source_uid (source_uid),
			KEY idx_source_plugin (source_plugin),
			KEY idx_consent_hash (consent_hash)
		) {$charset_collate};";
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'f152_consent_log_db_version', self::DB_VERSION );
	}

	public static function maybe_create_table(): bool {
		$current_version = get_option( 'f152_consent_log_db_version', '' );

		if ( $current_version === self::DB_VERSION && self::table_exists() ) {
			return true;
		}

		self::create_table();

		self::ensure_phone_column();

		return self::table_exists();
	}

	private static function ensure_phone_column(): void {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return;
		}

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DESCRIBE is a safe schema probe; table name comes from $wpdb->prefix + literal, no user input, cannot be injected.
		$columns = $wpdb->get_results( "DESCRIBE `{$table_name}`", ARRAY_A );
		if ( ! is_array( $columns ) ) {
			return;
		}

		$has_phone = false;
		foreach ( $columns as $column ) {
			if ( isset( $column['Field'] ) && $column['Field'] === 'phone' ) {
				$has_phone = true;
				break;
			}
		}

		if ( $has_phone ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- intentional additive schema change; table name comes from $wpdb->prefix + literal, no user input, cannot be injected.
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER full_name" );
	}

	public static function log( array $data ): bool {
		global $wpdb;

		$source_type = isset( $data['source_type'] ) ? sanitize_text_field( (string) $data['source_type'] ) : '';

		if ( $source_type === '' ) {
			return false;
		}

		$raw_source_id = $data['source_id'] ?? 0;
		$source_id     = ( is_numeric( $raw_source_id ) && (int) $raw_source_id > 0 )
			? (int) $raw_source_id
			: 0;

		$source_uid = '';
		if ( isset( $data['source_uid'] ) && is_scalar( $data['source_uid'] ) && (string) $data['source_uid'] !== '' ) {
			$source_uid = (string) $data['source_uid'];
		} elseif ( is_string( $raw_source_id ) && $raw_source_id !== '' ) {
			$source_uid = $raw_source_id;
		} elseif ( isset( $data['form_id'] ) && is_scalar( $data['form_id'] ) && (string) $data['form_id'] !== '' ) {
			$source_uid = (string) $data['form_id'];
		} elseif ( isset( $data['consent_hash'] ) && is_scalar( $data['consent_hash'] ) && (string) $data['consent_hash'] !== '' ) {
			$source_uid = (string) $data['consent_hash'];
		}

		if ( $source_id <= 0 && $source_uid === '' ) {
			return false;
		}

		$table_name = self::get_table_name();

		$policy_version = isset( $data['policy_version'] ) && is_scalar( $data['policy_version'] )
			? trim( sanitize_text_field( (string) $data['policy_version'] ) )
			: '';
		if ( $policy_version === '' ) {
			if ( class_exists( '\\F152\\API' ) && method_exists( '\\F152\\API', 'get_policy_version' ) ) {
				$policy_version = \F152\API::get_policy_version();
			} else {
				$policy_version = trim( sanitize_text_field( (string) get_option( 'f152_policy_version', '1.0' ) ) );
				if ( $policy_version === '' ) {
					$policy_version = '1.0';
				}
			}
		}

		$insert_data = [
			'created_at'     => current_time( 'mysql' ),
			'source_type'    => $source_type,
			'source_id'      => $source_id,
			'email'          => sanitize_email( $data['email'] ?? '' ),
			'full_name'      => sanitize_text_field( $data['full_name'] ?? '' ),
			'phone'          => self::sanitize_phone( $data['phone'] ?? '' ),
			'user_id'        => (int) ( $data['user_id'] ?? 0 ),
			'ip_address'     => sanitize_text_field( $data['ip_address'] ?? '' ),
			'user_agent'     => sanitize_text_field( $data['user_agent'] ?? '' ),
			'consent_text'   => wp_kses_post( $data['consent_text'] ?? '' ),
			'policy_version' => $policy_version,
			'policy_url'     => esc_url_raw( $data['policy_url'] ?? '' ),
			'source_uid'     => $source_uid !== '' ? mb_substr( sanitize_text_field( $source_uid ), 0, 191 ) : null,
			'source_plugin'  => isset( $data['source_plugin'] ) && is_scalar( $data['source_plugin'] )
				? mb_substr( sanitize_text_field( (string) $data['source_plugin'] ), 0, 100 ) : null,
			'source_label'   => isset( $data['source_label'] ) && is_scalar( $data['source_label'] )
				? mb_substr( sanitize_text_field( (string) $data['source_label'] ), 0, 255 ) : null,
			'page_url'       => isset( $data['page_url'] ) && is_scalar( $data['page_url'] )
				? esc_url_raw( (string) $data['page_url'] ) : null,
			'form_id'        => isset( $data['form_id'] ) && is_scalar( $data['form_id'] )
				? mb_substr( sanitize_text_field( (string) $data['form_id'] ), 0, 100 ) : null,
			'form_title'     => isset( $data['form_title'] ) && is_scalar( $data['form_title'] )
				? mb_substr( sanitize_text_field( (string) $data['form_title'] ), 0, 255 ) : null,
			'consent_hash'   => isset( $data['consent_hash'] ) && is_scalar( $data['consent_hash'] )
				? mb_substr( preg_replace( '/[^0-9a-fA-F]/', '', (string) $data['consent_hash'] ), 0, 64 ) : null,
			'context'        => self::prepare_context_json( $data['context'] ?? null ),
		];

		if ( empty( $insert_data['email'] ) ) {
			$insert_data['email'] = null;
		}

		if ( empty( $insert_data['phone'] ) ) {
			$insert_data['phone'] = null;
		}

		$format = [
			'%s', // created_at
			'%s', // source_type
			'%d', // source_id
			'%s', // email
			'%s', // full_name
			'%s', // phone
			'%d', // user_id
			'%s', // ip_address
			'%s', // user_agent
			'%s', // consent_text
			'%s', // policy_version
			'%s', // policy_url
			'%s', // source_uid
			'%s', // source_plugin
			'%s', // source_label
			'%s', // page_url
			'%s', // form_id
			'%s', // form_title
			'%s', // consent_hash
			'%s', // context
		];

		$result = $wpdb->insert( $table_name, $insert_data, $format );

		return $result !== false;
	}

	private static function sanitize_phone( $phone ): string {
		if ( ! is_scalar( $phone ) ) {
			return '';
		}

		$value = sanitize_text_field( wp_strip_all_tags( trim( (string) $phone ) ) );

		if ( $value === '' ) {
			return '';
		}

		$clean = preg_replace( '/[^0-9+\s()\-]/', '', $value );

		$has_plus = substr( $value, 0, 1 ) === '+';
		$clean    = preg_replace( '/\+/', '', $clean );
		$clean    = ( $has_plus ? '+' : '' ) . $clean;

		$clean = function_exists( 'mb_substr' ) ? mb_substr( trim( $clean ), 0, 50 ) : substr( trim( $clean ), 0, 50 );

		$digits = preg_replace( '/\D/', '', $clean );
		if ( ! is_string( $digits ) || strlen( $digits ) < 5 ) {
			return '';
		}

		return trim( $clean );
	}

	private static function prepare_context_json( $context ): ?string {
		if ( is_array( $context ) ) {
			$json = wp_json_encode( $context, defined( 'JSON_UNESCAPED_UNICODE' ) ? JSON_UNESCAPED_UNICODE : 0 );

			return ( is_string( $json ) && $json !== 'null' ) ? $json : null;
		}

		if ( is_string( $context ) && $context !== '' ) {
			$value = sanitize_textarea_field( $context );

			return $value !== '' ? $value : null;
		}

		return null;
	}

	private static function can_manage_admin_log(): bool {
		return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
	}

	public static function get_logs( int $page = 1, int $per_page = 50 ): array {
		if ( ! self::can_manage_admin_log() ) { return []; }
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [];
		}

		$table_name = esc_sql( self::get_table_name() );
		$page       = max( 1, $page );
		$per_page   = max( 1, $per_page );
		$offset     = ( $page - 1 ) * $per_page;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot use placeholders and is escaped with esc_sql().
				"SELECT * FROM `{$table_name}` ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	public static function get_total_count(): int {
		if ( ! self::can_manage_admin_log() ) { return 0; }
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$table_name = esc_sql( self::get_table_name() );

		return (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot use placeholders and is escaped with esc_sql().
			"SELECT COUNT(*) FROM `{$table_name}`"
		);
	}

	public static function get_all_for_export(): array {
		if ( ! self::can_manage_admin_log() ) { return []; }
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [];
		}

		$table_name = esc_sql( self::get_table_name() );

		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot use placeholders and is escaped with esc_sql().
			"SELECT * FROM `{$table_name}` ORDER BY created_at DESC",
			ARRAY_A
		);
	}

	public static function get_readonly_api_batch( array $args = [] ): array {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [
				'items'          => [],
				'has_more'       => false,
				'next_after_id'  => null,
				'next_before_id' => null,
			];
		}

		$limit = max( 1, min( 100, (int) ( $args['limit'] ?? 50 ) ) );
		$after_id = max( 0, (int) ( $args['after_id'] ?? 0 ) );
		$before_id = max( 0, (int) ( $args['before_id'] ?? 0 ) );
		$order = 'desc' === strtolower( (string) ( $args['order'] ?? '' ) ) ? 'DESC' : 'ASC';
		$include_technical = ! empty( $args['include_technical'] );
		$source_type = isset( $args['source_type'] ) ? sanitize_key( (string) $args['source_type'] ) : '';
		$source_plugin = isset( $args['source_plugin'] ) ? sanitize_text_field( (string) $args['source_plugin'] ) : '';
		$source_uid = isset( $args['source_uid'] ) ? sanitize_text_field( (string) $args['source_uid'] ) : '';
		$created_after = isset( $args['created_after'] ) ? sanitize_text_field( (string) $args['created_after'] ) : '';
		$created_before = isset( $args['created_before'] ) ? sanitize_text_field( (string) $args['created_before'] ) : '';

		$table_name = esc_sql( self::get_table_name() );
		$limit_plus_one = $limit + 1;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- The table identifier is generated only from $wpdb->prefix plus the fixed f152_consent_log suffix. WordPress 6.0 does not support the %i identifier placeholder; all request-derived values below are passed through $wpdb->prepare() placeholders.

		if ( $include_technical ) {
			if ( 'DESC' === $order ) {
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT id, created_at, source_type, source_id, source_uid, source_plugin, source_label, page_url, form_id, form_title, email, full_name, phone, consent_text, policy_version, policy_url, consent_hash, context, user_id, ip_address, user_agent FROM `' . $table_name . '` WHERE (%d = 0 OR id > %d) AND (%d = 0 OR id < %d) AND (%s = \'\' OR source_type = %s) AND (%s = \'\' OR source_plugin = %s) AND (%s = \'\' OR source_uid = %s) AND (%s = \'\' OR created_at >= %s) AND (%s = \'\' OR created_at <= %s) ORDER BY id DESC LIMIT %d',
						$after_id,
						$after_id,
						$before_id,
						$before_id,
						$source_type,
						$source_type,
						$source_plugin,
						$source_plugin,
						$source_uid,
						$source_uid,
						$created_after,
						$created_after,
						$created_before,
						$created_before,
						$limit_plus_one
					),
					ARRAY_A
				);
			} else {
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT id, created_at, source_type, source_id, source_uid, source_plugin, source_label, page_url, form_id, form_title, email, full_name, phone, consent_text, policy_version, policy_url, consent_hash, context, user_id, ip_address, user_agent FROM `' . $table_name . '` WHERE (%d = 0 OR id > %d) AND (%d = 0 OR id < %d) AND (%s = \'\' OR source_type = %s) AND (%s = \'\' OR source_plugin = %s) AND (%s = \'\' OR source_uid = %s) AND (%s = \'\' OR created_at >= %s) AND (%s = \'\' OR created_at <= %s) ORDER BY id ASC LIMIT %d',
						$after_id,
						$after_id,
						$before_id,
						$before_id,
						$source_type,
						$source_type,
						$source_plugin,
						$source_plugin,
						$source_uid,
						$source_uid,
						$created_after,
						$created_after,
						$created_before,
						$created_before,
						$limit_plus_one
					),
					ARRAY_A
				);
			}
		} elseif ( 'DESC' === $order ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, created_at, source_type, source_id, source_uid, source_plugin, source_label, page_url, form_id, form_title, email, full_name, phone, consent_text, policy_version, policy_url, consent_hash, context FROM `' . $table_name . '` WHERE (%d = 0 OR id > %d) AND (%d = 0 OR id < %d) AND (%s = \'\' OR source_type = %s) AND (%s = \'\' OR source_plugin = %s) AND (%s = \'\' OR source_uid = %s) AND (%s = \'\' OR created_at >= %s) AND (%s = \'\' OR created_at <= %s) ORDER BY id DESC LIMIT %d',
					$after_id,
					$after_id,
					$before_id,
					$before_id,
					$source_type,
					$source_type,
					$source_plugin,
					$source_plugin,
					$source_uid,
					$source_uid,
					$created_after,
					$created_after,
					$created_before,
					$created_before,
					$limit_plus_one
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, created_at, source_type, source_id, source_uid, source_plugin, source_label, page_url, form_id, form_title, email, full_name, phone, consent_text, policy_version, policy_url, consent_hash, context FROM `' . $table_name . '` WHERE (%d = 0 OR id > %d) AND (%d = 0 OR id < %d) AND (%s = \'\' OR source_type = %s) AND (%s = \'\' OR source_plugin = %s) AND (%s = \'\' OR source_uid = %s) AND (%s = \'\' OR created_at >= %s) AND (%s = \'\' OR created_at <= %s) ORDER BY id ASC LIMIT %d',
					$after_id,
					$after_id,
					$before_id,
					$before_id,
					$source_type,
					$source_type,
					$source_plugin,
					$source_plugin,
					$source_uid,
					$source_uid,
					$created_after,
					$created_after,
					$created_before,
					$created_before,
					$limit_plus_one
				),
				ARRAY_A
			);
		}
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$rows = is_array( $rows ) ? $rows : [];

		$has_more = count( $rows ) > $limit;
		if ( $has_more ) {
			$rows = array_slice( $rows, 0, $limit );
		}

		$items = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row['id'] = (int) ( $row['id'] ?? 0 );
			$row['source_id'] = (int) ( $row['source_id'] ?? 0 );
			$event_meta = self::api_event_meta( $row['context'] ?? '' );
			$row['consent_kind'] = $event_meta['consent_kind'];
			$row['decision'] = $event_meta['decision'];
			$row['required'] = $event_meta['required'];
			if ( $include_technical ) {
				$row['user_id'] = (int) ( $row['user_id'] ?? 0 );
				if ( isset( $row['context'] ) && is_string( $row['context'] ) && '' !== $row['context'] ) {
					$decoded = json_decode( $row['context'], true );
					if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
						$row['context'] = $decoded;
					}
				}
			} else {
				unset( $row['context'], $row['user_id'], $row['ip_address'], $row['user_agent'] );
			}
			$items[] = $row;
		}

		$last_id = ! empty( $items ) ? (int) ( $items[ count( $items ) - 1 ]['id'] ?? 0 ) : 0;

		return [
			'items'          => $items,
			'has_more'       => $has_more,
			'next_after_id'  => 'ASC' === $order && $last_id > 0 ? $last_id : null,
			'next_before_id' => 'DESC' === $order && $last_id > 0 ? $last_id : null,
		];
	}

	private static function api_event_meta( $context ): array {
		$decoded = [];
		if ( is_array( $context ) ) {
			$decoded = $context;
		} elseif ( is_string( $context ) && '' !== trim( $context ) ) {
			$value = json_decode( $context, true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $value ) ) {
				$decoded = $value;
			}
		}

		$kind = isset( $decoded['consent_kind'] ) && is_scalar( $decoded['consent_kind'] )
			? sanitize_key( (string) $decoded['consent_kind'] )
			: 'personal';
		if ( ! in_array( $kind, [ 'personal', 'marketing', 'custom' ], true ) ) {
			$kind = 'personal';
		}
		$decision = isset( $decoded['decision'] ) && is_scalar( $decoded['decision'] )
			? sanitize_key( (string) $decoded['decision'] )
			: 'accepted';
		$decision = 'declined' === $decision ? 'declined' : 'accepted';
		$required = 'custom' === $kind ? ! empty( $decoded['required'] ) : null;

		return [
			'consent_kind' => $kind,
			'decision'     => $decision,
			'required'     => $required,
		];
	}

	public static function count_through_date( string $date ): int {
		if ( ! self::can_manage_admin_log() ) { return 0; }
		global $wpdb;

		$until = self::normalize_cleanup_date( $date );
		if ( null === $until || ! self::table_exists() ) {
			return 0;
		}

		$table_name = esc_sql( self::get_table_name() );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table identifier is generated only from $wpdb->prefix plus the fixed f152_consent_log suffix. WordPress 6.0 does not support the %i identifier placeholder; the date value remains parameterized with $wpdb->prepare().
		$count = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM `{$table_name}` WHERE created_at <= %s", $until )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $count;
	}

	public static function delete_through_date( string $date ) {
		if ( ! self::can_manage_admin_log() ) { return false; }
		global $wpdb;

		$until = self::normalize_cleanup_date( $date );
		if ( null === $until || ! self::table_exists() ) {
			return false;
		}

		$table_name = esc_sql( self::get_table_name() );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table identifier is generated only from $wpdb->prefix plus the fixed f152_consent_log suffix. WordPress 6.0 does not support the %i identifier placeholder; the date value remains parameterized with $wpdb->prepare().
		$result = $wpdb->query(
			$wpdb->prepare( "DELETE FROM `{$table_name}` WHERE created_at <= %s", $until )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false === $result ? false : (int) $result;
	}

	private static function normalize_cleanup_date( string $date ): ?string {
		$date = sanitize_text_field( trim( $date ) );
		$dt = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $dt || ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) ) {
			return null;
		}
		return $dt->setTime( 23, 59, 59 )->format( 'Y-m-d H:i:s' );
	}

	public static function truncate_table(): bool {
		if ( ! self::can_manage_admin_log() ) { return false; }
		global $wpdb;

		if ( ! self::table_exists() ) {
			return false;
		}

		$table_name = esc_sql( self::get_table_name() );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table identifier is generated only from $wpdb->prefix plus the fixed f152_consent_log suffix. WordPress 6.0 does not support the %i identifier placeholder; this statement contains no user-controlled values.
		$result = $wpdb->query(
			"TRUNCATE TABLE `{$table_name}`"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result !== false;
	}

	public static function drop_table(): void {
		global $wpdb;

		$table_name = esc_sql( self::get_table_name() );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table identifier is generated only from $wpdb->prefix plus the fixed f152_consent_log suffix. WordPress 6.0 does not support the %i identifier placeholder; this uninstall statement contains no user-controlled values.
		$wpdb->query(
			"DROP TABLE IF EXISTS `{$table_name}`"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery