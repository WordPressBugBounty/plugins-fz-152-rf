<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery
final class PolicySnapshots {
	private const DB_VERSION = '1';

	public static function init(): void {
		add_action( 'pre_post_update', [ __CLASS__, 'capture_before_update' ], 10, 2 );
		add_action( 'update_option_f152_policy_version', [ __CLASS__, 'capture_version_mark' ], 20, 2 );
		add_action( 'admin_init', [ __CLASS__, 'maybe_seed_current' ], 5 );
	}

	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'f152_policy_snapshots';
	}

	public static function maybe_upgrade(): void {
		if ( get_option( 'f152_policy_snapshots_db_version', '' ) === self::DB_VERSION ) {
			return;
		}
		self::create_table();
	}

	public static function maybe_seed_current(): void {
		if ( get_option( 'f152_policy_snapshots_seeded_v1', false ) ) {
			return;
		}
		if ( self::capture_current_all( 'baseline' ) > 0 ) {
			update_option( 'f152_policy_snapshots_seeded_v1', 1, false );
		}
	}

	public static function table_exists(): bool {
		global $wpdb;
		$table = self::get_table_name();
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	public static function create_table(): void {
		global $wpdb;
		$table = self::get_table_name();
		$charset = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			document VARCHAR(40) NOT NULL,
			page_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			page_title VARCHAR(255) DEFAULT NULL,
			policy_version VARCHAR(50) DEFAULT NULL,
			source VARCHAR(40) NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			content_hash CHAR(64) NOT NULL,
			content LONGTEXT NOT NULL,
			PRIMARY KEY (id),
			KEY idx_document (document),
			KEY idx_page (page_id),
			KEY idx_created (created_at),
			KEY idx_hash (content_hash)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'f152_policy_snapshots_db_version', self::DB_VERSION, false );
	}

	public static function capture_before_update( int $post_id, array $data ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$document = self::document_for_page( $post_id );
		if ( '' === $document ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return;
		}
		$new_content = isset( $data['post_content'] ) ? (string) $data['post_content'] : (string) $post->post_content;
		if ( wp_unslash( $new_content ) === (string) $post->post_content ) {
			return;
		}
		self::capture( $document, $post_id, (string) $post->post_content, 'before_change' );
	}

	public static function capture_version_mark( $old_value, $new_value ): void {
		$new_version = sanitize_text_field( (string) $new_value );
		if ( '' === $new_version || (string) $old_value === (string) $new_value ) {
			return;
		}
		self::capture_current_all( 'version_mark', $new_version );
	}

	public static function capture_current_all( string $source = 'manual', ?string $version = null ): int {
		$count = 0;
		foreach ( self::tracked_pages() as $document => $page_id ) {
			$post = get_post( $page_id );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
				continue;
			}
			if ( self::capture( $document, $page_id, (string) $post->post_content, $source, $version ) ) {
				$count++;
			}
		}
		return $count;
	}

	public static function get_snapshots( int $limit = 100 ): array {
		global $wpdb;
		if ( ! self::table_exists() ) {
			return [];
		}
		$limit = max( 1, min( 500, $limit ) );
		$table = esc_sql( self::get_table_name() );
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix; limit uses a placeholder.
			$wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY created_at DESC, id DESC LIMIT %d", $limit ),
			ARRAY_A
		);
	}

	private static function capture( string $document, int $page_id, string $content, string $source, ?string $version = null ): bool {
		global $wpdb;
		if ( ! self::table_exists() ) {
			self::create_table();
		}
		$document = sanitize_key( $document );
		if ( '' === $document || $page_id <= 0 || '' === trim( $content ) ) {
			return false;
		}
		$version = null === $version && class_exists( '\\F152\\API' ) ? \F152\API::get_policy_version() : sanitize_text_field( (string) $version );
		if ( trim( $version ) === '' ) {
			$version = class_exists( '\\F152\\API' ) ? \F152\API::get_policy_version() : '1.0';
		}
		$hash = hash( 'sha256', $content );
		$table = esc_sql( self::get_table_name() );
		$last = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix; document and page ID use placeholders.
			$wpdb->prepare( "SELECT content_hash, policy_version FROM `{$table}` WHERE document = %s AND page_id = %d ORDER BY id DESC LIMIT 1", $document, $page_id ),
			ARRAY_A
		);
		if ( is_array( $last ) && hash_equals( (string) ( $last['content_hash'] ?? '' ), $hash ) && (string) ( $last['policy_version'] ?? '' ) === $version ) {
			return false;
		}
		$post = get_post( $page_id );
		$result = $wpdb->insert(
			self::get_table_name(),
			[
				'created_at'     => current_time( 'mysql' ),
				'document'       => $document,
				'page_id'        => $page_id,
				'page_title'     => $post instanceof \WP_Post ? sanitize_text_field( (string) $post->post_title ) : '',
				'policy_version' => $version,
				'source'         => sanitize_key( $source ),
				'user_id'        => get_current_user_id(),
				'content_hash'   => $hash,
				'content'        => $content,
			],
			[ '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);
		return false !== $result;
	}

	private static function document_for_page( int $page_id ): string {
		foreach ( self::tracked_pages() as $document => $tracked_id ) {
			if ( $page_id === $tracked_id ) {
				return $document;
			}
		}
		return '';
	}

	private static function tracked_pages(): array {
		$config = [
			'policy_pd'         => 'f152_link_policy_pd',
			'consent_pd'        => 'f152_link_consent_pd',
			'policy_cookie'     => 'f152_link_policy_cookie',
			'consent_marketing' => 'f152_link_consent_marketing',
		];
		$generated = get_option( 'f152_generated_page_ids', [] );
		$generated = is_array( $generated ) ? $generated : [];
		$result = [];
		foreach ( $config as $document => $option ) {
			$page_id = isset( $generated[ $document ] ) ? (int) $generated[ $document ] : 0;
			if ( $page_id <= 0 ) {
				$url = trim( (string) get_option( $option, '' ) );
				if ( '' !== $url ) {
					$page_id = (int) url_to_postid( $url );
				}
			}
			if ( $page_id > 0 ) {
				$result[ $document ] = $page_id;
			}
		}
		return $result;
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery
