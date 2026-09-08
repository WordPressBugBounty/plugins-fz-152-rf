<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ServiceScanner {

	private const TRANSIENT_TTL          = 10 * MINUTE_IN_SECONDS;
	private const RESPONSE_LIMIT         = 5 * MB_IN_BYTES;
	private const REQUEST_TIMEOUT        = 15;
	private const TRANSIENT_KEY          = 'f152_service_scan_site_v24';
	private const BROWSER_TRANSIENT_KEY  = 'f152_service_scan_browser_home_v2';
	private const BROWSER_TRANSIENT_TTL  = 60;
	private const LOCK_OPTION            = 'f152_service_scan_lock_v1';
	private const LOCK_TTL               = 5 * MINUTE_IN_SECONDS;

	private static $lock_token = '';

	public static function get_issues(): array {
		$result = self::get_scan_result();
		if ( empty( $result['ok'] ) || empty( $result['services'] ) || ! is_array( $result['services'] ) ) {
			return [];
		}

		$definitions = self::definitions();
		$statuses    = self::get_control_statuses();
		$issues      = [];

		foreach ( $result['services'] as $service_id => $detection ) {
			if ( empty( $detection['found_external'] ) || ! isset( $definitions[ $service_id ] ) ) {
				continue;
			}

			$definition = $definitions[ $service_id ];
			$catalog_definition = class_exists( '\\F152\\ServiceCatalog' ) ? ServiceCatalog::get( (string) $service_id ) : [];
			if ( 'data_transfer' === sanitize_key( (string) ( $catalog_definition['category'] ?? '' ) ) ) {
				continue;
			}
			$control    = isset( $statuses[ $service_id ] ) && is_array( $statuses[ $service_id ] )
				? $statuses[ $service_id ]
				: [];

			$configured = ! empty( $control['configured'] );
			$active     = ! empty( $control['active'] );
			$available  = ! empty( $control['available'] );
			$ids        = isset( $detection['ids'] ) && is_array( $detection['ids'] )
				? array_values( array_filter( array_map( 'strval', $detection['ids'] ) ) )
				: [];
			$locations  = isset( $detection['locations'] ) && is_array( $detection['locations'] )
				? $detection['locations']
				: [];

			if ( 'gtm' === $service_id && ! $configured ) {
				continue;
			}

			$is_embed = 'embed' === sanitize_key( (string) ( $control['control_type'] ?? '' ) );
			if ( $is_embed ) {
				$mode = isset( $control['mode'] ) ? sanitize_key( (string) $control['mode'] ) : 'always';
				$embed_type = sanitize_key( (string) ( $control['embed_type'] ?? ( $definition['category'] ?? 'content' ) ) );
				$found_in_rendered_html = false;
				foreach ( $locations as $location ) {
					if ( is_array( $location ) && 'rendered' === ( $location['kind'] ?? '' ) ) {
						$found_in_rendered_html = true;
						break;
					}
				}

				if ( $active && 'always' === $mode ) {
					continue;
				}

				if ( $active && in_array( $mode, [ 'disable_on_reject', 'require_accept' ], true ) && ! $found_in_rendered_html ) {
					continue;
				}

				if ( $active && in_array( $mode, [ 'disable_on_reject', 'require_accept' ], true ) && $found_in_rendered_html ) {
					if ( 'video' === $embed_type ) {
						$message = __( 'FZ-152 должен скрывать это видео до нужного согласия, но на странице всё ещё найден его обычный код. Сначала очистите кэш сайта и запустите проверку ещё раз. Если сообщение останется — пришлите его в поддержку: видео вставляется способом, который плагин не успевает перехватить.', 'fz-152-rf' );
					} elseif ( 'map' === $embed_type || 'maps' === $embed_type ) {
						$message = __( 'FZ-152 должен скрывать эту карту до нужного согласия, но на странице всё ещё найден её обычный код. Сначала очистите кэш сайта и запустите проверку ещё раз. Если сообщение останется — пришлите его в поддержку: карта вставляется способом, который плагин не успевает перехватить.', 'fz-152-rf' );
					} else {
						$message = __( 'FZ-152 должен блокировать это содержимое до нужного согласия, но на странице всё ещё найден обычный внешний код. Сначала очистите кэш сайта и запустите проверку ещё раз. Если сообщение останется — пришлите его в поддержку.', 'fz-152-rf' );
					}
				} elseif ( $active ) {
					if ( 'video' === $embed_type ) {
						$message = sprintf(
							/* translators: %s: external video service name. */
							__( '%s обнаружено на сайте и сейчас загружается всегда. Во вкладке «Сервисы и трекеры» можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' ),
							(string) ( $definition['label'] ?? $service_id )
						);
					} elseif ( 'map' === $embed_type || 'maps' === $embed_type ) {
						$message = sprintf(
							/* translators: %s: external map service name. */
							__( '%s обнаружены на сайте и сейчас загружаются всегда. Во вкладке «Сервисы и трекеры» можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' ),
							(string) ( $definition['label'] ?? $service_id )
						);
					} else {
						$message = sprintf(
							/* translators: %s: external content service name. */
							__( '%s обнаружено на сайте и сейчас загружается всегда. Во вкладке «Сервисы и трекеры» можно выбрать режим блокировки до согласия', 'fz-152-rf' ),
							(string) ( $definition['label'] ?? $service_id )
						);
					}
				} elseif ( $available && $configured ) {
					$message = __( 'FZ-152 умеет управлять этим сервисом, но управление сейчас выключено. Откройте «Сервисы и трекеры», проверьте настройку сервиса и, если это функция Pro, состояние лицензии.', 'fz-152-rf' );
				} elseif ( $available ) {
					$message = __( 'Мы нашли этот сервис на сайте, но FZ-152 сейчас не управляет его загрузкой. Откройте «Сервисы и трекеры» и выберите подходящий режим.', 'fz-152-rf' );
				} else {
					$message = __( 'Мы нашли этот сервис на сайте, но эта версия FZ-152 не управляет им автоматически. Проверьте его подключение вручную.', 'fz-152-rf' );
				}
			} elseif ( $configured && $active ) {
				$message = __( 'На сайте есть ещё один код этого сервиса помимо того, который добавляет FZ-152. Из-за этого сервис может запускаться в обход настроек cookie. Удалите старую вставку из темы, GTM или другого плагина и оставьте только подключение через FZ-152.', 'fz-152-rf' );
			} elseif ( 'gtm' === $service_id ) {
				$message = __( 'В FZ-152 настроен Google Tag Manager, но управление им сейчас не активно. Откройте «Сервисы и трекеры» и проверьте настройки GTM и состояние лицензии Pro.', 'fz-152-rf' );
			} elseif ( 'yandex_metrika' === $service_id ) {
				$message = __( 'Мы нашли Яндекс.Метрику, но она подключена не через FZ-152. Откройте «Сервисы и трекеры», укажите номер счётчика и сохраните настройки. Затем удалите прежний код Метрики из темы, GTM или другого плагина, чтобы счётчик не загружался дважды.', 'fz-152-rf' );
			} elseif ( $available && $configured && ! $active ) {
				$message = __( 'Мы нашли отдельный код этого сервиса, но управление через FZ-152 сейчас не работает. Откройте «Сервисы и трекеры», проверьте настройку сервиса и состояние Pro. После включения управления удалите старую отдельную вставку.', 'fz-152-rf' );
			} elseif ( $available ) {
				$message = __( 'Мы нашли этот сервис, но он подключён отдельно от FZ-152. Откройте «Сервисы и трекеры» и настройте управление этим сервисом. Если такого пункта там нет — подключение нужно проверить вручную.', 'fz-152-rf' );
			} else {
				$message = __( 'Мы нашли этот сервис на сайте, но FZ-152 не умеет управлять им автоматически. Если сервис должен запускаться только после согласия посетителя, настройте это в самом сервисе или плагине, который его подключает.', 'fz-152-rf' );
			}

			$issues[] = [
				'id'        => $service_id,
				'label'     => (string) $definition['label'],
				'message'   => $message,
				'ids'       => $ids,
				'locations' => $locations,
			];
		}

		return $issues;
	}

	public static function get_scan_snapshot(): array {
		return self::get_scan_result();
	}

	public static function clear_cache(): void {
		self::clear_server_cache();
		delete_transient( self::BROWSER_TRANSIENT_KEY );
	}

	public static function clear_server_cache(): void {
		delete_transient( self::TRANSIENT_KEY );

		foreach ( [
			'f152_service_scan_home_v2',
			'f152_service_scan_home_v3',
			'f152_service_scan_site_v4',
			'f152_service_scan_site_v5',
			'f152_service_scan_site_v6',
			'f152_service_scan_site_v7',
			'f152_service_scan_site_v8',
			'f152_service_scan_site_v9',
			'f152_service_scan_site_v10',
			'f152_service_scan_site_v11',
			'f152_service_scan_site_v12',
			'f152_service_scan_site_v13',
			'f152_service_scan_site_v14',
			'f152_service_scan_site_v15',
			'f152_service_scan_site_v16',
			'f152_service_scan_site_v17',
			'f152_service_scan_site_v18',
			'f152_service_scan_site_v19',
			'f152_service_scan_site_v20',
			'f152_service_scan_site_v21',
			'f152_service_scan_site_v22',
			'f152_service_scan_site_v23',
		] as $legacy_key ) {
			delete_transient( $legacy_key );
		}
	}

	public static function get_control_snapshot(): array {
		return self::get_control_statuses();
	}

	private static function get_scan_result(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		if ( ! self::may_start_scan() ) {
			return self::empty_scan_result( 'suppressed_context' );
		}

		if ( ! self::acquire_lock() ) {
			$cached = get_transient( self::TRANSIENT_KEY );
			return is_array( $cached ) ? $cached : self::empty_scan_result( 'scan_busy' );
		}

		try {
			$browser_snapshot = get_transient( self::BROWSER_TRANSIENT_KEY );

			$result = self::scan_site();
			$result = self::merge_browser_home_snapshot(
				$result,
				is_array( $browser_snapshot ) ? $browser_snapshot : []
			);
			set_transient( self::TRANSIENT_KEY, $result, self::TRANSIENT_TTL );

			return $result;
		} finally {
			self::release_lock();
		}
	}

	private static function may_start_scan(): bool {
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false;
		}

		$internal_header = isset( $_SERVER['HTTP_X_F152_SCANNER'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_X_F152_SCANNER'] ) )
			: '';
		if ( '1' === $internal_header ) {
			return false;
		}

		return is_admin() && current_user_can( 'manage_options' );
	}

	private static function empty_scan_result( string $reason ): array {
		return [
			'ok'         => false,
			'url'        => home_url( '/' ),
			'scanned_at' => 0,
			'services'   => self::empty_service_results( self::definitions() ),
			'scan_meta'  => [
				'suppressed' => true,
				'reason'     => sanitize_key( $reason ),
			],
		];
	}

	private static function acquire_lock(): bool {
		$now = time();
		$existing = get_option( self::LOCK_OPTION, [] );
		if ( is_array( $existing ) && (int) ( $existing['expires'] ?? 0 ) > $now ) {
			return false;
		}

		if ( false !== $existing && [] !== $existing ) {
			delete_option( self::LOCK_OPTION );
		}

		$token = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'f152-', true );
		$value = [
			'token'   => $token,
			'expires' => $now + self::LOCK_TTL,
		];

		if ( ! add_option( self::LOCK_OPTION, $value, '', false ) ) {
			return false;
		}

		self::$lock_token = (string) $token;
		return true;
	}

	private static function release_lock(): void {
		if ( '' === self::$lock_token ) {
			return;
		}

		$current = get_option( self::LOCK_OPTION, [] );
		if ( is_array( $current ) && hash_equals( self::$lock_token, (string) ( $current['token'] ?? '' ) ) ) {
			delete_option( self::LOCK_OPTION );
		}
		self::$lock_token = '';
	}

	public static function store_browser_home_html( string $html ): array {
		if ( strlen( $html ) > self::RESPONSE_LIMIT ) {
			$html = substr( $html, 0, self::RESPONSE_LIMIT );
		}

		$definitions = self::definitions();
		$services = self::empty_service_results( $definitions );
		if ( '' !== trim( $html ) ) {
			self::merge_source_into_services(
				$services,
				$html,
				$definitions,
				[
					'title' => __( 'Главная страница', 'fz-152-rf' ),
					'url'   => home_url( '/' ),
					'kind'  => 'rendered',
				]
			);
		}

		$snapshot = [
			'ok'         => '' !== trim( $html ),
			'scanned_at' => time(),
			'fingerprint'=> self::service_fingerprint( $services ),
			'services'   => $services,
		];
		$previous = get_transient( self::BROWSER_TRANSIENT_KEY );
		$previous_fingerprint = is_array( $previous ) ? (string) ( $previous['fingerprint'] ?? '' ) : '';

		set_transient( self::BROWSER_TRANSIENT_KEY, $snapshot, self::BROWSER_TRANSIENT_TTL );
		self::clear_server_cache();

		$labels = [];
		foreach ( $services as $service_id => $detection ) {
			if ( empty( $detection['found_external'] ) && empty( $detection['found_managed'] ) ) {
				continue;
			}
			$definition = ServiceCatalog::get( (string) $service_id );
			$labels[] = sanitize_text_field( (string) ( $definition['label'] ?? $service_id ) );
		}

		return [
			'changed' => $previous_fingerprint !== (string) $snapshot['fingerprint'],
			'labels'  => array_values( array_unique( array_filter( $labels ) ) ),
		];
	}

	private static function merge_browser_home_snapshot( array $result, array $browser ): array {
		if ( empty( $browser['ok'] ) || empty( $browser['services'] ) || ! is_array( $browser['services'] ) ) {
			return $result;
		}

		if ( empty( $result['services'] ) || ! is_array( $result['services'] ) ) {
			$result['services'] = self::empty_service_results( self::definitions() );
		}

		foreach ( $browser['services'] as $service_id => $detection ) {
			if ( ! isset( $result['services'][ $service_id ] ) || ! is_array( $detection ) ) {
				continue;
			}
			if ( ! empty( $detection['found_external'] ) ) {
				$result['services'][ $service_id ]['found_external'] = true;
			}
			if ( ! empty( $detection['found_managed'] ) ) {
				$result['services'][ $service_id ]['found_managed'] = true;
			}
			$result['services'][ $service_id ]['ids'] = array_values( array_unique( array_merge(
				(array) ( $result['services'][ $service_id ]['ids'] ?? [] ),
				(array) ( $detection['ids'] ?? [] )
			) ) );
			foreach ( (array) ( $detection['locations'] ?? [] ) as $location ) {
				if ( is_array( $location ) ) {
					self::append_location( $result['services'][ $service_id ]['locations'], $location );
				}
			}
			foreach ( (array) ( $detection['managed_locations'] ?? [] ) as $location ) {
				if ( is_array( $location ) ) {
					self::append_location( $result['services'][ $service_id ]['managed_locations'], $location );
				}
			}
		}

		$result['ok'] = true;
		$result['scan_meta'] = isset( $result['scan_meta'] ) && is_array( $result['scan_meta'] ) ? $result['scan_meta'] : [];
		$result['scan_meta']['home_ok'] = true;
		$result['scan_meta']['browser_home_ok'] = true;
		$result['scan_meta']['browser_scanned_at'] = (int) ( $browser['scanned_at'] ?? 0 );
		$result['scan_meta']['home_error'] = [];
		return $result;
	}

	private static function service_fingerprint( array $services ): string {
		$compact = [];
		foreach ( $services as $service_id => $detection ) {
			if ( ! is_array( $detection ) ) {
				continue;
			}
			$external = ! empty( $detection['found_external'] );
			$managed  = ! empty( $detection['found_managed'] );
			if ( ! $external && ! $managed ) {
				continue;
			}
			$ids = array_values( array_unique( array_map( 'strval', (array) ( $detection['ids'] ?? [] ) ) ) );
			sort( $ids );
			$compact[ sanitize_key( (string) $service_id ) ] = [ $external ? 1 : 0, $managed ? 1 : 0, $ids ];
		}
		ksort( $compact );
		return md5( wp_json_encode( $compact ) ?: '' );
	}

	private static function scan_site(): array {
		$definitions = self::definitions();
		$services    = self::empty_service_results( $definitions );
		$scanned_any = false;
		$home_url    = home_url( '/' );
		$scan_meta   = [
			'home_ok'         => false,
			'home_error'      => [],
			'candidate_pages' => 0,
			'rendered_pages'  => 0,
			'failed_pages'    => 0,
			'failed_page_details' => [],
		];

		$home = self::fetch_url_html( $home_url );
		if ( ! empty( $home['ok'] ) ) {
			$scanned_any = true;
			$scan_meta['home_ok'] = true;
			self::merge_source_into_services(
				$services,
				(string) $home['body'],
				$definitions,
				[
					'title' => __( 'Главная страница', 'fz-152-rf' ),
					'url'   => $home_url,
					'kind'  => 'rendered',
				]
			);
		} else {
			$scan_meta['home_error'] = [
				'type'       => sanitize_key( (string) ( $home['error_type'] ?? '' ) ),
				'code'       => sanitize_text_field( (string) ( $home['error_code'] ?? '' ) ),
				'message'    => sanitize_text_field( (string) ( $home['error_message'] ?? '' ) ),
				'http_code'  => isset( $home['status_code'] ) ? (int) $home['status_code'] : 0,
			];
		}

		$page_ids = get_posts(
			[
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( is_array( $page_ids ) ) {
			$front_page_id = (int) get_option( 'page_on_front', 0 );
			foreach ( $page_ids as $page_id ) {
				$page_id = (int) $page_id;
				if ( $page_id <= 0 ) {
					continue;
				}

				if ( $scan_meta['home_ok'] && $front_page_id > 0 && $page_id === $front_page_id ) {
					continue;
				}

				$source = self::get_page_source( $page_id );
				if ( '' === $source ) {
					continue;
				}

				$candidate_definitions = self::get_detected_definitions( $source, $definitions );
				if ( empty( $candidate_definitions ) ) {
					continue;
				}
				$scan_meta['candidate_pages']++;

				$page_title = get_the_title( $page_id ) ?: sprintf(
					/* translators: %d: WordPress page ID. */
					__( 'Страница #%d', 'fz-152-rf' ),
					$page_id
				);
				$page_url = get_permalink( $page_id );
				if ( ! is_string( $page_url ) || '' === $page_url ) {
					$scan_meta['failed_pages']++;
					if ( count( $scan_meta['failed_page_details'] ) < 3 ) {
						$scan_meta['failed_page_details'][] = [
							'title' => sanitize_text_field( (string) $page_title ),
							'url' => '',
							'error_type' => 'permalink',
							'error_code' => '',
							'error_message' => 'WordPress did not return a page URL',
							'status_code' => 0,
						];
					}
					continue;
				}

				$rendered = self::fetch_url_html( $page_url );
				if ( empty( $rendered['ok'] ) ) {
					$scan_meta['failed_pages']++;
					if ( count( $scan_meta['failed_page_details'] ) < 3 ) {
						$scan_meta['failed_page_details'][] = [
							'title' => sanitize_text_field( (string) $page_title ),
							'url' => esc_url_raw( $page_url ),
							'error_type' => sanitize_key( (string) ( $rendered['error_type'] ?? '' ) ),
							'error_code' => sanitize_text_field( (string) ( $rendered['error_code'] ?? '' ) ),
							'error_message' => sanitize_text_field( (string) ( $rendered['error_message'] ?? '' ) ),
							'status_code' => isset( $rendered['status_code'] ) ? (int) $rendered['status_code'] : 0,
						];
					}
					continue;
				}

				$scanned_any = true;
				$scan_meta['rendered_pages']++;
				self::merge_source_into_services(
					$services,
					(string) $rendered['body'],
					$definitions,
					[
						'title' => $page_title,
						'url'   => $page_url,
						'kind'  => 'rendered',
					]
				);
			}
		}

		return [
			'ok'         => $scanned_any,
			'url'        => $home_url,
			'scanned_at' => time(),
			'services'   => $services,
			'scan_meta'  => $scan_meta,
		];
	}

	private static function fetch_url_html( string $url ): array {
		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'             => self::REQUEST_TIMEOUT,
				'redirection'         => 3,
				'limit_response_size' => self::RESPONSE_LIMIT,
				'user-agent'          => 'Mozilla/5.0 (compatible; FZ152Scanner/' . F152_VERSION . ')',
				'headers'             => [
					'X-F152-Scanner' => '1',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'            => false,
				'body'          => '',
				'error_type'    => 'request',
				'error_code'    => (string) $response->get_error_code(),
				'error_message' => (string) $response->get_error_message(),
				'status_code'   => 0,
			];
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );
		if ( $status_code < 200 || $status_code >= 400 ) {
			return [
				'ok'            => false,
				'body'          => '',
				'error_type'    => 'http',
				'error_code'    => '',
				'error_message' => (string) wp_remote_retrieve_response_message( $response ),
				'status_code'   => $status_code,
			];
		}
		if ( '' === $body ) {
			return [
				'ok'            => false,
				'body'          => '',
				'error_type'    => 'empty',
				'error_code'    => '',
				'error_message' => 'empty response body',
				'status_code'   => $status_code,
			];
		}

		return [ 'ok' => true, 'body' => $body, 'status_code' => $status_code ];
	}

	private static function get_detected_definitions( string $source, array $definitions ): array {
		$detected = [];
		foreach ( $definitions as $service_id => $definition ) {
			if ( self::is_candidate_source( $source, $definition ) ) {
				$detected[ $service_id ] = $definition;
			}
		}

		return $detected;
	}

	private static function is_candidate_source( string $source, array $definition ): bool {
		$patterns = isset( $definition['patterns'] ) && is_array( $definition['patterns'] ) ? $definition['patterns'] : [];
		$candidate_patterns = isset( $definition['candidate_patterns'] ) && is_array( $definition['candidate_patterns'] )
			? $definition['candidate_patterns']
			: [];
		foreach ( array_merge( $patterns, $candidate_patterns ) as $pattern ) {
			if ( 1 === preg_match( $pattern, $source ) ) {
				return true;
			}
		}
		return false;
	}

	private static function empty_service_results( array $definitions ): array {
		$services = [];
		foreach ( $definitions as $service_id => $unused ) {
			$services[ $service_id ] = [
				'found_external'    => false,
				'found_managed'     => false,
				'ids'               => [],
				'locations'         => [],
				'managed_locations' => [],
			];
		}
		return $services;
	}

	private static function merge_source_into_services( array &$services, string $source, array $definitions, array $location ): void {
		$detection_source = self::frontend_detection_source( $source );

		foreach ( $definitions as $service_id => $definition ) {
			$detection = self::detect_service( $detection_source, $definition );
			$managed   = self::detect_managed_embed( $source, $service_id );
			if ( empty( $detection['found_external'] ) && ! $managed ) {
				continue;
			}

			if ( ! empty( $detection['found_external'] ) ) {
				$services[ $service_id ]['found_external'] = true;
				$services[ $service_id ]['ids'] = array_values(
					array_unique( array_merge( $services[ $service_id ]['ids'], $detection['ids'] ) )
				);
				self::append_location( $services[ $service_id ]['locations'], $location );
			}

			if ( $managed ) {
				$services[ $service_id ]['found_managed'] = true;
				self::append_location( $services[ $service_id ]['managed_locations'], $location );
			}
		}
	}

	private static function append_location( array &$locations, array $location ): void {
		$url = isset( $location['url'] ) ? esc_url_raw( (string) $location['url'] ) : '';
		$key = $url ?: sanitize_title( (string) ( $location['title'] ?? '' ) );
		if ( '' === $key ) {
			return;
		}

		foreach ( $locations as $item ) {
			$item_key = ! empty( $item['url'] )
				? (string) $item['url']
				: sanitize_title( (string) ( $item['title'] ?? '' ) );
			if ( $key === $item_key ) {
				return;
			}
		}

		$locations[] = [
			'title' => sanitize_text_field( (string) ( $location['title'] ?? '' ) ),
			'url'   => $url,
			'kind'  => sanitize_key( (string) ( $location['kind'] ?? 'stored' ) ),
		];
	}


	private static function frontend_detection_source( string $html ): string {
		$without_comments = preg_replace( '~<!--.*?-->~s', ' ', $html );
		if ( is_string( $without_comments ) ) {
			$html = $without_comments;
		}

		$matched = preg_match_all(
			'~<script\b[^>]*>.*?</script\s*>|<(?:iframe|img|link|embed|object)\b[^>]*>~is',
			$html,
			$matches
		);
		if ( false === $matched || 0 === $matched || empty( $matches[0] ) ) {
			return '';
		}
		return implode( "\n", $matches[0] );
	}

	private static function detect_managed_embed( string $html, string $service_id ): bool {
		$service_id = sanitize_key( $service_id );
		if ( '' === $service_id ) {
			return false;
		}

		$id = preg_quote( $service_id, '~' );
		return 1 === preg_match(
			'~\\bdata-f152-embed(?:-script)?\\s*=\\s*([\\\'\"])' . $id . '\\1~i',
			$html
		);
	}

	private static function get_page_source( int $page_id ): string {
		$parts   = [];
		$content = (string) get_post_field( 'post_content', $page_id );
		if ( '' !== $content ) {
			$parts[] = $content;
		}

		$meta = get_post_meta( $page_id );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $values ) {
				if ( ! is_array( $values ) ) {
					continue;
				}
				foreach ( $values as $value ) {
					self::collect_strings( maybe_unserialize( $value ), $parts, 0 );
				}
			}
		}

		$post = get_post( $page_id );
		if ( $post instanceof \WP_Post ) {
			$candidates = [];
			$template   = get_page_template_slug( $page_id );
			if ( $template && 'default' !== $template ) {
				$candidates[] = $template;
			}
			if ( ! empty( $post->post_name ) ) {
				$candidates[] = 'page-' . $post->post_name . '.php';
			}
			$candidates[] = 'page-' . $page_id . '.php';

			foreach ( array_unique( $candidates ) as $candidate ) {
				$path = locate_template( $candidate, false, false );
				if ( ! $path || ! is_readable( $path ) ) {
					continue;
				}
				$size = filesize( $path );
				if ( false === $size || $size > 1024 * 1024 ) {
					continue;
				}
				$file = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local active-theme template, not a remote request.
				if ( is_string( $file ) && '' !== $file ) {
					$parts[] = $file;
				}
			}
		}

		$source = implode( "\n", $parts );
		if ( strlen( $source ) > self::RESPONSE_LIMIT ) {
			$source = substr( $source, 0, self::RESPONSE_LIMIT );
		}
		return $source;
	}

	private static function collect_strings( $value, array &$parts, int $depth ): void {
		if ( $depth > 4 ) {
			return;
		}
		if ( is_string( $value ) ) {
			if ( '' !== trim( $value ) ) {
				$parts[] = $value;
			}
			return;
		}
		if ( ! is_array( $value ) ) {
			return;
		}
		foreach ( $value as $child ) {
			self::collect_strings( $child, $parts, $depth + 1 );
		}
	}

	private static function definitions(): array {
		return class_exists( '\\F152\\ServiceCatalog' ) ? ServiceCatalog::definitions() : [];
	}

	private static function detect_service( string $html, array $definition ): array {
		$found = false;
		foreach ( $definition['patterns'] as $pattern ) {
			if ( preg_match( $pattern, $html ) ) {
				$found = true;
				break;
			}
		}

		$ids = [];
		foreach ( $definition['id_patterns'] as $pattern ) {
			if ( preg_match_all( $pattern, $html, $matches ) && ! empty( $matches[1] ) ) {
				foreach ( $matches[1] as $id ) {
					$id = sanitize_text_field( (string) $id );
					if ( '' !== $id ) {
						$ids[] = $id;
					}
				}
			}
		}

		return [
			'found_external' => $found,
			'ids'            => array_values( array_unique( $ids ) ),
		];
	}

	private static function get_control_statuses(): array {
		$ym_id = Helpers::sanitize_metrika_id( get_option( 'f152_ym_counter', '' ) );
		$statuses = [
			'yandex_metrika' => [
				'provider'   => 'free',
				'available'  => true,
				'configured' => '' !== $ym_id,
				'active'     => '' !== $ym_id && (bool) get_option( 'f152_enabled', 1 ),
				'id'         => $ym_id,
				'mode'       => sanitize_key( (string) get_option( 'f152_ym_behavior', 'always' ) ),
			],
			'yandex_maps' => [
				'provider'           => 'free',
				'available'          => true,
				'configured'         => true,
				'inventory_evidence' => false,
				'control_type'       => 'embed',
				'embed_type'         => 'map',
				'active'     => (bool) get_option( 'f152_enabled', 1 ),
				'id'         => '',
				'mode'       => class_exists( '\F152\Settings' ) ? Settings::get_yandex_maps_behavior() : 'always',
			],
		];

		$free_statuses = $statuses;
		$filtered = apply_filters( 'f152_scanner_service_statuses', $statuses );
		$statuses = self::normalize_control_statuses( is_array( $filtered ) ? $filtered : [] );

		foreach ( $free_statuses as $service_id => $status ) {
			$normalized = self::normalize_control_status( (string) $service_id, $status );
			if ( null !== $normalized ) {
				$statuses[ sanitize_key( (string) $service_id ) ] = $normalized;
			}
		}

		return $statuses;
	}

	private static function normalize_control_statuses( array $statuses ): array {
		$result = [];
		foreach ( $statuses as $service_id => $status ) {
			$service_id = sanitize_key( (string) $service_id );
			if ( '' === $service_id || ! is_array( $status ) ) {
				continue;
			}

			if ( empty( ServiceCatalog::get( $service_id ) ) ) {
				continue;
			}
			$normalized = self::normalize_control_status( $service_id, $status );
			if ( null !== $normalized ) {
				$result[ $service_id ] = $normalized;
			}
		}
		return $result;
	}

	private static function normalize_control_status( string $service_id, array $status ): ?array {
		$service_id = sanitize_key( $service_id );
		if ( '' === $service_id ) {
			return null;
		}
		$mode = sanitize_key( (string) ( $status['mode'] ?? 'always' ) );
		if ( ! in_array( $mode, [ 'always', 'disable_on_reject', 'require_accept' ], true ) ) {
			$mode = 'require_accept';
		}
		$control_type = sanitize_key( (string) ( $status['control_type'] ?? '' ) );
		$control_type = 'embed' === $control_type ? 'embed' : '';
		$embed_type = sanitize_key( (string) ( $status['embed_type'] ?? 'content' ) );
		$embed_type = in_array( $embed_type, [ 'map', 'maps', 'video', 'content' ], true ) ? $embed_type : 'content';
		$id = isset( $status['id'] ) && is_scalar( $status['id'] )
			? sanitize_text_field( (string) $status['id'] )
			: '';

		$result = [
			'provider'   => sanitize_key( (string) ( $status['provider'] ?? 'extension' ) ),
			'available'  => ! empty( $status['available'] ),
			'configured' => ! empty( $status['configured'] ),
			'active'     => ! empty( $status['active'] ),
			'id'         => $id,
			'mode'       => $mode,
		];
		if ( array_key_exists( 'inventory_evidence', $status ) ) {
			$result['inventory_evidence'] = ! empty( $status['inventory_evidence'] );
		}
		if ( '' !== $control_type ) {
			$result['control_type'] = $control_type;
			$result['embed_type'] = $embed_type;
		}
		return $result;
	}
}
