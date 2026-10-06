<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

 




final class FeatureRequest {
	private const AJAX_ACTION = 'f152_submit_feature_request';
	private const NONCE_ACTION = 'f152_feature_request';
	private const MAX_LENGTH = 4000;

	public static function init() : void {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ __CLASS__, 'handle_ajax' ] );
	}

	public static function ajax_action() : string {
		return self::AJAX_ACTION;
	}

	public static function nonce() : string {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	public static function handle_ajax() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостаточно прав.', 'fz-152-rf' ) ], 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$message_raw = filter_input( INPUT_POST, 'message', FILTER_UNSAFE_RAW );
		$message = is_string( $message_raw ) ? sanitize_textarea_field( wp_unslash( $message_raw ) ) : '';
		$message = trim( $message );

		if ( '' === $message ) {
			wp_send_json_error( [ 'message' => __( 'Напишите, какую функцию вы хотите предложить.', 'fz-152-rf' ) ], 400 );
		}

		if ( self::string_length( $message ) > self::MAX_LENGTH ) {
			$message = self::string_substr( $message, 0, self::MAX_LENGTH );
		}

		$payload = [
			'message'        => $message,
			'plugin_version' => defined( 'F152_VERSION' ) ? (string) F152_VERSION : '',
		];

		$endpoint = defined( 'F152_FEATURE_REQUEST_ENDPOINT' )
			? (string) F152_FEATURE_REQUEST_ENDPOINT
			: 'https://fz152rf.ru/wp-json/ls/v1/f152-feature-request';

		$response = wp_remote_post(
			$endpoint,
			[
				'timeout'     => 8,
				'redirection' => 0,
				'headers'     => [
					'Content-Type' => 'application/json; charset=utf-8',
					'Accept'       => 'application/json',
					'User-Agent'   => 'FZ-152-RF/' . ( defined( 'F152_VERSION' ) ? F152_VERSION : 'unknown' ),
				],
				'body'        => wp_json_encode( $payload ),
				'data_format' => 'body',
			]
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => __( 'Не удалось отправить предложение. Попробуйте ещё раз позже.', 'fz-152-rf' ) ], 502 );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) || empty( $body['ok'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Не удалось отправить предложение. Попробуйте ещё раз позже.', 'fz-152-rf' ) ], 502 );
		}

		wp_send_json_success( [ 'message' => __( 'Спасибо! Предложение отправлено разработчику.', 'fz-152-rf' ) ] );
	}

	private static function string_length( string $value ) : int {
		return function_exists( 'mb_strlen' ) ? (int) mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	private static function string_substr( string $value, int $start, int $length ) : string {
		return function_exists( 'mb_substr' ) ? (string) mb_substr( $value, $start, $length, 'UTF-8' ) : substr( $value, $start, $length );
	}
}
