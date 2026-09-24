<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DeveloperAPI {

	public const API_VERSION = '1.0';
	private const REST_NAMESPACE = 'f152/v1';
	private const OPTION_TOKENS = 'f152_api_tokens_v1';
	private const TOKEN_PREFIX = 'f152v1_';
	private const MAX_TOKENS = 10;

	private static $authorized_token = null;

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_action( 'wp_ajax_f152_api_create_token', [ __CLASS__, 'ajax_create_token' ] );
		add_action( 'wp_ajax_f152_api_revoke_token', [ __CLASS__, 'ajax_revoke_token' ] );
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/status',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_status' ],
				'permission_callback' => [ __CLASS__, 'authorize_status' ],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/consents',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_consents' ],
				'permission_callback' => [ __CLASS__, 'authorize_consents' ],
				'args'                => [
					'limit' => [
						'default'           => 50,
						'sanitize_callback' => 'absint',
					],
					'after_id' => [
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
					'before_id' => [
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
					'order' => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					],
					'source_type' => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					],
					'source_plugin' => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'source_uid' => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'created_after' => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'created_before' => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	public static function authorize_status( \WP_REST_Request $request ) {
		return self::authorize_request( $request, 'status.read' );
	}

	public static function authorize_consents( \WP_REST_Request $request ) {
		return self::authorize_request( $request, 'consents.read' );
	}

	private static function authorize_request( \WP_REST_Request $request, string $required_scope ) {
		self::$authorized_token = null;
		if ( ! self::is_https_site() ) {
			return new \WP_Error(
				'f152_api_https_required',
				__( 'Удалённый API FZ-152 доступен только по HTTPS.', 'fz-152-rf' ),
				[ 'status' => 403 ]
			);
		}
		if ( self::is_cross_origin_browser_request( $request ) ) {
			return new \WP_Error(
				'f152_api_cross_origin_denied',
				__( 'Удалённый API FZ-152 предназначен только для server-to-server запросов.', 'fz-152-rf' ),
				[ 'status' => 403 ]
			);
		}
		$raw = self::extract_request_token( $request );
		if ( '' === $raw ) {
			return new \WP_Error(
				'f152_api_token_missing',
				__( 'Не указан API-токен FZ-152.', 'fz-152-rf' ),
				[ 'status' => 401 ]
			);
		}

		$token_id = self::token_id_from_plaintext( $raw );
		if ( '' === $token_id ) {
			return self::invalid_token_error();
		}

		$tokens = self::get_tokens();
		if ( empty( $tokens[ $token_id ] ) || ! is_array( $tokens[ $token_id ] ) ) {
			return self::invalid_token_error();
		}

		$record = $tokens[ $token_id ];
		$stored_hash = isset( $record['hash'] ) && is_string( $record['hash'] ) ? $record['hash'] : '';
		$actual_hash = self::hash_token( $raw );
		if ( '' === $stored_hash || '' === $actual_hash || ! hash_equals( $stored_hash, $actual_hash ) ) {
			return self::invalid_token_error();
		}

		$scopes = isset( $record['scopes'] ) && is_array( $record['scopes'] ) ? array_map( [ __CLASS__, 'sanitize_scope' ], $record['scopes'] ) : [];
		if ( ! in_array( $required_scope, $scopes, true ) ) {
			return new \WP_Error(
				'f152_api_scope_denied',
				__( 'У этого API-токена нет необходимого разрешения.', 'fz-152-rf' ),
				[ 'status' => 403 ]
			);
		}

		$record['id'] = $token_id;
		self::$authorized_token = $record;

		return true;
	}

	private static function is_https_site(): bool {
		return 'https' === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_SCHEME ) );
	}

	private static function is_cross_origin_browser_request( \WP_REST_Request $request ): bool {
		return '' !== trim( (string) $request->get_header( 'origin' ) );
	}

	private static function invalid_token_error(): \WP_Error {
		return new \WP_Error(
			'f152_api_token_invalid',
			__( 'API-токен FZ-152 недействителен или отозван.', 'fz-152-rf' ),
			[ 'status' => 401 ]
		);
	}

	private static function extract_request_token( \WP_REST_Request $request ): string {
		$authorization = trim( (string) $request->get_header( 'authorization' ) );
		if ( preg_match( '/^Bearer\s+(.+)$/i', $authorization, $matches ) ) {
			return trim( (string) $matches[1] );
		}

		$fallback = trim( (string) $request->get_header( 'x-f152-token' ) );
		return $fallback;
	}

	public static function rest_status( \WP_REST_Request $request ): \WP_REST_Response {
		unset( $request );
		$response = new \WP_REST_Response(
			[
				'api_version'    => self::API_VERSION,
				'plugin_version' => defined( 'F152_VERSION' ) ? (string) F152_VERSION : '',
				'site_url'       => home_url( '/' ),
				'policy_version' => class_exists( '\F152\API' ) ? API::get_policy_version() : '',
				'policy_url'     => class_exists( '\F152\API' ) ? API::get_policy_url() : '',
				'capabilities'   => class_exists( '\F152\API' ) ? API::get_capabilities() : [],
				'server_time'    => current_time( 'mysql' ),
				'read_only'      => true,
			]
		);
		return self::secure_response( $response );
	}

	public static function rest_consents( \WP_REST_Request $request ) {
		if ( ! class_exists( '\F152\ConsentLog' ) || ! ConsentLog::table_exists() ) {
			$response = new \WP_REST_Response(
				[
					'items'          => [],
					'has_more'       => false,
					'next_after_id'  => null,
					'next_before_id' => null,
				]
			);
			return self::secure_response( $response );
		}

		$limit = max( 1, min( 100, (int) $request->get_param( 'limit' ) ) );
		$after_id = absint( $request->get_param( 'after_id' ) );
		$before_id = absint( $request->get_param( 'before_id' ) );
		$order_raw = sanitize_key( (string) $request->get_param( 'order' ) );
		$order = in_array( $order_raw, [ 'asc', 'desc' ], true )
			? $order_raw
			: ( $after_id > 0 ? 'asc' : 'desc' );

		$created_after = self::normalize_api_datetime( (string) $request->get_param( 'created_after' ), false );
		if ( is_wp_error( $created_after ) ) {
			return $created_after;
		}
		$created_before = self::normalize_api_datetime( (string) $request->get_param( 'created_before' ), true );
		if ( is_wp_error( $created_before ) ) {
			return $created_before;
		}

		$include_technical = self::current_token_has_scope( 'consents.read_technical' );
		$batch = ConsentLog::get_readonly_api_batch(
			[
				'limit'          => $limit,
				'after_id'       => $after_id,
				'before_id'      => $before_id,
				'order'          => $order,
				'source_type'    => sanitize_key( (string) $request->get_param( 'source_type' ) ),
				'source_plugin'  => self::cut( sanitize_text_field( (string) $request->get_param( 'source_plugin' ) ), 100 ),
				'source_uid'     => self::cut( sanitize_text_field( (string) $request->get_param( 'source_uid' ) ), 191 ),
				'created_after'  => is_string( $created_after ) ? $created_after : '',
				'created_before' => is_string( $created_before ) ? $created_before : '',
				'include_technical' => $include_technical,
			]
		);

		$response = new \WP_REST_Response( $batch );
		return self::secure_response( $response );
	}

	private static function secure_response( \WP_REST_Response $response ): \WP_REST_Response {
		$response->header( 'Cache-Control', 'no-store, private, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'X-Robots-Tag', 'noindex, nofollow' );
		$response->header( 'Vary', 'Authorization, X-F152-Token' );
		return $response;
	}

	private static function normalize_api_datetime( string $raw, bool $end_of_day ) {
		$raw = trim( sanitize_text_field( $raw ) );
		if ( '' === $raw ) {
			return '';
		}

		$timezone = wp_timezone();
		$formats = [ 'Y-m-d H:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s', 'Y-m-d' ];
		foreach ( $formats as $format ) {
			$dt = \DateTimeImmutable::createFromFormat( '!' . $format, $raw, $timezone );
			$errors = \DateTimeImmutable::getLastErrors();
			if ( false === $dt || ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) ) {
				continue;
			}
			if ( 'Y-m-d' === $format && $end_of_day ) {
				$dt = $dt->setTime( 23, 59, 59 );
			}
			return $dt->setTimezone( $timezone )->format( 'Y-m-d H:i:s' );
		}

		return new \WP_Error(
			'f152_api_invalid_datetime',
			__( 'Неверный формат даты. Используйте YYYY-MM-DD, YYYY-MM-DD HH:MM:SS или RFC3339.', 'fz-152-rf' ),
			[ 'status' => 400 ]
		);
	}

	public static function ajax_create_token(): void {
		self::require_api_admin_ajax();
		check_ajax_referer( 'f152_developer_api_admin' );

		$name_raw = filter_input( INPUT_POST, 'name', FILTER_UNSAFE_RAW );
		$name = self::cut( sanitize_text_field( is_scalar( $name_raw ) ? wp_unslash( (string) $name_raw ) : '' ), 100 );
		if ( '' === $name ) {
			wp_send_json_error( [ 'message' => __( 'Укажите название токена.', 'fz-152-rf' ) ], 400 );
		}

		$tokens = self::get_tokens();
		if ( count( $tokens ) >= self::MAX_TOKENS ) {
			wp_send_json_error( [ 'message' => __( 'Достигнут лимит API-токенов. Отзовите ненужный токен.', 'fz-152-rf' ) ], 400 );
		}
		$read_consents_raw = filter_input( INPUT_POST, 'read_consents', FILTER_UNSAFE_RAW );
		$read_technical_raw = filter_input( INPUT_POST, 'read_technical', FILTER_UNSAFE_RAW );
		$read_consents = is_scalar( $read_consents_raw ) && '1' === sanitize_text_field( wp_unslash( (string) $read_consents_raw ) );
		$read_technical = $read_consents && is_scalar( $read_technical_raw ) && '1' === sanitize_text_field( wp_unslash( (string) $read_technical_raw ) );

		$created = self::create_token( $name, $read_consents, $read_technical );
		if ( is_wp_error( $created ) ) {
			wp_send_json_error( [ 'message' => $created->get_error_message() ], 500 );
		}

		wp_send_json_success(
			[
				'token'  => $created['token'],
				'id'     => $created['id'],
				'name'   => $created['name'],
				'scopes' => $created['scopes'],
				'created_at' => $created['created_at'],
			]
		);
	}

	public static function ajax_revoke_token(): void {
		self::require_api_admin_ajax();
		check_ajax_referer( 'f152_developer_api_admin' );

		$id_raw = filter_input( INPUT_POST, 'token_id', FILTER_UNSAFE_RAW );
		$id = strtolower( preg_replace( '/[^a-f0-9]/i', '', is_scalar( $id_raw ) ? sanitize_text_field( wp_unslash( (string) $id_raw ) ) : '' ) );
		if ( ! preg_match( '/^[a-f0-9]{16}$/', $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Неверный идентификатор токена.', 'fz-152-rf' ) ], 400 );
		}

		$tokens = self::get_tokens();
		if ( ! isset( $tokens[ $id ] ) ) {
			wp_send_json_success( [ 'revoked' => true ] );
		}

		unset( $tokens[ $id ] );
		self::save_tokens( $tokens );
		wp_send_json_success( [ 'revoked' => true ] );
	}

	private static function require_api_admin_ajax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостаточно прав.', 'fz-152-rf' ) ], 403 );
		}
	}

	private static function create_token( string $name, bool $read_consents, bool $read_technical ) {
		try {
			$id = bin2hex( random_bytes( 8 ) );
			$secret = bin2hex( random_bytes( 32 ) );
		} catch ( \Throwable $e ) {
			if ( ! function_exists( 'hash' ) ) {
				return new \WP_Error( 'f152_api_random_failed', __( 'Не удалось безопасно создать токен.', 'fz-152-rf' ) );
			}
			$id = substr( hash( 'sha256', wp_generate_password( 64, true, true ) . microtime( true ) ), 0, 16 );
			$secret = hash( 'sha256', wp_generate_password( 128, true, true ) . wp_salt( 'nonce' ) . microtime( true ) );
		}

		$plain = self::TOKEN_PREFIX . $id . '_' . $secret;
		$hash = self::hash_token( $plain );
		if ( '' === $hash ) {
			return new \WP_Error( 'f152_api_hash_failed', __( 'Не удалось безопасно сохранить токен.', 'fz-152-rf' ) );
		}

		$scopes = [ 'status.read' ];
		if ( $read_consents ) {
			$scopes[] = 'consents.read';
		}
		if ( $read_technical ) {
			$scopes[] = 'consents.read_technical';
		}

		$record = [
			'name'         => $name,
			'hash'         => $hash,
			'scopes'       => $scopes,
			'created_at'   => current_time( 'mysql' ),
			'created_by'   => get_current_user_id(),
		];
		$tokens = self::get_tokens();
		$tokens[ $id ] = $record;
		self::save_tokens( $tokens );

		return [
			'token'      => $plain,
			'id'         => $id,
			'name'       => $name,
			'scopes'     => $scopes,
			'created_at' => $record['created_at'],
		];
	}

	private static function hash_token( string $token ): string {
		if ( '' === $token || ! function_exists( 'hash_hmac' ) ) {
			return '';
		}
		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	private static function token_id_from_plaintext( string $token ): string {
		if ( ! preg_match( '/^' . preg_quote( self::TOKEN_PREFIX, '/' ) . '([a-f0-9]{16})_[a-f0-9]{64}$/', $token, $matches ) ) {
			return '';
		}
		return strtolower( (string) $matches[1] );
	}

	private static function get_tokens(): array {
		$stored = get_option( self::OPTION_TOKENS, [] );
		if ( ! is_array( $stored ) ) {
			return [];
		}

		$tokens = [];
		foreach ( $stored as $id => $record ) {
			$id = strtolower( preg_replace( '/[^a-f0-9]/i', '', (string) $id ) );
			if ( ! preg_match( '/^[a-f0-9]{16}$/', $id ) || ! is_array( $record ) ) {
				continue;
			}
			$hash = isset( $record['hash'] ) && is_string( $record['hash'] ) ? $record['hash'] : '';
			if ( ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
				continue;
			}
			$scopes = isset( $record['scopes'] ) && is_array( $record['scopes'] ) ? array_values( array_unique( array_filter( array_map( [ __CLASS__, 'sanitize_scope' ], $record['scopes'] ) ) ) ) : [];
			if ( ! in_array( 'status.read', $scopes, true ) ) {
				$scopes[] = 'status.read';
			}
			$tokens[ $id ] = [
				'name'         => self::cut( sanitize_text_field( (string) ( $record['name'] ?? '' ) ), 100 ),
				'hash'         => $hash,
				'scopes'       => $scopes,
				'created_at'   => sanitize_text_field( (string) ( $record['created_at'] ?? '' ) ),
				'created_by'   => absint( $record['created_by'] ?? 0 ),
			];
		}
		return $tokens;
	}

	private static function save_tokens( array $tokens ): void {
		if ( false === get_option( self::OPTION_TOKENS, false ) ) {
			add_option( self::OPTION_TOKENS, $tokens, '', false );
			return;
		}
		update_option( self::OPTION_TOKENS, $tokens, false );
	}

	private static function current_token_has_scope( string $scope ): bool {
		if ( ! is_array( self::$authorized_token ) ) {
			return false;
		}
		$scopes = isset( self::$authorized_token['scopes'] ) && is_array( self::$authorized_token['scopes'] ) ? self::$authorized_token['scopes'] : [];
		return in_array( self::sanitize_scope( $scope ), $scopes, true );
	}

	public static function admin_tokens(): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return [];
		}
		$out = [];
		foreach ( self::get_tokens() as $id => $record ) {
			$out[] = [
				'id'           => $id,
				'name'         => $record['name'],
				'scopes'       => $record['scopes'],
				'created_at'   => $record['created_at'],
			];
		}
		return $out;
	}

	public static function max_tokens(): int {
		return self::MAX_TOKENS;
	}

	private static function sanitize_scope( $scope ): string {
		$scope = strtolower( trim( is_scalar( $scope ) ? (string) $scope : '' ) );
		return preg_replace( '/[^a-z0-9_.-]/', '', $scope ) ?: '';
	}

	private static function cut( string $value, int $length ): string {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $length );
		}
		return substr( $value, 0, $length );
	}
}
