<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class PolicyServices {

	public const ZONE_VERSION = 1;
	private const FREE_SYNC_ACTION = 'f152_sync_free_policy_services';
	private static bool $initialized = false;

	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;

		add_action( 'f152_render_policy_sync_panel', [ __CLASS__, 'render_free_sync_panel' ], 5, 1 );
		add_action( 'admin_post_' . self::FREE_SYNC_ACTION, [ __CLASS__, 'handle_free_sync' ] );
	}

	public static function render_zone( string $document, ?array $service_ids = null ): string {
		$document = sanitize_key( $document );
		if ( ! in_array( $document, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			return '';
		}

		if ( null === $service_ids ) {
			$service_ids = self::services_for_new_pages();
		}

		$service_ids = array_values( array_unique( array_filter( array_map( 'sanitize_key', $service_ids ) ) ) );
		$chunks = [];
		foreach ( $service_ids as $service_id ) {
			$chunk = self::render_service( $document, $service_id );
			if ( '' !== trim( $chunk ) ) {
				$chunks[] = self::service_marker( $service_id ) . "\n" . $chunk;
			}
		}

		$start = sprintf( '<!-- f152-services:start:%s:v%d -->', $document, self::ZONE_VERSION );
		$end   = sprintf( '<!-- f152-services:end:%s -->', $document );
		$body  = empty( $chunks ) ? '' : implode( "\n\n", $chunks );

		return $start . "\n" . $body . "\n" . $end;
	}

	public static function services_for_new_pages(): array {
		$services = self::free_managed_services();
		$services = apply_filters( 'f152_policy_services_for_new_pages', $services );
		return is_array( $services ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $services ) ) ) ) : [];
	}

	public static function free_managed_services(): array {
		$services = [];
		$ym_id = class_exists( '\\F152\\Helpers' )
			? Helpers::sanitize_metrika_id( get_option( 'f152_ym_counter', '' ) )
			: trim( (string) get_option( 'f152_ym_counter', '' ) );
		if ( '' !== $ym_id ) {
			$services[] = 'yandex_metrika';
		}

		$maps_confirmed = ! empty( get_option( 'f152_yandex_maps_policy_confirmed', 0 ) );
		if ( class_exists( '\\F152\\ServiceInventory' ) && is_callable( [ '\\F152\\ServiceInventory', 'get' ] ) ) {
			$inventory = ServiceInventory::get();
			$maps_confirmed = $maps_confirmed || ! empty( $inventory['yandex_maps']['confirmed'] );
		}
		if ( $maps_confirmed ) {
			$services[] = 'yandex_maps';
		}

		$services = apply_filters( 'f152_free_managed_policy_services', $services );
		if ( ! is_array( $services ) ) {
			return [];
		}

		$result = [];
		foreach ( array_values( array_unique( array_filter( array_map( 'sanitize_key', $services ) ) ) ) as $service_id ) {
			$definition = ServiceCatalog::get( $service_id );
			if ( 'managed' === ( $definition['policy_mode'] ?? '' ) && 'free' === self::policy_owner( $service_id, $definition ) ) {
				$result[] = $service_id;
			}
		}
		return $result;
	}

	public static function render_service( string $document, string $service_id ): string {
		$definition = ServiceCatalog::get( $service_id );
		if ( empty( $definition ) || 'managed' !== ( $definition['policy_mode'] ?? '' ) ) {
			return '';
		}
		$documents = isset( $definition['policy_documents'] ) && is_array( $definition['policy_documents'] )
			? array_map( 'sanitize_key', $definition['policy_documents'] )
			: [];
		if ( ! in_array( $document, $documents, true ) ) {
			return '';
		}

		$block = '';
		switch ( $service_id ) {
			case 'yandex_metrika':
				$block = 'policy_cookie' === $document ? self::yandex_metrika_cookie_block() : self::yandex_metrika_pd_block();
				break;
			case 'yandex_maps':
				$block = 'policy_cookie' === $document ? self::yandex_maps_cookie_block() : self::yandex_maps_pd_block();
				break;
		}

		if ( 'pro' === sanitize_key( (string) ( $definition['policy_owner'] ?? '' ) ) && function_exists( 'apply_filters' ) ) {
			$block = apply_filters( 'f152/render_policy_service', $block, $document, $service_id, $definition );
		}
		if ( ! is_string( $block ) ) {
			return '';
		}

		$clean = preg_replace( '#<!--\s*f152-policy-source:.*?-->\s*#s', '', $block );
		return is_string( $clean ) ? $clean : $block;
	}

	public static function preserve_existing_free_services_on_full_replace( string $new_content, string $old_content, string $document ): string {
		$document = sanitize_key( $document );
		if ( ! in_array( $document, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			return $new_content;
		}

		if ( ! preg_match( self::zone_pattern( $document, true ), $new_content, $zone_match ) || ! isset( $zone_match[1] ) ) {
			return $new_content;
		}
		$parsed = self::parse_service_chunks( (string) $zone_match[1], $document );
		if ( empty( $parsed['ok'] ) ) {
			return $new_content;
		}

		$existing = isset( $parsed['chunks'] ) && is_array( $parsed['chunks'] ) ? $parsed['chunks'] : [];
		$free_chunks = [];
		foreach ( self::free_owned_services_for_document( $document ) as $service_id ) {
			if ( isset( $existing[ $service_id ] ) ) {
				$free_chunks[] = trim( (string) $existing[ $service_id ] );
				continue;
			}

			$had_managed_block = self::service_marker_version( $old_content, $service_id ) > 0;
			if ( ! $had_managed_block ) {
				continue;
			}
			$decision = self::free_service_sync_decision( $service_id, true );
			if ( ! in_array( $decision, [ 'add', 'preserve' ], true ) ) {
				continue;
			}

			$block = self::render_service( $document, $service_id );
			if ( '' !== trim( $block ) ) {
				$free_chunks[] = self::service_marker( $service_id ) . "\n" . trim( $block );
			}
		}

		$other_chunks = [];
		foreach ( $existing as $service_id => $chunk ) {
			$definition = ServiceCatalog::get( (string) $service_id );
			if ( 'free' === self::policy_owner( (string) $service_id, $definition ) ) {
				continue;
			}
			$other_chunks[] = trim( (string) $chunk );
		}

		$chunks = array_values( array_filter( array_merge( $free_chunks, $other_chunks ), static fn( $chunk ): bool => '' !== trim( (string) $chunk ) ) );
		$start = sprintf( '<!-- f152-services:start:%s:v%d -->', $document, self::ZONE_VERSION );
		$end = sprintf( '<!-- f152-services:end:%s -->', $document );
		$zone = $start . "\n" . ( empty( $chunks ) ? '' : implode( "\n\n", $chunks ) . "\n" ) . $end;

		$updated = preg_replace_callback(
			self::zone_pattern( $document ),
			static function() use ( $zone ): string { return $zone; },
			$new_content,
			1
		);
		return is_string( $updated ) ? $updated : $new_content;
	}

	public static function service_marker( string $service_id ): string {
		$definition = ServiceCatalog::get( $service_id );
		$version = isset( $definition['policy_version'] ) ? max( 1, (int) $definition['policy_version'] ) : 1;
		return sprintf( '<!-- f152-service:%s:v%d -->', sanitize_key( $service_id ), $version );
	}

	public static function service_marker_version( string $content, string $service_id ): int {
		$service_id = sanitize_key( $service_id );
		if ( '' === $service_id ) {
			return 0;
		}
		$pattern = '#<!--\s*f152-service:' . preg_quote( $service_id, '#' ) . ':v([0-9]+)\s*-->#i';
		if ( ! preg_match_all( $pattern, $content, $matches ) || empty( $matches[1] ) ) {
			return 0;
		}
		$versions = array_map( 'intval', $matches[1] );
		return empty( $versions ) ? 0 : max( $versions );
	}

	public static function expected_service_version( string $service_id ): int {
		$definition = ServiceCatalog::get( sanitize_key( $service_id ) );
		return isset( $definition['policy_version'] ) ? max( 1, (int) $definition['policy_version'] ) : 1;
	}

	public static function has_future_service_markers( string $content ): bool {
		foreach ( ServiceCatalog::all() as $service_id => $definition ) {
			if ( 'managed' !== ( $definition['policy_mode'] ?? '' ) ) {
				continue;
			}
			$found = self::service_marker_version( $content, (string) $service_id );
			if ( $found > self::expected_service_version( (string) $service_id ) ) {
				return true;
			}
		}
		return false;
	}

	public static function known_legacy_match( string $document, string $content ): array {
		$document = sanitize_key( $document );
		$templates = self::legacy_templates( $document );
		if ( empty( $templates ) || ! class_exists( '\\F152\\Helpers' ) ) {
			return [];
		}

		$actual = self::normalize_document( $content );
		foreach ( $templates as $legacy_id => $definition ) {
			$path = isset( $definition['path'] ) ? (string) $definition['path'] : '';
			if ( '' === $path || ! is_readable( $path ) ) {
				continue;
			}
			$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Bundled local template snapshot.
			if ( ! is_string( $raw ) || '' === $raw ) {
				continue;
			}
			$expected = Helpers::replace_common_macros( $raw );
			$macro_values = [];
			if ( self::normalize_document( $expected ) !== $actual ) {
				$macro_values = self::match_legacy_structure( $raw, $actual );
				if ( false === $macro_values ) {
					continue;
				}
			}

			return [
				'id'       => sanitize_key( (string) $legacy_id ),
				'services' => isset( $definition['services'] ) && is_array( $definition['services'] )
					? array_values( array_unique( array_filter( array_map( 'sanitize_key', $definition['services'] ) ) ) )
					: [],
				'macros'   => is_array( $macro_values ) ? $macro_values : [],
			];
		}

		return [];
	}

	public static function upgrade_known_legacy_content( string $document, string $content ): array {
		$match = self::known_legacy_match( $document, $content );
		if ( empty( $match ) ) {
			return [];
		}

		$services = isset( $match['services'] ) && is_array( $match['services'] ) ? $match['services'] : [];
		$macros = isset( $match['macros'] ) && is_array( $match['macros'] ) ? $match['macros'] : [];
		$current = self::render_current_document( $document, $services, $macros );
		if ( '' === trim( $current ) ) {
			return [];
		}

		$match['content'] = $current;
		return $match;
	}

	private static function render_current_document( string $document, array $service_ids, array $macro_values = [] ): string {
		$document = sanitize_key( $document );
		$option_map = [
			'policy_pd'     => 'f152_text_policy_pd',
			'policy_cookie' => 'f152_text_policy_cookie',
		];
		if ( ! isset( $option_map[ $document ] ) || ! class_exists( '\\F152\\Templates' ) || ! class_exists( '\\F152\\Helpers' ) ) {
			return '';
		}

		$raw = (string) Templates::default_for_option( $option_map[ $document ] );
		if ( '' === $raw ) {
			return '';
		}

		$macro_values = self::prepare_legacy_macro_values( $macro_values );
		foreach ( $macro_values as $macro_name => $macro_value ) {
			$raw = str_replace( '[' . $macro_name . ']', (string) $macro_value, $raw );
		}

		$macro = 'policy_pd' === $document ? '[f152_services_policy_pd]' : '[f152_services_policy_cookie]';
		$raw = str_replace( $macro, self::render_zone( $document, $service_ids ), $raw );
		return Helpers::replace_common_macros( $raw );
	}

	private static function match_legacy_structure( string $template, string $actual ) {
		$template = self::normalize_document( $template );
		$actual   = self::normalize_document( $actual );
		$parts = preg_split( '/(\\[f152_[a-z0-9_]+\\])/i', $template, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			return false;
		}

		$allowed = [
			'f152_policy_version',
			'f152_operator_identity',
			'f152_company_name',
			'f152_company_inn',
			'f152_company_email',
			'f152_site_url',
			'f152_link_policy_pd',
		];
		$captured = [];
		$cursor = 0;
		$count = count( $parts );

		for ( $i = 0; $i < $count; $i++ ) {
			$part = (string) $parts[ $i ];
			if ( ! preg_match( '/^\\[(f152_[a-z0-9_]+)\\]$/i', $part, $macro_match ) ) {
				$length = strlen( $part );
				if ( substr( $actual, $cursor, $length ) !== $part ) {
					return false;
				}
				$cursor += $length;
				continue;
			}

			$name = strtolower( (string) $macro_match[1] );
			if ( ! in_array( $name, $allowed, true ) ) {
				return false;
			}

			if ( array_key_exists( $name, $captured ) ) {
				$value = (string) $captured[ $name ];
				if ( substr( $actual, $cursor, strlen( $value ) ) !== $value ) {
					return false;
				}
				$cursor += strlen( $value );
				continue;
			}

			$next_static = '';
			for ( $j = $i + 1; $j < $count; $j++ ) {
				$candidate = (string) $parts[ $j ];
				if ( '' === $candidate || preg_match( '/^\\[f152_[a-z0-9_]+\\]$/i', $candidate ) ) {
					continue;
				}
				$next_static = $candidate;
				break;
			}

			if ( '' === $next_static ) {
				$value = substr( $actual, $cursor );
				$cursor = strlen( $actual );
			} else {
				$next_pos = strpos( $actual, $next_static, $cursor );
				if ( false === $next_pos ) {
					return false;
				}
				$value = substr( $actual, $cursor, $next_pos - $cursor );
				$cursor = $next_pos;
			}

			if ( strlen( $value ) > 2048 || preg_match( '/[<\\r\\n]/', $value ) ) {
				return false;
			}
			$captured[ $name ] = $value;
		}

		return $cursor === strlen( $actual ) ? $captured : false;
	}

	private static function prepare_legacy_macro_values( array $values ): array {
		$prepared = [];
		foreach ( $values as $name => $value ) {
			$name = sanitize_key( (string) $name );
			if ( 0 !== strpos( $name, 'f152_' ) ) {
				continue;
			}
			$prepared[ $name ] = (string) $value;
		}

		if ( ! isset( $prepared['f152_operator_identity'] ) && isset( $prepared['f152_company_name'] ) ) {
			$name = trim( (string) $prepared['f152_company_name'] );
			$inn  = trim( (string) ( $prepared['f152_company_inn'] ?? '' ) );
			if ( '' !== $name ) {
				$prepared['f152_operator_identity'] = '' !== $inn
					? $name . ' (ИНН ' . $inn . ', далее — «Оператор»)' 
					: $name . ' (далее — «Оператор»)' ;
			}
		}
		return $prepared;
	}

	private static function legacy_templates( string $document ): array {
		$base = trailingslashit( F152_DIR . 'assets/texts/legacy' );
		if ( 'policy_pd' === $document ) {
			return [
				'free-0-2-4' => [
					'path'     => $base . 'policy_pd_0.2.4.html',
					'services' => [ 'yandex_metrika' ],
				],
			];
		}
		if ( 'policy_cookie' === $document ) {
			return [
				'free-0-2-4' => [
					'path'     => $base . 'policy_cookie_0.2.4.html',
					'services' => [ 'yandex_metrika' ],
				],
			];
		}
		return [];
	}

	private static function normalize_document( string $content ): string {
		$content = str_replace( [ "\r\n", "\r" ], "\n", $content );
		return trim( $content );
	}

	public static function generated_page_id( string $document ): int {
		$document = sanitize_key( $document );
		$url_options = [
			'policy_pd'     => 'f152_link_policy_pd',
			'policy_cookie' => 'f152_link_policy_cookie',
		];
		if ( ! isset( $url_options[ $document ] ) ) {
			return 0;
		}

		$generated = get_option( 'f152_generated_page_ids', [] );
		$page_id = is_array( $generated ) && isset( $generated[ $document ] ) ? (int) $generated[ $document ] : 0;
		if ( $page_id <= 0 ) {
			return 0;
		}
		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
			return 0;
		}

		$configured_url = trim( (string) get_option( $url_options[ $document ], '' ) );
		$permalink = get_permalink( $page_id );
		if ( '' === $configured_url || ! is_string( $permalink ) || '' === $permalink ) {
			return 0;
		}

		return self::same_policy_url( $configured_url, $permalink ) ? $page_id : 0;
	}

	private static function same_policy_url( string $left, string $right ): bool {
		$normalize = static function( string $url ): string {
			$url = trim( $url );
			if ( '' === $url ) {
				return '';
			}

			$hash = strpos( $url, '#' );
			if ( false !== $hash ) {
				$url = substr( $url, 0, $hash );
			}
			return rtrim( $url, '/' );
		};
		return $normalize( $left ) === $normalize( $right );
	}

	public static function render_free_sync_panel( string $document ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$document = sanitize_key( $document );
		if ( ! in_array( $document, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			return;
		}

		$page_id = self::generated_page_id( $document );
		$readiness = self::all_free_sync_targets_ready();
		$status = '';
		$status_raw = filter_input( INPUT_GET, 'f152-free-policy-sync', FILTER_UNSAFE_RAW );
		$notice_nonce_raw = filter_input( INPUT_GET, 'f152-free-policy-sync-nonce', FILTER_UNSAFE_RAW );
		if ( is_string( $status_raw ) && is_string( $notice_nonce_raw ) ) {
			$notice_nonce = sanitize_text_field( wp_unslash( $notice_nonce_raw ) );
			if ( wp_verify_nonce( $notice_nonce, 'f152_free_policy_sync_notice' ) ) {
				$status = sanitize_key( wp_unslash( $status_raw ) );
			}
		}
		$ym_id = class_exists( '\\F152\\Helpers' )
			? Helpers::sanitize_metrika_id( get_option( 'f152_ym_counter', '' ) )
			: trim( (string) get_option( 'f152_ym_counter', '' ) );
		$inventory = class_exists( '\\F152\\ServiceInventory' ) && is_callable( [ '\\F152\\ServiceInventory', 'get' ] )
			? ServiceInventory::get()
			: [];
		$maps_confirmed = ! empty( $inventory['yandex_maps']['confirmed'] );
		$metrika_reflection = self::reflection_status( 'yandex_metrika' );
		$maps_reflection = self::reflection_status( 'yandex_maps' );
		$reflected_states = [ 'managed', 'legacy', 'manual', 'mixed' ];
		$show_metrika = '' !== $ym_id || in_array( $metrika_reflection, $reflected_states, true );
		$show_maps = $maps_confirmed || in_array( $maps_reflection, $reflected_states, true );
		$free_pending = ( $show_metrika || $show_maps ) ? self::free_sync_has_pending_changes() : false;
		$outdated_pro = ! defined( 'F152_PRO_VERSION' ) ? self::outdated_pro_services_in_document( $document ) : [];

		if ( $show_metrika && $show_maps ) {
			$sync_intro = __( 'FZ-152 может автоматически поддерживать описание Яндекс.Метрики и Яндекс.Карт на страницах политик, которые создал сам.', 'fz-152-rf' );
		} elseif ( $show_maps ) {
			$sync_intro = __( 'FZ-152 может автоматически поддерживать описание Яндекс.Карт на страницах политик, которые создал сам.', 'fz-152-rf' );
		} elseif ( $show_metrika ) {
			$sync_intro = __( 'FZ-152 может автоматически поддерживать описание Яндекс.Метрики на страницах политик, которые создал сам. Эта кнопка обновляет только управляемый блок сервиса и не заменяет остальной текст политики.', 'fz-152-rf' );
		} else {
			$sync_intro = __( 'Бесплатная версия управляет только текстами Яндекс.Метрики и Яндекс.Карт. Эти кнопки меняют только управляемые блоки сервисов, а не весь текст политики. Блоки, ранее созданные Pro, сохраняются без изменений.', 'fz-152-rf' );
		}

		if ( ! $free_pending && empty( $outdated_pro ) && ! in_array( $status, [ 'updated', 'adopted', 'unchanged' ], true ) ) {
			return;
		}
		?>
		<div class="f152-settings-card" style="margin:18px 0;">
			<div class="f152-settings-card__header">
				<div>
					<span class="f152-card-eyebrow">FZ-152 RF</span>
					<h2><?php echo esc_html__( 'Сервисы в политиках', 'fz-152-rf' ); ?></h2>
					<p><?php echo esc_html( $sync_intro ); ?></p>
				</div>
			</div>
			<div class="f152-settings-card__body">
				<?php if ( 'updated' === $status ) : ?><div class="notice notice-success inline"><p><?php echo esc_html__( 'Управляемые блоки сервисов бесплатной версии обновлены. Остальной текст политики не изменялся. Блоки Pro сохранены без изменений.', 'fz-152-rf' ); ?></p></div><?php endif; ?>
				<?php if ( 'adopted' === $status ) : ?><div class="notice notice-success inline"><p><?php echo esc_html__( 'Старый шаблон FZ-152 обновлён. Перед изменением WordPress сохранил ревизию страницы.', 'fz-152-rf' ); ?></p></div><?php endif; ?>
				<?php if ( 'unchanged' === $status ) : ?><div class="notice notice-info inline"><p><?php echo esc_html__( 'Здесь уже всё актуально.', 'fz-152-rf' ); ?></p></div><?php endif; ?>
				<?php if ( in_array( $status, [ 'legacy', 'ambiguous', 'incompatible', 'error' ], true ) ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html__( 'Автоматически обновить политики не удалось. Ничего не перезаписано — эту страницу лучше проверить вручную.', 'fz-152-rf' ); ?></p></div><?php endif; ?>

				<?php if ( ! empty( $outdated_pro ) ) : ?>
					<div class="notice notice-warning inline"><p>
						<?php
						$labels = array_map( static function( string $service_id ): string {
							$definition = ServiceCatalog::get( $service_id );
							return sanitize_text_field( (string) ( $definition['label'] ?? $service_id ) );
						}, $outdated_pro );
						/* translators: %s: comma-separated list of FZ-152 RF Pro service names with outdated managed policy blocks. */
						echo esc_html( sprintf( __( 'В этой политике есть блоки, ранее созданные FZ-152 RF Pro, для которых доступна новая редакция: %s. Бесплатная версия намеренно не изменяет тексты Pro. Активируйте Pro и сохраните сервисы на вкладке «Тексты», чтобы обновить эти блоки.', 'fz-152-rf' ), implode( ', ', $labels ) ) );
						?>
					</p></div>
				<?php endif; ?>

				<?php if ( $show_metrika || $show_maps ) : ?>
				<ul style="margin:0 0 12px 18px;list-style:disc;">
					<?php if ( $show_metrika ) : ?>
						<li><strong><?php echo esc_html__( 'Яндекс.Метрика:', 'fz-152-rf' ); ?></strong> <?php echo esc_html( '' !== $ym_id ? __( 'настроена — FZ-152 может добавить или обновить её описание.', 'fz-152-rf' ) : __( 'сейчас не настроена — при обновлении будет удалён только старый блок Метрики, созданный FZ-152.', 'fz-152-rf' ) ); ?></li>
					<?php endif; ?>
					<?php if ( $show_maps ) : ?>
						<li><strong><?php echo esc_html__( 'Яндекс.Карты:', 'fz-152-rf' ); ?></strong> <?php echo esc_html( $maps_confirmed ? __( 'найдены на сайте — FZ-152 может добавить или обновить их описание.', 'fz-152-rf' ) : __( 'в политике уже есть их описание, но сейчас карты на сайте не найдены. FZ-152 не удаляет такой текст автоматически.', 'fz-152-rf' ) ); ?></li>
					<?php endif; ?>
				</ul>
				<?php endif; ?>

				<?php if ( ! $free_pending ) : ?>
					<?php /* Only preserved Pro text needs attention; Free has nothing to rewrite. */ ?>
				<?php elseif ( $page_id <= 0 ) : ?>
					<div class="notice notice-info inline"><p><?php echo esc_html__( 'Эта страница политики не была создана FZ-152, поэтому плагин не будет менять её автоматически. Если вы ведёте политику вручную — ничего делать не нужно. Чтобы включить автообновление, создайте страницы политик через кнопку «Создать страницы» в основных настройках.', 'fz-152-rf' ); ?></p></div>
				<?php elseif ( ! empty( $readiness['blocked'] ) || empty( $readiness['ready'] ) ) : ?>
					<div class="notice notice-warning inline"><p><?php echo esc_html__( 'Автообновление сейчас недоступно: одна из страниц, созданных FZ-152, была изменена вручную или имеет неизвестный формат. Плагин ничего не перезапишет — проверьте эту страницу вручную.', 'fz-152-rf' ); ?></p></div>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::FREE_SYNC_ACTION ); ?>">
						<input type="hidden" name="return_document" value="<?php echo esc_attr( $document ); ?>">
						<?php wp_nonce_field( self::FREE_SYNC_ACTION ); ?>
						<?php
						$button_label = $show_metrika && $show_maps
							? __( 'Обновить тексты Метрики и Карт', 'fz-152-rf' )
							: ( $show_maps ? __( 'Обновить текст Яндекс.Карт', 'fz-152-rf' ) : __( 'Обновить текст Яндекс.Метрики', 'fz-152-rf' ) );
						submit_button( $button_label, 'secondary', 'submit', false );
						?>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php

	}

	public static function handle_free_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для выполнения этого действия.', 'fz-152-rf' ) );
		}
		check_admin_referer( self::FREE_SYNC_ACTION );

		$return_document = isset( $_POST['return_document'] )
			? sanitize_key( wp_unslash( (string) $_POST['return_document'] ) )
			: 'policy_pd';
		if ( ! in_array( $return_document, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			$return_document = 'policy_pd';
		}

		$prepared = self::build_free_sync_targets();
		if ( empty( $prepared['ok'] ) ) {
			self::redirect_free_sync( (string) ( $prepared['status'] ?? 'error' ), $return_document );
		}

		$targets = isset( $prepared['targets'] ) && is_array( $prepared['targets'] ) ? $prepared['targets'] : [];
		$changed = false;
		foreach ( $targets as $target ) {
			if ( (string) ( $target['old_content'] ?? '' ) !== (string) ( $target['new_content'] ?? '' ) ) {
				$changed = true;
				break;
			}
		}
		if ( ! self::apply_free_sync_targets( $targets ) ) {
			self::redirect_free_sync( 'error', $return_document );
		}

		if ( $changed ) {
			self::clear_free_sync_caches();
		}
		$status = ! empty( $prepared['adopted'] ) ? 'adopted' : ( $changed ? 'updated' : 'unchanged' );
		self::redirect_free_sync( $status, $return_document );
	}

	private static function free_sync_has_pending_changes(): bool {
		$prepared = self::build_free_sync_targets();
		if ( empty( $prepared['ok'] ) ) {
			return true;
		}

		$targets = isset( $prepared['targets'] ) && is_array( $prepared['targets'] ) ? $prepared['targets'] : [];
		foreach ( $targets as $target ) {
			if ( (string) ( $target['old_content'] ?? '' ) !== (string) ( $target['new_content'] ?? '' ) ) {
				return true;
			}
		}

		return ! empty( $prepared['adopted'] );
	}

	private static function outdated_pro_services_in_document( string $document ): array {
		$document = sanitize_key( $document );
		$page_id = self::generated_page_id( $document );
		if ( $page_id <= 0 ) {
			return [];
		}
		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post ) {
			return [];
		}
		$content = (string) $post->post_content;
		if ( ! preg_match( self::zone_pattern( $document, true ), $content, $zone_match ) || ! isset( $zone_match[1] ) ) {
			return [];
		}
		$parsed = self::parse_service_chunks( (string) $zone_match[1], $document );
		if ( empty( $parsed['ok'] ) || empty( $parsed['chunks'] ) || ! is_array( $parsed['chunks'] ) ) {
			return [];
		}

		$result = [];
		foreach ( $parsed['chunks'] as $service_id => $chunk ) {
			$service_id = sanitize_key( (string) $service_id );
			$definition = ServiceCatalog::get( $service_id );
			if ( 'managed' !== ( $definition['policy_mode'] ?? '' ) || 'pro' !== self::policy_owner( $service_id, $definition ) ) {
				continue;
			}
			$found = self::service_marker_version( (string) $chunk, $service_id );
			if ( $found > 0 && $found < self::expected_service_version( $service_id ) ) {
				$result[] = $service_id;
			}
		}
		return array_values( array_unique( $result ) );
	}

	private static function all_free_sync_targets_ready(): array {
		$found = 0;
		$blocked = false;
		foreach ( [ 'policy_pd', 'policy_cookie' ] as $document ) {
			$page_id = self::generated_page_id( $document );
			if ( $page_id <= 0 ) {
				continue;
			}
			$found++;
			$post = get_post( $page_id );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
				$blocked = true;
				continue;
			}
			$content = (string) $post->post_content;
			$state = self::zone_state_from_content( $content, $document );
			if ( ! empty( $state['compatible'] ) ) {
				continue;
			}
			if ( empty( self::known_legacy_match( $document, $content ) ) ) {
				$blocked = true;
			}
		}
		return [
			'ready'   => $found > 0 && ! $blocked,
			'blocked' => $blocked,
			'found'   => $found,
		];
	}

	private static function build_free_sync_targets(): array {
		$targets = [];
		$adopted = false;
		$found = 0;

		foreach ( [ 'policy_pd', 'policy_cookie' ] as $document ) {
			$page_id = self::generated_page_id( $document );
			if ( $page_id <= 0 ) {
				continue;
			}
			$found++;
			$post = get_post( $page_id );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
				return [ 'ok' => false, 'status' => 'error' ];
			}

			$old_content = (string) $post->post_content;
			$working = $old_content;
			$state = self::zone_state_from_content( $working, $document );
			if ( empty( $state['compatible'] ) ) {
				if ( (int) ( $state['count'] ?? 0 ) > 1 ) {
					return [ 'ok' => false, 'status' => 'ambiguous' ];
				}
				if ( ! empty( $state['future_zone'] ) || ! empty( $state['old_zone'] ) || ! empty( $state['future_service'] ) || ! empty( $state['duplicate_service'] ) ) {
					return [ 'ok' => false, 'status' => 'incompatible' ];
				}
				$upgrade = self::upgrade_known_legacy_content( $document, $working );
				if ( empty( $upgrade['content'] ) || ! is_string( $upgrade['content'] ) ) {
					return [ 'ok' => false, 'status' => 'legacy' ];
				}
				$working = (string) $upgrade['content'];
				$adopted = true;
				$state = self::zone_state_from_content( $working, $document );
				if ( empty( $state['compatible'] ) ) {
					return [ 'ok' => false, 'status' => 'incompatible' ];
				}
			}

			$sync = self::sync_free_services_in_content( $document, $working );
			if ( empty( $sync['ok'] ) || ! isset( $sync['content'] ) || ! is_string( $sync['content'] ) ) {
				return [ 'ok' => false, 'status' => (string) ( $sync['status'] ?? 'incompatible' ) ];
			}
			$targets[] = [
				'page_id'     => $page_id,
				'old_content' => $old_content,
				'new_content' => (string) $sync['content'],
			];
		}

		if ( 0 === $found ) {
			return [ 'ok' => false, 'status' => 'unavailable' ];
		}
		return [ 'ok' => true, 'status' => 'ready', 'targets' => $targets, 'adopted' => $adopted ];
	}

	private static function sync_free_services_in_content( string $document, string $content ): array {
		$document = sanitize_key( $document );
		$state = self::zone_state_from_content( $content, $document );
		if ( empty( $state['compatible'] ) ) {
			return [ 'ok' => false, 'status' => 'incompatible' ];
		}

		$pattern = self::zone_pattern( $document, true );
		if ( ! preg_match( $pattern, $content, $zone_match ) || ! isset( $zone_match[1] ) ) {
			return [ 'ok' => false, 'status' => 'incompatible' ];
		}
		$body = (string) $zone_match[1];
		$parsed = self::parse_service_chunks( $body, $document );
		if ( empty( $parsed['ok'] ) ) {
			return [ 'ok' => false, 'status' => 'incompatible' ];
		}

		$existing = isset( $parsed['chunks'] ) && is_array( $parsed['chunks'] ) ? $parsed['chunks'] : [];
		$preserved = [];
		foreach ( $existing as $service_id => $chunk ) {
			$definition = ServiceCatalog::get( $service_id );
			if ( 'free' !== self::policy_owner( $service_id, $definition ) ) {
				$preserved[] = (string) $chunk;
			}
		}

		$free_chunks = [];
		foreach ( self::free_owned_services_for_document( $document ) as $service_id ) {
			$definition = ServiceCatalog::get( $service_id );
			$has_marker = isset( $existing[ $service_id ] );
			$decision = self::free_service_sync_decision( $service_id, $has_marker );
			if ( 'preserve' === $decision && $has_marker ) {
				$free_chunks[] = (string) $existing[ $service_id ];
				continue;
			}
			if ( 'add' !== $decision ) {
				continue;
			}
			if ( ! $has_marker && self::content_mentions_service_outside_zone( $content, $document, $definition ) ) {
				continue;
			}
			$block = self::render_service( $document, $service_id );
			if ( '' !== trim( $block ) ) {
				$free_chunks[] = self::service_marker( $service_id ) . "\n" . $block;
			}
		}

		$chunks = array_merge( $free_chunks, $preserved );
		$start = sprintf( '<!-- f152-services:start:%s:v%d -->', $document, self::ZONE_VERSION );
		$end = sprintf( '<!-- f152-services:end:%s -->', $document );
		$zone = $start . "\n" . ( empty( $chunks ) ? '' : implode( "\n\n", array_map( 'trim', $chunks ) ) . "\n" ) . $end;

		$new_content = preg_replace_callback(
			self::zone_pattern( $document ),
			static function() use ( $zone ): string { return $zone; },
			$content,
			1
		);
		return is_string( $new_content )
			? [ 'ok' => true, 'content' => $new_content ]
			: [ 'ok' => false, 'status' => 'error' ];
	}

	private static function free_owned_services_for_document( string $document ): array {
		$result = [];
		foreach ( ServiceCatalog::all() as $service_id => $definition ) {
			$service_id = sanitize_key( (string) $service_id );
			if ( 'managed' !== ( $definition['policy_mode'] ?? '' ) || 'free' !== self::policy_owner( $service_id, (array) $definition ) ) {
				continue;
			}
			$documents = isset( $definition['policy_documents'] ) && is_array( $definition['policy_documents'] )
				? array_map( 'sanitize_key', $definition['policy_documents'] )
				: [];
			if ( in_array( sanitize_key( $document ), $documents, true ) ) {
				$result[] = $service_id;
			}
		}
		return $result;
	}

	private static function free_service_sync_decision( string $service_id, bool $has_marker ): string {
		$service_id = sanitize_key( $service_id );
		if ( 'yandex_metrika' === $service_id ) {
			$ym_id = class_exists( '\\F152\\Helpers' )
				? Helpers::sanitize_metrika_id( get_option( 'f152_ym_counter', '' ) )
				: trim( (string) get_option( 'f152_ym_counter', '' ) );
			return '' !== $ym_id ? 'add' : 'remove';
		}
		if ( 'yandex_maps' === $service_id ) {
			$inventory = class_exists( '\\F152\\ServiceInventory' ) && is_callable( [ '\\F152\\ServiceInventory', 'get' ] )
				? ServiceInventory::get()
				: [];
			if ( ! empty( $inventory['yandex_maps']['confirmed'] ) ) {
				return 'add';
			}

			return $has_marker ? 'preserve' : 'remove';
		}

		$active = self::free_managed_services();
		return in_array( $service_id, $active, true ) ? 'add' : ( $has_marker ? 'preserve' : 'remove' );
	}

	private static function policy_owner( string $service_id, array $definition = [] ): string {
		$owner = sanitize_key( (string) ( $definition['policy_owner'] ?? '' ) );
		if ( '' !== $owner ) {
			return $owner;
		}
		return in_array( sanitize_key( $service_id ), [ 'yandex_metrika', 'yandex_maps' ], true ) ? 'free' : 'manual';
	}

	private static function content_mentions_service_outside_zone( string $content, string $document, array $definition ): bool {
		$outside = preg_replace( self::zone_pattern( $document ), '', $content, 1 );
		return is_string( $outside ) && self::content_mentions_service( $outside, $definition );
	}

	private static function parse_service_chunks( string $body, string $document ): array {
		$marker_pattern = '#<!--\\s*f152-service:([a-z0-9_-]+):v([0-9]+)\\s*-->#i';
		if ( ! preg_match_all( $marker_pattern, $body, $matches, PREG_OFFSET_CAPTURE ) ) {
			return [ 'ok' => true, 'chunks' => [] ];
		}

		$chunks = [];
		$count = count( $matches[0] );
		for ( $i = 0; $i < $count; $i++ ) {
			$service_id = sanitize_key( (string) $matches[1][ $i ][0] );
			$version = (int) $matches[2][ $i ][0];
			$start = (int) $matches[0][ $i ][1];
			$end = $i + 1 < $count ? (int) $matches[0][ $i + 1 ][1] : strlen( $body );
			$definition = ServiceCatalog::get( $service_id );
			if ( isset( $chunks[ $service_id ] ) ) {
				return [ 'ok' => false, 'chunks' => [] ];
			}

			if ( ! empty( $definition ) ) {
				if ( 'managed' !== ( $definition['policy_mode'] ?? '' ) ) {
					return [ 'ok' => false, 'chunks' => [] ];
				}
				$documents = isset( $definition['policy_documents'] ) && is_array( $definition['policy_documents'] )
					? array_map( 'sanitize_key', $definition['policy_documents'] ) : [];
				if ( ! in_array( sanitize_key( $document ), $documents, true ) || $version > self::expected_service_version( $service_id ) ) {
					return [ 'ok' => false, 'chunks' => [] ];
				}
			}
			$chunks[ $service_id ] = substr( $body, $start, max( 0, $end - $start ) );
		}
		return [ 'ok' => true, 'chunks' => $chunks ];
	}

	private static function zone_state_from_content( string $content, string $document ): array {
		$state = [
			'count' => 0,
			'current_count' => 0,
			'future_zone' => false,
			'old_zone' => false,
			'future_service' => false,
			'unknown_service' => false,
			'duplicate_service' => false,
			'compatible' => false,
		];
		$document = sanitize_key( $document );
		if ( ! in_array( $document, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			return $state;
		}
		$doc = preg_quote( $document, '#' );
		$any = '#<!--\\s*f152-services:start:' . $doc . ':v([0-9]+)\\s*-->.*?<!--\\s*f152-services:end:' . $doc . '\\s*-->#s';
		$count = preg_match_all( $any, $content, $matches );
		if ( false === $count ) {
			return $state;
		}
		$state['count'] = (int) $count;
		foreach ( (array) ( $matches[1] ?? [] ) as $version ) {
			$version = (int) $version;
			if ( self::ZONE_VERSION === $version ) {
				$state['current_count']++;
			} elseif ( $version > self::ZONE_VERSION ) {
				$state['future_zone'] = true;
			} else {
				$state['old_zone'] = true;
			}
		}
		$state['future_service'] = self::has_future_service_markers( $content );

		if ( 1 === $state['count'] && 1 === $state['current_count'] ) {
			if ( preg_match( self::zone_pattern( $document, true ), $content, $zone ) && isset( $zone[1] ) ) {
				$parsed = self::parse_service_chunks( (string) $zone[1], $document );
				if ( empty( $parsed['ok'] ) ) {
					$marker_pattern = '#<!--\\s*f152-service:([a-z0-9_-]+):v([0-9]+)\\s*-->#i';
					preg_match_all( $marker_pattern, (string) $zone[1], $service_matches );
					$ids = array_map( 'sanitize_key', (array) ( $service_matches[1] ?? [] ) );
					$state['duplicate_service'] = count( $ids ) !== count( array_unique( $ids ) );
					foreach ( $ids as $id ) {
						if ( empty( ServiceCatalog::get( $id ) ) ) {
							$state['unknown_service'] = true;
						}
					}
				}
			}
		}
		$state['compatible'] = 1 === $state['count']
			&& 1 === $state['current_count']
			&& ! $state['future_zone']
			&& ! $state['old_zone']
			&& ! $state['future_service']
			&& ! $state['duplicate_service'];
		return $state;
	}

	private static function zone_pattern( string $document, bool $capture_body = false ): string {
		$document = preg_quote( sanitize_key( $document ), '#' );
		$body = $capture_body ? '(.*?)' : '.*?';
		return '#<!--\\s*f152-services:start:' . $document . ':v' . self::ZONE_VERSION . '\\s*-->' . $body . '<!--\\s*f152-services:end:' . $document . '\\s*-->#s';
	}

	private static function apply_free_sync_targets( array $targets ): bool {
		$updated = [];
		foreach ( $targets as $target ) {
			$page_id = (int) ( $target['page_id'] ?? 0 );
			$old_content = (string) ( $target['old_content'] ?? '' );
			$new_content = (string) ( $target['new_content'] ?? '' );
			if ( $page_id <= 0 ) {
				self::rollback_free_sync_targets( $updated );
				return false;
			}
			if ( $old_content === $new_content ) {
				continue;
			}

			$current = get_post( $page_id );
			if ( ! $current instanceof \WP_Post || (string) $current->post_content !== $old_content ) {
				self::rollback_free_sync_targets( $updated );
				return false;
			}

			if ( function_exists( 'wp_save_post_revision' ) ) {
				wp_save_post_revision( $page_id );
			}
			$result = wp_update_post( [ 'ID' => $page_id, 'post_content' => wp_slash( $new_content ) ], true );
			if ( is_wp_error( $result ) ) {
				self::rollback_free_sync_targets( $updated );
				return false;
			}
			$updated[] = [
				'page_id'     => $page_id,
				'old_content' => $old_content,
				'new_content' => $new_content,
			];
		}
		return true;
	}

	private static function rollback_free_sync_targets( array $updated ): void {
		foreach ( array_reverse( $updated ) as $target ) {
			$page_id = (int) ( $target['page_id'] ?? 0 );
			if ( $page_id <= 0 ) {
				continue;
			}
			$old_content = (string) ( $target['old_content'] ?? '' );
			$new_content = (string) ( $target['new_content'] ?? '' );
			$current = get_post( $page_id );

			if ( $current instanceof \WP_Post && (string) $current->post_content === $new_content ) {
				wp_update_post( [ 'ID' => $page_id, 'post_content' => wp_slash( $old_content ) ], true );
			}
		}
	}

	private static function clear_free_sync_caches(): void {
		if ( class_exists( '\\F152\\ServiceScanner' ) && is_callable( [ '\\F152\\ServiceScanner', 'clear_cache' ] ) ) {
			ServiceScanner::clear_cache();
		}
		if ( class_exists( '\\F152\\Settings' ) && is_callable( [ '\\F152\\Settings', 'handle_services_setting_updated' ] ) ) {
			Settings::handle_services_setting_updated( [ 'policy_sync' => 0 ], [ 'policy_sync' => 1 ] );
		}
	}

	private static function redirect_free_sync( string $status, string $document ): void {
		$document = in_array( sanitize_key( $document ), [ 'policy_pd', 'policy_cookie' ], true ) ? sanitize_key( $document ) : 'policy_pd';
		$url = add_query_arg( [
			'page' => 'f152',
			'tab' => 'texts',
			'subtab' => $document,
			'f152-free-policy-sync'       => sanitize_key( $status ),
			'f152-free-policy-sync-nonce' => wp_create_nonce( 'f152_free_policy_sync_notice' ),
		], admin_url( 'options-general.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	public static function reflected_in_generated_policies( string $service_id ): bool {
		$status = self::reflection_status( $service_id );
		return ! in_array( $status, [ 'missing', 'unreadable' ], true );
	}

	public static function reflection_status( string $service_id ): string {
		$service_id = sanitize_key( $service_id );
		$definition = ServiceCatalog::get( $service_id );
		$documents = isset( $definition['policy_documents'] ) && is_array( $definition['policy_documents'] )
			? array_values( array_filter( array_map( 'sanitize_key', $definition['policy_documents'] ) ) )
			: [];
		if ( empty( $documents ) ) {
			return 'not_applicable';
		}

		$statuses = [];
		foreach ( $documents as $document ) {
			$page_id = self::policy_page_id_for_read( $document );
			if ( $page_id <= 0 ) {
				return 'unreadable';
			}
			$post = get_post( $page_id );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
				return 'unreadable';
			}

			$content = (string) $post->post_content;
			if ( self::service_marker_version( $content, $service_id ) >= self::expected_service_version( $service_id ) ) {
				$statuses[] = 'managed';
				continue;
			}

			$legacy = self::known_legacy_match( $document, $content );
			$legacy_services = isset( $legacy['services'] ) && is_array( $legacy['services'] ) ? $legacy['services'] : [];
			if ( in_array( $service_id, array_map( 'sanitize_key', $legacy_services ), true ) ) {
				$statuses[] = 'legacy';
				continue;
			}

			if ( self::content_mentions_service( $content, $definition ) ) {
				$statuses[] = 'manual';
				continue;
			}
			return 'missing';
		}

		$statuses = array_values( array_unique( $statuses ) );
		return 1 === count( $statuses ) ? $statuses[0] : 'mixed';
	}

	private static function policy_page_id_for_read( string $document ): int {
		$document = sanitize_key( $document );
		$owned = self::generated_page_id( $document );
		if ( $owned > 0 ) {
			return $owned;
		}

		$url_options = [
			'policy_pd'     => 'f152_link_policy_pd',
			'policy_cookie' => 'f152_link_policy_cookie',
		];
		if ( ! isset( $url_options[ $document ] ) || ! function_exists( 'url_to_postid' ) ) {
			return 0;
		}
		$url = trim( (string) get_option( $url_options[ $document ], '' ) );
		if ( '' === $url ) {
			return 0;
		}
		$page_id = (int) url_to_postid( $url );
		if ( $page_id <= 0 ) {
			return 0;
		}
		$post = get_post( $page_id );
		return $post instanceof \WP_Post && 'page' === $post->post_type && 'trash' !== $post->post_status ? $page_id : 0;
	}

	private static function content_mentions_service( string $content, array $definition ): bool {
		$terms = isset( $definition['policy_terms'] ) && is_array( $definition['policy_terms'] )
			? $definition['policy_terms']
			: [];
		if ( empty( $terms ) ) {
			return false;
		}

		$text = html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$normalized = preg_replace( '/\s+/u', ' ', $text );
		if ( is_string( $normalized ) ) {
			$text = $normalized;
		}

		foreach ( $terms as $term ) {
			$term = trim( (string) $term );
			if ( '' === $term ) {
				continue;
			}
			$normalized_term = preg_replace( '/\s+/u', ' ', $term );
			if ( is_string( $normalized_term ) ) {
				$term = $normalized_term;
			}

			$pattern = '~(?<![\p{L}\p{N}_])' . preg_quote( $term, '~' ) . '(?![\p{L}\p{N}_])~iu';
			if ( 1 === preg_match( $pattern, $text ) ) {
				return true;
			}
		}
		return false;
	}

	private static function yandex_metrika_pd_block(): string {
		return <<<'HTML'
<!-- f152-policy-source: https://yandex.ru/legal/metrica_termsofuse/ru/ ; reviewed: 2026-08-29 -->
<div class="f152-policy-service" data-f152-policy-service="yandex_metrika">
	<h3>Яндекс.Метрика</h3>
	<table>
		<tr><th colspan="2">Цель обработки: анализ посещаемости и действий пользователей, получение статистики и улучшение работы сайта с помощью Яндекс.Метрики</th></tr>
		<tr><td>Категория персональных данных</td><td>общие (иные) персональные данные и технические сведения, относящиеся к посетителю сайта</td></tr>
		<tr><td>Перечень обрабатываемых данных</td><td>сведения о посещении сайта и действиях на его страницах, файлы cookie и иные сетевые идентификаторы, технические сведения об устройстве, браузере и операционной системе, а также иные технические данные, автоматически передаваемые при использовании сервиса</td></tr>
		<tr><td>Категории субъектов</td><td>посетители веб-сайта</td></tr>
		<tr><td>Правовое основание</td><td>согласие субъекта персональных данных на загрузку аналитического счётчика и передачу данных Яндексу для обработки по поручению Оператора (п. 1 ч. 1 и ч. 3 ст. 6 Федерального закона № 152-ФЗ); для собственной обработки Оператора в рамках указанной цели могут применяться иные основания, предусмотренные статьёй 6 Федерального закона № 152-ФЗ, если они действительно применимы к конкретной обработке</td></tr>
		<tr><td>Обработка по поручению</td><td>ООО «ЯНДЕКС» обрабатывает персональные данные посетителей сайта по поручению Оператора на условиях использования Яндекс.Метрики; поручение осуществляется в пределах целей и правовых оснований, определённых Оператором</td></tr>
		<tr><td>Действия с данными</td><td>сбор, запись, систематизация, накопление, хранение, уточнение, извлечение, использование, передача (предоставление, доступ), обезличивание, блокирование, удаление и уничтожение в объёме, необходимом для работы сервиса</td></tr>
		<tr><td>Способ обработки</td><td>автоматизированная обработка с передачей данных по информационно-телекоммуникационным сетям</td></tr>
		<tr><td>Срок обработки и хранения</td><td>до достижения указанной цели обработки, прекращения использования сервиса для этой цели либо до отзыва согласия или получения требования о прекращении обработки, если обработка основана на согласии и отсутствует иное законное основание; если законодательством или договором установлен иной обязательный срок — в течение такого срока</td></tr>
		<tr><td>Порядок уничтожения</td><td>удаление или обезличивание персональных данных по достижении цели обработки либо при наступлении иного законного основания для прекращения обработки в порядке, установленном разделом 8 настоящей Политики; если данные обрабатываются внешним лицом по поручению Оператора — также прекращение их обработки и удаление таким лицом в соответствии с поручением и условиями сервиса</td></tr>
	</table>
	<p>Для указанной цели Оператор использует сервис веб-аналитики «Яндекс.Метрика», предоставляемый ООО «ЯНДЕКС» (Россия, г. Москва, ул. Льва Толстого, д. 16). В отношении персональных данных посетителей сайта Оператор определяет цели обработки, а Яндекс действует по поручению Оператора в смысле ч. 3 ст. 6 Федерального закона № 152-ФЗ.</p>
	<p>Счётчик передаёт Яндексу данные о посещениях сайта, активности посетителя, cookie, устройстве, операционной системе и иные сведения, предусмотренные условиями сервиса. Перечень операций, выполняемых Яндексом по поручению Оператора, и условия обработки определены <a href="https://yandex.ru/legal/metrica_termsofuse/ru/">условиями использования Яндекс.Метрики</a> и <a href="https://yandex.ru/legal/confidential/ru/">политикой конфиденциальности Яндекса</a>.</p>
</div>
HTML;
	}

	private static function yandex_metrika_cookie_block(): string {
		return <<<'HTML'
<!-- f152-policy-source: https://yandex.ru/legal/metrica_termsofuse/ru/ ; reviewed: 2026-08-25 -->
<div class="f152-policy-service" data-f152-policy-service="yandex_metrika">
	<hr>
	<h3>Яндекс.Метрика</h3>
	<p>На сайте может использоваться сервис веб-аналитики «Яндекс.Метрика» ООО «ЯНДЕКС». При загрузке счётчика сервис может получать сведения о посещениях и действиях на страницах, файлы cookie и иные сетевые идентификаторы, а также технические параметры устройства, браузера и операционной системы. Эти сведения используются для анализа посещаемости, статистики, выявления ошибок и улучшения работы сайта.</p>
	<p>Загрузка Яндекс.Метрики зависит от выбранного на сайте режима аналитических cookie. Подробнее: <a href="https://yandex.ru/legal/metrica_termsofuse/ru/">условия использования Яндекс.Метрики</a> и <a href="https://yandex.ru/legal/confidential/ru/">политика конфиденциальности Яндекса</a>.</p>
</div>
HTML;
	}

	private static function yandex_maps_pd_block(): string {
		return <<<'HTML'
<!-- f152-policy-source: https://yandex.ru/legal/maps_api/ru/ ; reviewed: 2026-08-31 -->
<div class="f152-policy-service" data-f152-policy-service="yandex_maps">
	<h3>Яндекс Карты</h3>
	<table>
		<tr><th colspan="2">Цель обработки: отображение интерактивных карт, адресов и географических объектов с помощью сервисов Яндекс Карт</th></tr>
		<tr><td>Категория персональных данных</td><td>общие (иные) персональные данные и технические сведения, относящиеся к посетителю сайта</td></tr>
		<tr><td>Перечень обрабатываемых данных</td><td>IP-адрес, идентификатор устройства и, в зависимости от используемой функциональности сервиса, геолокационная информация, а также иные технические сведения, передаваемые при обращении к сервисам Яндекс Карт</td></tr>
		<tr><td>Категории субъектов</td><td>посетители веб-сайта, на страницах которого используются Яндекс Карты</td></tr>
		<tr><td>Правовое основание</td><td>согласие субъекта персональных данных на сбор, передачу и хранение IP-адреса, идентификатора устройства и, при использовании соответствующей функциональности, геолокационной информации в связи с загрузкой сервисов Яндекс Карт (п. 1 ч. 1 ст. 6 Федерального закона № 152-ФЗ; п. 8.1 условий использования отдельных сервисов «Яндекс Карт»)</td></tr>
		<tr><td>Действия с данными</td><td>сбор, запись, систематизация, накопление, хранение, использование, передача и иная автоматизированная обработка в объёме, необходимом для работы картографического сервиса</td></tr>
		<tr><td>Способ обработки</td><td>автоматизированная обработка с передачей данных по информационно-телекоммуникационным сетям</td></tr>
		<tr><td>Срок обработки и хранения</td><td>в течение периода использования сервиса для указанной цели и действия согласия субъекта персональных данных; после отзыва согласия или прекращения использования сервиса дальнейшая передача данных через картографические компоненты прекращается, а ранее переданные Яндексу сведения обрабатываются и хранятся в сроки, предусмотренные применимым законодательством и условиями сервиса</td></tr>
		<tr><td>Порядок уничтожения</td><td>прекращение загрузки картографических компонентов и передачи данных при отзыве согласия, достижении цели обработки или прекращении использования сервиса; персональные данные, находящиеся под контролем Оператора, удаляются или обезличиваются в порядке, установленном разделом 8 настоящей Политики, а сведения, ранее переданные Яндексу, обрабатываются и удаляются в соответствии с применимым законодательством и условиями сервиса</td></tr>
	</table>
	<p>На отдельных страницах сайта используются сервисы «Яндекс Карты», предоставляемые ООО «ЯНДЕКС» (Россия, г. Москва, ул. Льва Толстого, д. 16). При загрузке картографических компонентов браузер посетителя обращается к инфраструктуре Яндекса.</p>
	<p>В зависимости от используемой функциональности Яндекс может автоматически получать IP-адрес и идентификатор устройства посетителя, а также геолокационную информацию. Условия Яндекса предусматривают получение владельцем сайта соответствующего согласия посетителя на сбор, передачу и хранение этих сведений на серверах Яндекса. Подробные условия опубликованы в <a href="https://yandex.ru/legal/maps_api/ru/">условиях использования отдельных сервисов Яндекс Карт</a> и <a href="https://yandex.ru/legal/confidential/ru/">политике конфиденциальности Яндекса</a>.</p>
</div>
HTML;
	}

	private static function yandex_maps_cookie_block(): string {
		return <<<'HTML'
<!-- f152-policy-source: https://yandex.ru/legal/maps_api/ru/ ; reviewed: 2026-08-31 -->
<div class="f152-policy-service" data-f152-policy-service="yandex_maps">
	<hr>
	<h3>Яндекс Карты</h3>
	<p>На сайте могут использоваться сервисы «Яндекс Карты» ООО «ЯНДЕКС». При загрузке карты браузер обращается к серверам Яндекса; в зависимости от функциональности Яндекс может автоматически получать IP-адрес, идентификатор устройства и геолокационную информацию посетителя.</p>
	<p>Правовым основанием для связанной с загрузкой Яндекс Карт обработки указанных сведений является согласие субъекта персональных данных. Условия Яндекса предусматривают получение владельцем сайта согласия посетителя на сбор, передачу и хранение этой информации на серверах Яндекса. Подробнее: <a href="https://yandex.ru/legal/maps_api/ru/">условия использования отдельных сервисов Яндекс Карт</a> и <a href="https://yandex.ru/legal/confidential/ru/">политика конфиденциальности Яндекса</a>.</p>
</div>
HTML;
	}



}
