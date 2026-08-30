<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ServiceScanner {

	private const TRANSIENT_TTL  = 10 * MINUTE_IN_SECONDS;
	private const RESPONSE_LIMIT = 5 * MB_IN_BYTES;
	private const TRANSIENT_KEY  = 'f152_service_scan_site_v11';

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

			if ( in_array( $service_id, [ 'yandex_maps', 'google_maps', '2gis_maps', 'vk_video', 'rutube', 'youtube', 'dzen_video' ], true ) ) {
				$mode = isset( $control['mode'] ) ? sanitize_key( (string) $control['mode'] ) : 'always';
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
					$message = in_array( $service_id, [ 'vk_video', 'rutube', 'youtube', 'dzen_video' ], true )
						? __( 'обнаружено в уже отданном HTML несмотря на выбранный режим блокировки. Очистите кэш страниц/CDN; если проблема останется, видео выводится мимо серверной обработки FZ-152', 'fz-152-rf' )
						: __( 'обнаружены в уже отданном HTML несмотря на выбранный режим блокировки. Очистите кэш страниц/CDN; если проблема останется, карта выводится мимо серверной обработки FZ-152', 'fz-152-rf' );
				} elseif ( $active ) {
					if ( 'google_maps' === $service_id ) {
						$message = __( 'обнаружены на сайте и сейчас загружаются всегда. В настройках Google Maps в Pro можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' );
					} elseif ( '2gis_maps' === $service_id ) {
						$message = __( 'обнаружены на сайте и сейчас загружаются всегда. В настройках 2ГИС в Pro можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' );
					} elseif ( 'vk_video' === $service_id ) {
						$message = __( 'обнаружено на сайте и сейчас загружается всегда. В настройках VK Видео в Pro можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' );
					} elseif ( 'rutube' === $service_id ) {
						$message = __( 'обнаружено на сайте и сейчас загружается всегда. В настройках RUTUBE в Pro можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' );
					} elseif ( 'youtube' === $service_id ) {
						$message = __( 'обнаружено на сайте и сейчас загружается всегда. В настройках YouTube в Pro можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' );
					} elseif ( 'dzen_video' === $service_id ) {
						$message = __( 'обнаружено на сайте и сейчас загружается всегда. В настройках Дзен Видео в Pro можно выбрать скрытие после отказа или показ только после согласия', 'fz-152-rf' );
					} else {
						$message = __( 'обнаружены на сайте и сейчас загружаются всегда. Во вкладке «Сервисы и трекеры» можно выбрать режим скрытия после отказа или показа только после согласия', 'fz-152-rf' );
					}
				} elseif ( $available && $configured ) {
					$message = __( 'обнаружены на сайте, но настроенное управление сейчас не активно. Проверьте настройки и состояние Pro', 'fz-152-rf' );
				} elseif ( $available ) {
					$message = __( 'обнаружены на сайте; текущая конфигурация FZ-152 не управляет их загрузкой', 'fz-152-rf' );
				} else {
					$message = __( 'обнаружены на сайте и сейчас не управляются FZ-152', 'fz-152-rf' );
				}
			} elseif ( $configured && $active ) {
				$message = __( 'обнаружен отдельный код вне FZ-152; он может загружаться независимо от настроек cookie. Удалите дублирующую вставку из темы, GTM или другого плагина', 'fz-152-rf' );
			} elseif ( 'yandex_metrika' === $service_id ) {
				$message = __( 'обнаружена вне FZ-152 и сейчас не управляется плагином. Перенесите номер счётчика во вкладку «Сервисы и трекеры» и удалите прежнюю вставку', 'fz-152-rf' );
			} elseif ( $available && $configured && ! $active ) {
				$message = __( 'обнаружен внешний код, а настроенное управление сейчас не активно. Проверьте настройки и состояние Pro; отдельная вставка может загружаться независимо', 'fz-152-rf' );
			} elseif ( $available ) {
				$message = __( 'обнаружен внешний код, который сейчас загружается независимо от FZ-152', 'fz-152-rf' );
			} else {
				$message = __( 'обнаружен на сайте и сейчас не управляется FZ-152', 'fz-152-rf' );
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

	private static function get_scan_result(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = self::scan_site();
		set_transient( self::TRANSIENT_KEY, $result, self::TRANSIENT_TTL );

		return $result;
	}

	private static function scan_site(): array {
		$definitions = self::definitions();
		$services    = self::empty_service_results( $definitions );
		$scanned_any = false;
		$home_url    = home_url( '/' );

		$home = self::fetch_url_html( $home_url );
		if ( ! empty( $home['ok'] ) ) {
			$scanned_any = true;
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
			foreach ( $page_ids as $page_id ) {
				$page_id = (int) $page_id;
				if ( $page_id <= 0 ) {
					continue;
				}

				$source = self::get_page_source( $page_id );
				if ( '' === $source ) {
					continue;
				}

				$scanned_any = true;
				self::merge_source_into_services(
					$services,
					$source,
					$definitions,
					[
						'title' => get_the_title( $page_id ) ?: sprintf(
							/* translators: %d: WordPress page ID. */
							__( 'Страница #%d', 'fz-152-rf' ),
							$page_id
						),
						'url'   => get_permalink( $page_id ) ?: '',
						'kind'  => 'stored',
					]
				);
			}
		}

		return [
			'ok'         => $scanned_any,
			'url'        => $home_url,
			'scanned_at' => time(),
			'services'   => $services,
		];
	}

	private static function fetch_url_html( string $url ): array {
		$scan_url = add_query_arg( 'f152_scan', (string) time(), $url );
		$response = wp_safe_remote_get(
			$scan_url,
			[
				'timeout'             => 5,
				'redirection'         => 3,
				'limit_response_size' => self::RESPONSE_LIMIT,
				'headers'             => [
					'Cache-Control' => 'no-cache',
					'Pragma'        => 'no-cache',
				],
				'user-agent'          => 'Mozilla/5.0 (compatible; FZ152Scanner/' . F152_VERSION . ')',
			]
		);

		if ( is_wp_error( $response ) ) {
			return [ 'ok' => false, 'body' => '' ];
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );
		if ( $status_code < 200 || $status_code >= 400 || '' === $body ) {
			return [ 'ok' => false, 'body' => '' ];
		}

		return [ 'ok' => true, 'body' => $body ];
	}

	private static function empty_service_results( array $definitions ): array {
		$services = [];
		foreach ( $definitions as $service_id => $unused ) {
			$services[ $service_id ] = [
				'found_external' => false,
				'ids'            => [],
				'locations'      => [],
			];
		}
		return $services;
	}

	private static function merge_source_into_services( array &$services, string $source, array $definitions, array $location ): void {
		foreach ( $definitions as $service_id => $definition ) {
			$detection = self::detect_service( $source, $definition );
			if ( empty( $detection['found_external'] ) ) {
				continue;
			}

			$services[ $service_id ]['found_external'] = true;
			$services[ $service_id ]['ids'] = array_values(
				array_unique( array_merge( $services[ $service_id ]['ids'], $detection['ids'] ) )
			);

			$url = isset( $location['url'] ) ? esc_url_raw( (string) $location['url'] ) : '';
			$key = $url ?: sanitize_title( (string) ( $location['title'] ?? '' ) );
			$known = [];
			foreach ( $services[ $service_id ]['locations'] as $item ) {
				$known[] = ! empty( $item['url'] )
					? (string) $item['url']
					: sanitize_title( (string) ( $item['title'] ?? '' ) );
			}
			if ( '' !== $key && ! in_array( $key, $known, true ) ) {
				$services[ $service_id ]['locations'][] = [
					'title' => sanitize_text_field( (string) ( $location['title'] ?? '' ) ),
					'url'   => $url,
					'kind'  => sanitize_key( (string) ( $location['kind'] ?? 'stored' ) ),
				];
			}
		}
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
		return [
			'yandex_metrika' => [
				'label'       => 'Яндекс.Метрика',
				'patterns'    => [
					'~(?:https?:)?//mc\.yandex\.(?:ru|com)/metrika/tag\.js~i',
					'~(?:https?:)?//mc\.yandex\.(?:ru|com)/watch/[0-9]+~i',
					'~\bym\s*\(\s*[\'\"]?[0-9]+[\'\"]?\s*,\s*[\'\"]init[\'\"]~i',
				],
				'id_patterns' => [
					'~\bym\s*\(\s*[\'\"]?([0-9]+)[\'\"]?\s*,\s*[\'\"]init[\'\"]~i',
					'~mc\.yandex\.(?:ru|com)/watch/([0-9]+)~i',
				],
			],
			'yandex_maps' => [
				'label'       => 'Яндекс.Карты',
				'patterns'    => [
					'~(?:https?:)?//api-maps\.yandex\.(?:ru|com)/services/constructor/1\.0/js/~i',
					'~(?:https?:)?//api-maps\.yandex\.(?:ru|com)/frame/v1/~i',
					'~(?:https?:)?//(?:www\.)?yandex\.(?:ru|com)/map-widget/~i',
				],
				'id_patterns' => [],
			],
			'google_maps' => [
				'label'       => 'Google Maps',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.|maps\.)?google\.[a-z.]+/maps/(?:embed|d/embed)(?:[/?]|$)~i',
					'~(?:https?:)?//(?:www\.|maps\.)?google\.[a-z.]+/maps\?[^\"\'<>\s]*(?:output(?:=|%3D)embed)~i',
				],
				'id_patterns' => [],
			],
			'2gis_maps' => [
				'label'       => '2ГИС',
				'patterns'    => [
					'~(?:https?:)?//widgets\.2gis\.com/widget(?:[?/#]|$)~i',
					'~(?:https?:)?//(?:widgets\.2gis\.com|firmsonmap\.api\.2gis\.ru)/js/DGWidgetLoader\.js~i',
					'~\bnew\s+DGWidgetLoader\s*\(~i',
				],
				'id_patterns' => [],
			],
			'vk_video' => [
				'label'       => 'VK Видео',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.)?vk\.com/video_ext\.php(?:[?/#]|$)~i',
					'~(?:https?:)?//(?:www\.)?vkvideo\.ru/video_ext\.php(?:[?/#]|$)~i',
					'~(?:https?:)?//(?:www\.)?vkontakte\.ru/video_ext\.php(?:[?/#]|$)~i',
				],
				'id_patterns' => [],
			],
			'rutube' => [
				'label'       => 'RUTUBE',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.)?rutube\.ru/play/embed/(?:[^\s\"\'<]*)~i',
				],
				'id_patterns' => [],
			],

			'youtube' => [
				'label'       => 'YouTube',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.)?youtube\.com/embed/(?:[^\s"\'<]*)~i',
					'~(?:https?:)?//(?:www\.)?youtube-nocookie\.com/embed/(?:[^\s"\'<]*)~i',
				],
				'id_patterns' => [],
			],
			'dzen_video' => [
				'label'       => 'Дзен Видео',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.)?dzen\.ru/embed/(?:[^\s"\'<]*)~i',
					'~(?:https?:)?//zen\.yandex\.ru/embed/(?:[^\s"\'<]*)~i',
					'~(?:https?:)?//frontend\.vh\.yandex\.ru/player/(?:[^\s"\'<]*)~i',
				],
				'id_patterns' => [],
			],

			'ga4' => [
				'label'       => 'Google Analytics 4',
				'patterns'    => [
					'~googletagmanager\.com/gtag/js\?[^\"\'<>&\\s]*id=G-[A-Z0-9_-]+~i',
					'~\bgtag\s*\(\s*[\'\"]config[\'\"]\s*,\s*[\'\"]G-[A-Z0-9_-]+[\'\"]~i',
				],
				'id_patterns' => [
					'~googletagmanager\.com/gtag/js\?[^\"\'<>&\\s]*id=(G-[A-Z0-9_-]+)~i',
					'~\bgtag\s*\(\s*[\'\"]config[\'\"]\s*,\s*[\'\"](G-[A-Z0-9_-]+)[\'\"]~i',
				],
			],
			'gtm' => [
				'label'       => 'Google Tag Manager',
				'patterns'    => [
					'~googletagmanager\.com/gtm\.js\?[^\"\'<>&\\s]*id=GTM-[A-Z0-9_-]+~i',
					'~googletagmanager\.com/ns\.html\?[^\"\'<>&\\s]*id=GTM-[A-Z0-9_-]+~i',
				],
				'id_patterns' => [
					'~googletagmanager\.com/(?:gtm\.js|ns\.html)\?[^\"\'<>&\\s]*id=(GTM-[A-Z0-9_-]+)~i',
				],
			],
			'digital_culture' => [
				'label'       => 'Цифровая культура (PRO.Культура.РФ)',
				'patterns'    => [
					'~(?:https?:)?//culturaltracking\.ru/static/js/spxl\.js(?:[?][^\s"\'<]*)?~i',
				],
				'id_patterns' => [
					'~culturaltracking\.ru/static/js/spxl\.js[?][^\s"\'<>&]*pixelId=([0-9]+)~i',
					'~data-pixel-id=["\']([0-9]+)["\']~i',
				],
			],
			'jivosite' => [
				'label'       => 'JivoSite',
				'patterns'    => [
					'~(?:https?:)?//code\.(?:jivo\.ru|jivosite\.com)/widget/[A-Za-z0-9_-]+~i',
					'~\bjivo_api\b~i',
				],
				'id_patterns' => [
					'~code\.(?:jivo\.ru|jivosite\.com)/widget/([A-Za-z0-9_-]+)~i',
				],
			],

			'vk_ads_pixel' => [
				'label'       => 'VK Реклама Pixel',
				'patterns'    => [
					'~top-fwz1\.mail\.ru/js/code\.js~i',
					'~top\.mail\.ru/js/code\.js~i',
					'~\b_tmr\.push\s*\(~i',
				],
				'id_patterns' => [
					'~_tmr\.push\s*\(\s*\{[^}]*\bid\s*:\s*[\'\"]?([0-9]+)[\'\"]?~is',
				],
			],
		];
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
				'provider'   => 'free',
				'available'  => true,
				'configured' => true,
				'active'     => (bool) get_option( 'f152_enabled', 1 ),
				'id'         => '',
				'mode'       => class_exists( '\F152\Settings' ) ? Settings::get_yandex_maps_behavior() : 'always',
			],
		];

		$statuses = apply_filters( 'f152_scanner_service_statuses', $statuses );

		return is_array( $statuses ) ? $statuses : [];
	}
}
