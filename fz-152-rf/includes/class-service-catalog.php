<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ServiceCatalog {

	public const CATALOG_VERSION = 10;

	public static function definitions(): array {
		$definitions = [
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
				'candidate_patterns' => [
					'~(?:https?:)?//(?:www\.)?yandex\.(?:ru|com)/maps(?:[/?#]|$)~i',
				],
				'id_patterns' => [],
			],
			'google_maps' => [
				'label'       => 'Google Maps',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.|maps\.)?google\.[a-z.]+/maps/(?:embed|d/embed)(?:[/?]|$)~i',
					'~(?:https?:)?//(?:www\.|maps\.)?google\.[a-z.]+/maps\?[^\"\'<>\s]*(?:output(?:=|%3D)embed)~i',
				],
				'candidate_patterns' => [
					'~(?:https?:)?//(?:www\.|maps\.)?google\.[a-z.]+/maps(?:[/?#]|$)~i',
				],
				'id_patterns' => [],
			],
			'2gis_maps' => [
				'label'       => '2ГИС',
				'patterns'    => [
					'~(?:https?:)?//widgets\.2gis\.com/widget(?:[?/#]|$)~i',
					'~(?:https?:)?//makemap\.2gis\.ru/widget(?:[?/#]|$)~i',
					'~(?:https?:)?//(?:widgets\.2gis\.com|firmsonmap\.api\.2gis\.ru)/js/DGWidgetLoader\.js~i',
					'~\bnew\s+DGWidgetLoader\s*\(~i',
				],
				'candidate_patterns' => [
					'~(?:https?:)?//(?:www\.)?2gis\.(?:ru|com)(?:[/?#]|$)~i',
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
				'candidate_patterns' => [
					'~(?:https?:)?//(?:www\.)?(?:vk\.com|vkvideo\.ru)/(?:video|clip)[^\s"\'<]*~i',
				],
				'id_patterns' => [],
			],
			'rutube' => [
				'label'       => 'RUTUBE',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.)?rutube\.ru/play/embed/(?:[^\s\"\'<]*)~i',
				],
				'candidate_patterns' => [
					'~(?:https?:)?//(?:www\.)?rutube\.ru/(?:video|shorts)/(?:[^\s"\'<]*)~i',
				],
				'id_patterns' => [],
			],

			'youtube' => [
				'label'       => 'YouTube',
				'patterns'    => [
					'~(?:https?:)?//(?:www\.)?youtube\.com/embed/(?:[^\s"\'<]*)~i',
					'~(?:https?:)?//(?:www\.)?youtube-nocookie\.com/embed/(?:[^\s"\'<]*)~i',
				],
				'candidate_patterns' => [
					'~(?:https?:)?//(?:www\.)?youtube\.com/(?:watch|shorts|live)(?:[/?#]|$)~i',
					'~(?:https?:)?//youtu\.be/(?:[^\s"\'<]*)~i',
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

            'amocrm' => [
                'label'       => 'amoCRM',
                'patterns'    => [
                    '~(?:https?:)?//forms\.amocrm\.(?:ru|com)/[^\s"\'<>]*~i',
                    '~\bamo_forms_params\b~i',
                    '~\bamo_forms_loaded\s*\(~i',
                ],
                'candidate_patterns' => [
                    '~(?:https?:)?//[A-Za-z0-9.-]+\.amocrm\.(?:ru|com)/api/(?:v[0-9]+/)?[^\s"\'<>]*~i',
                ],
                'id_patterns' => [],
            ],
            'bitrix24' => [
                'label'       => 'Битрикс24',
                'patterns'    => [
                    '~(?:https?:)?//(?:cdn(?:-ru)?\.)?bitrix24\.(?:ru|com)/[^\s"\'<>]*/crm/form/loader_[^\s"\'<>]*\.js~i',
                    '~\bdata-b24-form\s*=~i',
                    '~\bBitrix24FormObject\b~i',
                ],
                'candidate_patterns' => [
                    '~(?:https?:)?//[A-Za-z0-9.-]+\.bitrix24\.(?:ru|com)/rest/[0-9]+/[A-Za-z0-9_-]+/~i',
                ],
                'id_patterns' => [],
            ],
            'retailcrm' => [
                'label'       => 'RetailCRM / Simla.com',
                'patterns'    => [
                    '~(?:https?:)?//c\.retailcrm\.tech/widget/loader\.js~i',
                    '~\b_rcco\s*=\s*\{~i',
                ],
                'candidate_patterns' => [
                    '~(?:https?:)?//[A-Za-z0-9.-]+\.(?:retailcrm\.ru|simla\.com)/api/(?:v[0-9]+/)?[^\s"\'<>]*~i',
                ],
                'id_patterns' => [],
            ],
            'moysklad' => [
                'label'       => 'МойСклад',
                'patterns'    => [],
                'candidate_patterns' => [
                    '~(?:https?:)?//online\.moysklad\.ru/api/remap/[0-9.]+/[^\s"\'<>]*~i',
                ],
                'id_patterns' => [],
            ],

			'roistat' => [
				'label'       => 'Roistat',
				'patterns'    => [
					'~(?:https?:)?//cloud(?:-eu)?\.roistat\.com/(?:dist/module\.js|api/site/1\.0/)~i',
					'~\broistatProjectId\b~i',
				],
				'id_patterns' => [
					'~roistatProjectId\s*=\s*[\'\"]([A-Za-z0-9._-]{1,180})[\'\"]~i',
					'~cloud(?:-eu)?\.roistat\.com[\'\"]\s*,\s*[\'\"]([A-Za-z0-9._-]{1,180})[\'\"]\s*\)~i',
				],
			],

		];

		foreach ( self::extensions() as $id => $extension ) {
			if ( empty( $extension['definition'] ) || ! is_array( $extension['definition'] ) ) {
				continue;
			}
			if ( isset( $definitions[ $id ] ) && ! self::is_pro_owned_builtin( $id ) ) {
				continue;
			}
			$base = isset( $definitions[ $id ] ) && is_array( $definitions[ $id ] ) ? $definitions[ $id ] : [];
			$definition = self::normalize_definition( $extension['definition'], $base );
			if ( ! empty( $definition ) ) {
				$definitions[ $id ] = $definition;
			}
		}
		return $definitions;
	}

	public static function get( string $id ): array {
		$id = sanitize_key( $id );
		$definitions = self::definitions();
		if ( ! isset( $definitions[ $id ] ) || ! is_array( $definitions[ $id ] ) ) {
			return [];
		}
		$metadata = self::metadata();
		return array_merge( $definitions[ $id ], $metadata[ $id ] ?? [] );
	}

	public static function all(): array {
		$definitions = self::definitions();
		$metadata    = self::metadata();
		foreach ( $definitions as $id => &$definition ) {
			$definition = array_merge( $definition, $metadata[ $id ] ?? [] );
		}
		unset( $definition );
		return $definitions;
	}

	private static function built_in_metadata(): array {
		return [
			'yandex_metrika' => [ 'category' => 'analytics', 'policy_mode' => 'managed', 'policy_owner' => 'free', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 5, 'policy_terms' => [ 'Яндекс.Метрика', 'Яндекс Метрика', 'Яндекс.Метрики', 'Яндекс Метрики', 'Яндекс.Метрику', 'Яндекс Метрику', 'Яндекс.Метрикой', 'Яндекс Метрикой', 'Яндекс.Метрике', 'Яндекс Метрике', 'Yandex Metrica', 'Yandex Metrika' ], 'plugin_keywords' => [ 'yandex metrica', 'yandex metrika', 'yandex-metrica', 'yandex-metrika' ] ],
			'yandex_maps' => [ 'category' => 'maps', 'policy_mode' => 'managed', 'policy_owner' => 'free', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 6, 'policy_terms' => [ 'Яндекс.Карты', 'Яндекс Карты', 'Яндекс.Карт', 'Яндекс Карт', 'Яндекс.Картах', 'Яндекс Картах', 'Яндекс.Картами', 'Яндекс Картами', 'Yandex Maps' ], 'plugin_keywords' => [ 'yandex map', 'yandex maps', 'yandex-map', 'yandex-maps' ] ],
			'google_maps' => [ 'category' => 'maps', 'policy_mode' => 'manual_review', 'policy_owner' => 'manual', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 1, 'policy_terms' => [ 'Google Maps', 'Карты Google' ], 'plugin_keywords' => [ 'google maps', 'google-map', 'google-maps' ] ],
			'2gis_maps' => [ 'category' => 'maps', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 5, 'policy_terms' => [ '2ГИС', '2GIS' ], 'plugin_keywords' => [ '2gis', '2gis map', '2gis-map' ] ],
			'vk_video' => [ 'category' => 'video', 'policy_mode' => 'manual_review', 'policy_owner' => 'manual', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 1, 'policy_terms' => [ 'VK Видео', 'VK Video', 'ВКонтакте Видео' ], 'plugin_keywords' => [ 'vk video', 'vk-video', 'vkvideo' ] ],
			'rutube' => [ 'category' => 'video', 'policy_mode' => 'manual_review', 'policy_owner' => 'manual', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 1, 'policy_terms' => [ 'RUTUBE', 'Rutube' ], 'plugin_keywords' => [ 'rutube' ] ],
			'youtube' => [ 'category' => 'video', 'policy_mode' => 'manual_review', 'policy_owner' => 'manual', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 1, 'policy_terms' => [ 'YouTube', 'Ютуб' ], 'plugin_keywords' => [ 'youtube', 'youtube embed' ] ],
			'dzen_video' => [ 'category' => 'video', 'policy_mode' => 'manual_review', 'policy_owner' => 'manual', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 1, 'policy_terms' => [ 'Дзен Видео', 'Dzen Video' ], 'plugin_keywords' => [ 'dzen', 'zen video', 'yandex zen' ] ],
			'ga4' => [ 'category' => 'analytics', 'policy_mode' => 'manual_review', 'policy_owner' => 'manual', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 1, 'policy_terms' => [ 'Google Analytics', 'Google Аналитика' ], 'plugin_keywords' => [ 'google analytics', 'google-analytics' ] ],
			'gtm' => [ 'category' => 'container', 'policy_mode' => 'container_info', 'policy_owner' => 'info', 'policy_documents' => [], 'policy_version' => 1, 'plugin_keywords' => [ 'google tag manager', 'google-tag-manager', 'duracelltomi' ] ],
			'digital_culture' => [ 'category' => 'analytics', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 5, 'policy_terms' => [ 'Цифровая культура', 'PRO.Культура.РФ', 'PRO.Культура' ], 'plugin_keywords' => [ 'pro.культура', 'pro culture', 'culturaltracking' ] ],
			'jivosite' => [ 'category' => 'chat', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 5, 'policy_terms' => [ 'JivoSite', 'Jivo' ], 'plugin_keywords' => [ 'jivosite', 'jivochat', 'jivo chat', 'jivo' ] ],
			'vk_ads_pixel' => [ 'category' => 'marketing', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 5, 'policy_terms' => [ 'VK Реклама', 'VK Ads', 'VK Pixel', 'VK Реклама Pixel', 'пиксель VK' ], 'plugin_keywords' => [ 'vk pixel', 'vk-pixel', 'vk ads', 'vk-ads' ] ],
			'amocrm' => [ 'category' => 'data_transfer', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd' ], 'policy_version' => 5, 'policy_terms' => [ 'amoCRM', 'amo CRM', 'АмоCRM', 'АмоЦРМ' ], 'plugin_keywords' => [ 'wooamoconnector', 'amocrm', 'amo crm', 'amo-crm' ] ],
			'bitrix24' => [ 'category' => 'data_transfer', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd' ], 'policy_version' => 4, 'policy_terms' => [ 'Битрикс24', 'Bitrix24', '1С-Битрикс24' ], 'plugin_keywords' => [ 'flamix-bitrix24', 'bitrix24', 'bitrix 24', 'bitrix-24', 'битрикс24' ] ],
			'retailcrm' => [ 'category' => 'data_transfer', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd' ], 'policy_version' => 4, 'policy_terms' => [ 'RetailCRM', 'retailCRM', 'Simla.com', 'Simla' ], 'plugin_keywords' => [ 'woo-retailcrm', 'retailcrm', 'retail crm', 'simla.com', 'simla' ] ],
			'moysklad' => [ 'category' => 'data_transfer', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd' ], 'policy_version' => 6, 'policy_terms' => [ 'МойСклад', 'Мой Склад', 'MoySklad' ], 'plugin_keywords' => [ 'wooms' ] ],
			'roistat' => [ 'category' => 'analytics', 'policy_mode' => 'managed', 'policy_owner' => 'pro', 'policy_documents' => [ 'policy_pd', 'policy_cookie' ], 'policy_version' => 6, 'policy_terms' => [ 'Roistat' ], 'plugin_keywords' => [ 'roistat' ] ],
		];
	}

	private static function metadata(): array {
		$metadata = self::built_in_metadata();

		foreach ( self::extensions() as $id => $extension ) {
			if ( empty( $extension['metadata'] ) || ! is_array( $extension['metadata'] ) ) {
				continue;
			}

			$built_in = isset( $metadata[ $id ] ) && is_array( $metadata[ $id ] );
			$base = $built_in ? $metadata[ $id ] : [];
			$base_owner = sanitize_key( (string) ( $base['policy_owner'] ?? '' ) );
			if ( $built_in && 'pro' !== $base_owner ) {
				continue;
			}

			$normalized = self::normalize_metadata( $extension['metadata'], $base );
			if ( $built_in ) {
				$normalized['policy_owner'] = 'pro';
			} elseif ( 'free' === ( $normalized['policy_owner'] ?? '' ) ) {
				$normalized['policy_owner'] = 'managed' === ( $normalized['policy_mode'] ?? '' ) ? 'pro' : 'manual';
			}
			$metadata[ $id ] = $normalized;
		}
		return $metadata;
	}

	private static function extensions(): array {
		$extensions = function_exists( 'apply_filters' ) ? apply_filters( 'f152/service_extensions', [] ) : [];
		if ( ! is_array( $extensions ) ) {
			return [];
		}
		$result = [];
		foreach ( $extensions as $id => $extension ) {
			$id = sanitize_key( (string) $id );
			if ( '' === $id || ! is_array( $extension ) ) {
				continue;
			}
			$result[ $id ] = $extension;
		}
		return $result;
	}

	private static function is_pro_owned_builtin( string $service_id ): bool {
		$service_id = sanitize_key( $service_id );
		$metadata = self::built_in_metadata();
		return isset( $metadata[ $service_id ] )
			&& 'pro' === sanitize_key( (string) ( $metadata[ $service_id ]['policy_owner'] ?? '' ) );
	}

	private static function normalize_definition( array $definition, array $base = [] ): array {
		$label = array_key_exists( 'label', $definition )
			? sanitize_text_field( (string) $definition['label'] )
			: sanitize_text_field( (string) ( $base['label'] ?? '' ) );
		$patterns = array_key_exists( 'patterns', $definition )
			? self::normalize_regex_list( $definition['patterns'] )
			: self::normalize_regex_list( $base['patterns'] ?? [] );
		$id_patterns = array_key_exists( 'id_patterns', $definition )
			? self::normalize_regex_list( $definition['id_patterns'] )
			: self::normalize_regex_list( $base['id_patterns'] ?? [] );
		$candidate_patterns = array_key_exists( 'candidate_patterns', $definition )
			? self::normalize_regex_list( $definition['candidate_patterns'] )
			: self::normalize_regex_list( $base['candidate_patterns'] ?? [] );
		if ( '' === $label || empty( $patterns ) ) {
			return [];
		}
		return [
			'label'              => $label,
			'patterns'           => $patterns,
			'candidate_patterns' => $candidate_patterns,
			'id_patterns'        => $id_patterns,
		];
	}

	private static function normalize_regex_list( $patterns ): array {
		if ( ! is_array( $patterns ) ) {
			return [];
		}
		$result = [];
		foreach ( $patterns as $pattern ) {
			$pattern = (string) $pattern;
			if ( '' === $pattern || false === @preg_match( $pattern, '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- validates third-party regex without leaking warnings into admin.
				continue;
			}
			$result[] = $pattern;
		}
		return array_values( array_unique( $result ) );
	}

	private static function normalize_metadata( array $metadata, array $base = [] ): array {
		$result = [];
		$result['category'] = sanitize_key( (string) ( $metadata['category'] ?? ( $base['category'] ?? 'other' ) ) );
		$result['policy_mode'] = sanitize_key( (string) ( $metadata['policy_mode'] ?? ( $base['policy_mode'] ?? 'manual_review' ) ) );
		$result['policy_owner'] = sanitize_key( (string) ( $metadata['policy_owner'] ?? ( $base['policy_owner'] ?? 'manual' ) ) );
		$result['policy_version'] = max( 1, (int) ( $metadata['policy_version'] ?? ( $base['policy_version'] ?? 1 ) ) );
		foreach ( [ 'policy_documents', 'plugin_keywords', 'policy_terms' ] as $key ) {
			$value = array_key_exists( $key, $metadata ) && is_array( $metadata[ $key ] )
				? $metadata[ $key ]
				: ( isset( $base[ $key ] ) && is_array( $base[ $key ] ) ? $base[ $key ] : [] );
			$result[ $key ] = array_values( array_unique( array_filter( array_map(
				static function( $item ) use ( $key ): string {
					$item = trim( (string) $item );
					return 'policy_documents' === $key ? sanitize_key( $item ) : sanitize_text_field( $item );
				},
				$value
			) ) ) );
		}
		return $result;
	}

}
