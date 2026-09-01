<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;
final class Settings {
	private static $services_cache_purge_scheduled = false;

        public static function init() : void {
                $group_main     = 'f152';
                $group_services = 'f152_services';
                $group_toggle   = 'f152_toggle';

                self::register_csv_export_handler();
                add_action( 'admin_post_f152_rescan_site', [ __CLASS__, 'handle_rescan_site' ] );
                add_action( 'wp_ajax_f152_browser_scan_home', [ __CLASS__, 'handle_browser_scan_home' ] );

                register_setting($group_toggle, 'f152_enabled', [
                        'type'              => 'boolean',
                        'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
                        'default'           => 1,
                ]);
                register_setting($group_main, 'f152_banner_text', [
                        'type'              => 'string',
                        'sanitize_callback' => [__CLASS__, 'sanitize_banner_text'],
                        'default'           => Helpers::default_banner_text(),
                ]);
		register_setting($group_main, 'f152_site_url', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => home_url('/'),
		]);
		register_setting($group_main, 'f152_company_name', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_company_inn', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_company_email', [
			'type'              => 'string',
			'sanitize_callback' => [__CLASS__, 'sanitize_email_soft'],
			'default'           => '',
		]);
		register_setting($group_main, 'f152_scanner_ack_gtm', [
			'type'              => 'boolean',
			'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
			'default'           => 0,
		]);
                register_setting($group_services, 'f152_ym_counter', [
                        'type'              => 'string',
                        'sanitize_callback' => [Helpers::class, 'sanitize_metrika_id'],
                        'default'           => '',
                ]);
                register_setting($group_services, 'f152_ym_behavior', [
                        'type'              => 'string',
                        'sanitize_callback' => [__CLASS__, 'sanitize_metrika_mode'],
                        'default'           => 'always',
                ]);
                register_setting($group_services, 'f152_yandex_maps_behavior', [
                        'type'              => 'string',
                        'sanitize_callback' => [__CLASS__, 'sanitize_metrika_mode'],
                        'default'           => 'always',
                ]);
		register_setting($group_main, 'f152_popup_upper', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Templates::default_for_option( 'f152_popup_upper' ),
		]);
		register_setting($group_main, 'f152_popup_func', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Templates::default_for_option( 'f152_popup_func' ),
		]);
		register_setting($group_main, 'f152_popup_anal', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Templates::default_for_option( 'f152_popup_anal' ),
		]);
		register_setting($group_main, 'f152_popup_mark', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Templates::default_for_option( 'f152_popup_mark' ),
		]);
		register_setting($group_main, 'f152_link_policy_pd', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_policy_version', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_link_consent_pd', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_link_policy_cookie', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_link_consent_marketing', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		]);
		register_setting($group_main, 'f152_comment_enable', [
			'type'              => 'boolean',
			'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
			'default'           => 1,
		]);
		register_setting($group_main, 'f152_comment_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Helpers::default_personal_consent_text(),
		]);
		register_setting($group_main, 'f152_reviews_enable', [
			'type'              => 'boolean',
			'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
			'default'           => 1,
		]);
		register_setting($group_main, 'f152_reviews_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Helpers::default_personal_consent_text(),
		]);
		register_setting($group_main, 'f152_checkout_enable', [
			'type'              => 'boolean',
			'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
			'default'           => 1,
		]);
		register_setting($group_main, 'f152_checkout_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Helpers::default_personal_consent_text(),
		]);
		register_setting($group_main, 'f152_register_enable', [
			'type'              => 'boolean',
			'sanitize_callback' => [__CLASS__, 'sanitize_checkbox'],
			'default'           => 1,
		]);
		register_setting($group_main, 'f152_register_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'kses_paragraph'],
			'default'           => Helpers::default_personal_consent_text(),
		]);
		register_setting($group_main, 'f152_cookie_domain', [
			'type'              => 'string',
			'sanitize_callback' => [__CLASS__, 'sanitize_cookie_domain'],
			'default'           => '',
		]);
		register_setting($group_main, 'f152_theme', [
			'type'              => 'string',
			'sanitize_callback' => [__CLASS__, 'sanitize_theme_mode'],
			'default'           => 'light',
		]);
		register_setting($group_main, 'f152_btn_settings_view', [
			'type'              => 'string',
			'sanitize_callback' => [__CLASS__, 'sanitize_settings_button_view'],
			'default'           => 'button',
		]);
		register_setting($group_main, 'f152_color_bg', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);
		register_setting($group_main, 'f152_color_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);
                register_setting($group_main, 'f152_color_link', [
                        'type'              => 'string',
                        'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
                        'default'           => '',
                ]);
                register_setting($group_main, 'f152_btn_radius', [
                        'type'              => 'string',
                        'sanitize_callback' => [__CLASS__, 'sanitize_radius'],
                        'default'           => '',
                ]);
                register_setting($group_main, 'f152_font_size_text', [
                        'type'              => 'string',
                        'sanitize_callback' => [__CLASS__, 'sanitize_font_size'],
                        'default'           => '',
                ]);
                register_setting($group_main, 'f152_font_size_btn', [
                        'type'              => 'string',
                        'sanitize_callback' => [__CLASS__, 'sanitize_font_size'],
                        'default'           => '',
                ]);
		register_setting($group_main, 'f152_color_btn_accept_bg', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);
		register_setting($group_main, 'f152_color_btn_accept_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);

		register_setting($group_main, 'f152_color_btn_settings_bg', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);
		register_setting($group_main, 'f152_color_btn_settings_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);

		register_setting($group_main, 'f152_color_btn_reject_bg', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);
		register_setting($group_main, 'f152_color_btn_reject_text', [
			'type'              => 'string',
			'sanitize_callback' => [Helpers::class, 'sanitize_hex_color_soft'],
			'default'           => '',
		]);

		register_setting('f152_custom_css', 'f152_custom_css', [
			'type'              => 'string',
			'sanitize_callback' => [__CLASS__, 'sanitize_custom_css'],
			'default'           => '',
		]);
	}

	public static function sanitize_custom_css( $raw ) : string {
		$previous = (string) get_option( 'f152_custom_css', '' );
		$css      = is_string( $raw ) ? $raw : '';

		$css = wp_check_invalid_utf8( $css );
		$css = str_replace( "\0", '', $css );

		if ( strlen( $css ) > 102400 ) {
			add_settings_error(
				'f152_custom_css',
				'f152_custom_css_too_large',
				__( 'CSS не сохранён: превышен допустимый размер (максимум 100 КБ).', 'fz-152-rf' ),
				'error'
			);

			return $previous;
		}

		if (
			false !== strpos( $css, '<?' )
			|| preg_match( '#<\s*/?\s*(?:style|script)\b#i', $css )
		) {
			add_settings_error(
				'f152_custom_css',
				'f152_custom_css_invalid',
				__( 'CSS не сохранён: теги style, script и PHP-конструкции запрещены.', 'fz-152-rf' ),
				'error'
			);

			return $previous;
		}

		return trim( $css );
	}

        public static function admin_menu() : void {
                add_options_page(
                        'ФЗ-152',
                        'ФЗ-152',
                        'manage_options',
                        'f152',
                        [__CLASS__, 'render_page']
                );
        }

        public static function enqueue_assets(string $hook) : void {
                if ( $hook !== 'settings_page_f152' ) {
                        return;
                }

                wp_enqueue_style(
                        'f152-preview',
                        F152_URL . 'assets/css/152.css',
                        [],
                        Assets::asset_version( 'assets/css/152.css' )
                );

                wp_enqueue_style(
                        'f152-admin',
                        F152_URL . 'assets/css/admin.css',
                        [],
                        Assets::asset_version( 'assets/css/admin.css' )
                );

                wp_enqueue_script(
                        'f152-admin',
                        F152_URL . 'assets/js/admin.js',
                        [],
                        Assets::asset_version( 'assets/js/admin.js' ),
                        true
                );

                $tab_raw = filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW );
                $tab     = is_string( $tab_raw ) ? sanitize_key( wp_unslash( $tab_raw ) ) : 'main';

                if ( 'main' === $tab ) {
                        wp_localize_script(
                                'f152-admin',
                                'F152AdminScanner',
                                [
                                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                                        'homeUrl' => home_url( '/' ),
                                        'nonce'   => wp_create_nonce( 'f152_browser_scan_home' ),
                                ]
                        );
                }

                if ( 'custom_css' === $tab ) {
                        $cm_settings = wp_enqueue_code_editor( [ 'type' => 'text/css' ] );
                        if ( false !== $cm_settings && is_array( $cm_settings ) ) {
                                wp_add_inline_script(
                                        'code-editor',
                                        sprintf(
                                                'window.F152_CODE_EDITOR = %s;',
                                                wp_json_encode( $cm_settings )
                                        ),
                                        'after'
                                );
                        }
                }
        }

        public static function render_page() : void {
                if ( ! current_user_can('manage_options') ) {
			return;
		}
		$tab_raw = filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW );
		$tab     = is_string( $tab_raw ) ? sanitize_key( wp_unslash( $tab_raw ) ) : 'main';

		$text_subtabs_legacy = [ 'policy_pd', 'consent_pd', 'policy_cookie', 'consent_marketing' ];
		if ( in_array( $tab, $text_subtabs_legacy, true ) ) {
			$legacy = $tab;
			$tab    = 'texts';
			$_GET['subtab'] = $legacy;
		}

		$allowed_tabs = [ 'main', 'services', 'texts', 'policy_pd', 'consent_pd', 'policy_cookie', 'consent_marketing', 'logs', 'stats', 'integrations', 'custom_css' ];
		if ( ! in_array( $tab, $allowed_tabs, true ) ) {
			$tab = 'main';
		}

		?>
		<div class="wrap">
			<div class="f152-settings-header">
				<div class="f152-settings-header__main">
					<h1>ФЗ-152 — настройки</h1>
					<?php self::render_enable_toggle(); ?>
				</div>
				<?php self::render_review_cta(); ?>
			</div>

			<?php if ( class_exists( '\\F152\\Survey' ) ) { \F152\Survey::render(); } ?>

			<h2 class="nav-tab-wrapper f152-nav-tabs">
			                             <a href="<?php echo esc_url( self::tab_url('main') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='main'?'nav-tab-active':'' ); ?>">Основные настройки</a>
			                             <a href="<?php echo esc_url( self::tab_url('services') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='services'?'nav-tab-active':'' ); ?>">Сервисы и трекеры</a>
			                             <a href="<?php echo esc_url( self::tab_url('texts') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='texts'?'nav-tab-active':'' ); ?>">Тексты</a>
			                             <a href="<?php echo esc_url( self::tab_url('logs') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='logs'?'nav-tab-active':'' ); ?>">Журнал согласий</a>
			                             <a href="<?php echo esc_url( self::tab_url('stats') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='stats'?'nav-tab-active':'' ); ?>">Статистика</a>
			                             <a href="<?php echo esc_url( self::tab_url('integrations') ); ?>" class="nav-tab f152-nav-tab-pro <?php echo esc_attr( $tab==='integrations'?'nav-tab-active':'' ); ?>"><?php echo esc_html( apply_filters( 'f152_integrations_tab_label', 'Add-on' ) ); ?></a>
			                             <a href="<?php echo esc_url( self::tab_url('custom_css') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='custom_css'?'nav-tab-active':'' ); ?>">Кастомный CSS</a>
			</h2>

			<?php
			if ( $tab === 'main' ) {
				self::render_create_pages_notice();
				?>
				<form method="post" action="options.php">
					<?php settings_fields('f152'); ?>
					<?php self::render_tab_main(); ?>
					<?php submit_button('Сохранить изменения'); ?>
				</form>
				<?php
			} elseif ( $tab === 'services' ) {
				self::render_tab_services();
			} elseif ( $tab === 'texts' ) {
				self::render_create_pages_notice();
				self::render_tab_texts();
			} elseif ( $tab === 'logs' ) {
				self::render_tab_logs();
			} elseif ( $tab === 'stats' ) {
				self::render_banner_stats_block();
			} elseif ( $tab === 'integrations' ) {
				if ( has_action( 'f152_render_settings_tab_integrations' ) ) {
					do_action( 'f152_render_settings_tab_integrations' );
					self::render_multisite_discount_card();
					self::render_partner_program_card();
				} else {
					self::render_multisite_discount_card();
					self::render_partner_program_card();
					self::render_locked_integrations_tab();
				}
			} elseif ( $tab === 'custom_css' ) {
				self::render_tab_custom_css();
			}
			?>
		</div>
		<?php
	}

	private static function render_services_explainer() : void {
		$lead_paragraphs = [
			__( 'Знаю, что большинство из вас вообще не понимают, что это и для чего, поэтому попробую объяснить максимально простым языком.', 'fz-152-rf' ),
			__( 'У вас на сайте по умолчанию появляется баннер с кнопками "Принять", "Отклонить" и "Настроить", который создаёт этот плагин. Так же у вас в текстах политик и согласий указано, что используются куки сайта и Яндекс.Метрики.', 'fz-152-rf' ),
			__( 'Если пользователь принял, то никаких вопросов нет. Но вы же понимаете, что если пользователь ничего не нажал или нажал "Отклонить", то по 152 ФЗ, вы не можете выводить, например код Метрики, потому как она куки свои всё равно "обработает", на что у вас нет разрешения пользователя, ведь он не соглашался.', 'fz-152-rf' ),
			__( 'Вы по закону имеете право загружать счётчик Яндекс метрики только после того, как пользователь принял ваши условия (конечно, если у вас нет других, законных оснований собирать ПД, из статьи 6 этого ФЗ).', 'fz-152-rf' ),
		];

		$paragraphs = [
			__( 'Думаете все же используют метрику, Яндекс же наверно не дураки! Конечно! В условиях метрики от 7 августа 26 года прямо сказано, что владелец сайта является оператором ПД, а Яндекс действует по поручению. То есть даже яндекс Не говорит: «Метрику можно грузить всегда». Он говорит - сам докажи, на каком основании ты это делаешь. Он просто скинул ответственность на нас с вами))', 'fz-152-rf' ),
			__( 'И вот тут начинается "виляние хвостом"))', 'fz-152-rf' ),
			__( 'Если следовать закону у вас просто отвалится аналитика, что вредно для позиций сайта и тем более рекламы из директа. В плагине, в отношении Яндекс Метрики реализовано 3 режима работы. 1. Всегда показывать (это нарушение). 2. Показывать до того, как пользователь нажал "Отклонить" (это тоже нарушение). 3. Показывать только после того, как нажал "Принять". То есть, человек согласился - мы загружаем счётчик, он получает ещё и куку от метрики (нет нарушения). Но...', 'fz-152-rf' ),
			__( 'Давайте будем откровенными, сейчас многим абсолютно наплевать, что там нажал пользователь, хочет он куки или нет - он их получит. Штрафов за это пока в новостях никому не выписывают, а отключив метрику мы просто теряем кучу аналитики, что недопустимо.', 'fz-152-rf' ),
			__( 'Потому на этой странице скорее Профессиональный функционал для серьёзных проектов, которым не нужны лишние риски. Если у вас никому не известный блог, как у меня, вам это врятли пригодится. Но если у вас умные клиенты и конкуренты, которые прекрасно понимают законы, вашу перед ними ответственность и в случае спорных ситуаций могут вам начать угрожать жалобами, то возможно оно вам необходимо. Много с вами общаюсь и минимум у одного из пользователей плагина уже есть кейс потребительского терроризма подобным методом. Почему я и развиваю плагин дальше. Вовсе не потому, что параноик, хотя и не без этого)) Сейчас модно у юридических компаний выкладывать картинки, а потом высылать досудебки тем, кто их разместил у себя на сайте, скачав из яндекс картинок, так может случиться и с этим федеральным законом, если появится схема как на этом заработать. А может и не случиться, я не пытаюсь вас запугивать, не подумайте, просто имейте ввиду, что такое может случиться.', 'fz-152-rf' ),
			__( 'Помимо Метрики есть огромное множество всяких сервисов и систем, которые так же, как она пропихивают свои куки посетителям вашего сайта, о чём вы и сами скорее всего даже не знаете. Например, любые карты, любые вставки видео, ютуб, рутуб, дзен, вк, вк пиксель, мета пиксель, онлайн консультанты типа jivosite, аналитики, например roistat и много всего ещё. Список всего что следит будет состоять из тысяч строк.', 'fz-152-rf' ),
			__( 'Всё что стоит у вас на сайте и выдаёт посетителям идентифицирующие cookie, нужно учитывать в текстах политик. Бесплатный FZ-152 автоматически управляет блоками Яндекс.Метрики и подтверждённых на сайте Яндекс.Карт; остальные найденные сервисы нужно проверить отдельно.', 'fz-152-rf' ),
			__( 'Я, создав и Pro версию плагина, взял на себя обязательства его развивать. И бесплатную версию, конечно, тоже бросать не стану. В рамках этого развития, готовлюсь к тому, чтобы вы всеми этими следилками могли управлять уже сейчас, или когда это понадобится. Делать это или нет, выбор только ваш, но плагин закрывающий вопрос по ФЗ 152 должен технически это уметь, что вроде логично.', 'fz-152-rf' ),
			__( 'Так вот, на этой странице те самые следилки, которыми плагин теперь умеет управлять в зависимости от выбора пользователя. Наверно, если он нажал "Отклонить", то не стоит ему грузить куки от всех сервисов мира. Если вы добавляете, например счётчик гугл аналитикса в плагин, то необходимо удалить его из кода темы, плагин сам вставит необходимый код. С картами и видео ничего делать не надо, они обрабатываются автоматически. Если пользователь отклонил куки, то на месте видео увидит кнопку "изменить решение" или как-то так, аналогичной по значению кнопке "Принять", после чего у него сразу всё заработает, и он получит свою порцию кукисов ;)', 'fz-152-rf' ),
			__( 'На странице основных настроек есть автоматический сканер, который красным подсвечивает, то, что у вас есть, но работает мимо плагина и игнорирует пожелания пользователей, это не весь список, а только то, с чем плагин знаком и умеет работать. В будущем список будет расти, по мере ваших обращений.', 'fz-152-rf' ),
		];
		?>
		<div class="f152-services-explainer">
			<div class="f152-services-explainer__intro">
				<?php foreach ( $lead_paragraphs as $paragraph ) : ?>
					<p class="f152-services-explainer__lead"><?php echo esc_html( $paragraph ); ?></p>
				<?php endforeach; ?>
			</div>
			<details class="f152-services-explainer__details">
				<summary class="f152-services-explainer__toggle">
					<span class="f152-services-explainer__more"><?php echo esc_html__( 'Читать полностью', 'fz-152-rf' ); ?></span>
					<span class="f152-services-explainer__less"><?php echo esc_html__( 'Свернуть', 'fz-152-rf' ); ?></span>
				</summary>
				<div class="f152-services-explainer__body">
					<?php foreach ( $paragraphs as $paragraph ) : ?>
						<p><?php echo esc_html( $paragraph ); ?></p>
					<?php endforeach; ?>
				</div>
			</details>
		</div>
		<?php
	}

	private static function render_tab_services() : void {
		?>
		<div class="f152-services-wrap">
			<h2>Сервисы и трекеры</h2>
			<?php self::render_services_explainer(); ?>

			<?php self::render_services_cache_notice(); ?>

			<form id="f152-services-form" method="post" action="options.php">
				<?php settings_fields( 'f152_services' ); ?>

				<div class="f152-service-category-tabs nav-tab-wrapper" data-f152-service-tabs role="tablist" aria-label="Категории сервисов">
					<button type="button" class="nav-tab nav-tab-active" data-f152-service-tab="trackers" role="tab" aria-selected="true">Аналитика и трекеры</button>
					<button type="button" class="nav-tab" data-f152-service-tab="maps" role="tab" aria-selected="false">Карты</button>
					<button type="button" class="nav-tab" data-f152-service-tab="video" role="tab" aria-selected="false">Видео</button>
					<button type="button" class="nav-tab" data-f152-service-tab="consultants" role="tab" aria-selected="false">Онлайн-консультанты</button>
					<?php do_action( 'f152_render_service_category_tabs' ); ?>
				</div>

				<?php submit_button( 'Сохранить все сервисы' ); ?>

				<div class="f152-service-card" data-f152-service-category="trackers">
				<div class="f152-field">
					<h3>Яндекс.Метрика</h3>
					<p class="description">FZ-152 RF может самостоятельно загружать счётчик с учётом решения посетителя по аналитическим cookie.</p>
				</div>

				<div class="f152-field">
					<label for="f152_ym_counter">Номер счётчика Яндекс.Метрики</label>
					<input type="text" class="regular-text" id="f152_ym_counter" name="f152_ym_counter" value="<?php echo esc_attr( get_option( 'f152_ym_counter', '' ) ); ?>" pattern="\d+" inputmode="numeric">
					<small>Только цифры, узнать можно на странице <a href="https://metrika.yandex.ru/list" target="_blank" rel="noopener noreferrer">metrika.yandex.ru/list</a>.</small>
				</div>

				<?php $ym_behavior = self::get_metrika_behavior(); ?>
				<div class="f152-field">
					<label for="f152_ym_behavior">Загрузка счётчика Яндекс.Метрики</label>
					<select id="f152_ym_behavior" name="f152_ym_behavior">
						<option value="always" <?php selected( $ym_behavior, 'always' ); ?>>Всегда включена</option>
						<option value="disable_on_reject" <?php selected( $ym_behavior, 'disable_on_reject' ); ?>>Отключать после нажатия «Отклонить»</option>
						<option value="require_accept" <?php selected( $ym_behavior, 'require_accept' ); ?>>Включить только после «Принять»</option>
					</select>
					<small>
						<b>Всегда включена</b> — счётчик грузится на всех просмотрах.<br>
						<b>Отключать после нажатия «Отклонить»</b> — Метрика перестанет загружаться после отказа от аналитических cookie.<br>
						<b>Включить только после «Принять»</b> — Метрика не грузится до явного согласия (кнопка «Принять» или включённые аналитические cookie в настройках).
					</small>
				</div>
				</div>

				<div class="f152-service-card" data-f152-service-category="maps">
				<div class="f152-field">
					<h3>Яндекс.Карты</h3>
					<p class="description">FZ-152 RF может блокировать загрузку кода конструктора и iframe Яндекс.Карт до решения посетителя по аналитическим cookie.</p>
				</div>

				<?php $yandex_maps_behavior = self::get_yandex_maps_behavior(); ?>
				<div class="f152-field">
					<label for="f152_yandex_maps_behavior">Показ встроенных Яндекс.Карт</label>
					<select id="f152_yandex_maps_behavior" name="f152_yandex_maps_behavior">
						<option value="always" <?php selected( $yandex_maps_behavior, 'always' ); ?>>Всегда показывать</option>
						<option value="disable_on_reject" <?php selected( $yandex_maps_behavior, 'disable_on_reject' ); ?>>Скрывать после нажатия «Отклонить»</option>
						<option value="require_accept" <?php selected( $yandex_maps_behavior, 'require_accept' ); ?>>Показывать только после «Принять»</option>
					</select>
					<small>
						<b>Всегда показывать</b> — карта загружается независимо от решения посетителя.<br>
						<b>Скрывать после нажатия «Отклонить»</b> — карта доступна до явного отказа от аналитических cookie; после отказа вместо неё показывается заглушка.<br>
						<b>Показывать только после «Принять»</b> — код конструктора/iframe карты не загружается до явного разрешения аналитических cookie.<br>
						Если карта скрыта, кнопка «Разрешить и показать карту» включает только аналитические cookie и сразу загружает карту, не включая маркетинговые cookie.<br>
						FZ-152 выдаёт для кэшируемых страниц нейтральный код карты: решение конкретного посетителя применяется уже в его браузере. Это совместимо с full-page cache. При изменении настроек FZ-152 автоматически очищает WP Rocket; дополнительный серверный/CDN-кэш (например Nginx FastCGI Cache, Varnish или кэш хостинга) нужно очистить средствами сервера/хостинга либо дождаться его TTL.
					</small>
				</div>
				</div>

				<?php
				do_action( 'f152_render_settings_tab_services_fields' );
				?>

				<?php if ( ! has_action( 'f152_render_settings_tab_services_fields' ) && ! has_action( 'f152_render_settings_tab_services' ) ) : ?>
					<?php self::render_google_maps_pro_preview(); ?>
					<?php self::render_2gis_pro_preview(); ?>
					<?php self::render_jivosite_pro_preview(); ?>
					<?php self::render_vk_video_pro_preview(); ?>
					<?php self::render_rutube_pro_preview(); ?>
					<?php self::render_youtube_pro_preview(); ?>
					<?php self::render_dzen_video_pro_preview(); ?>
					<?php self::render_ga4_pro_preview(); ?>
					<?php self::render_gtm_pro_preview(); ?>
					<?php self::render_roistat_pro_preview(); ?>
					<?php self::render_digital_culture_pro_preview(); ?>
					<?php self::render_vk_ads_pro_preview(); ?>
				<?php endif; ?>

				<div class="f152-services-save-bottom">
					<?php submit_button( 'Сохранить все сервисы' ); ?>
				</div>
			</form>

			<?php
			do_action( 'f152_render_settings_tab_services' );
			?>
		</div>
		<?php
	}

	public static function handle_services_setting_updated( $old_value, $new_value ) : void {
		if ( $old_value === $new_value ) {
			return;
		}

		if ( class_exists( '\\F152\\ServiceScanner' ) && is_callable( [ '\\F152\\ServiceScanner', 'clear_cache' ] ) ) {
			ServiceScanner::clear_cache();
		}

		if ( self::$services_cache_purge_scheduled ) {
			return;
		}

		self::$services_cache_purge_scheduled = true;
		add_action( 'shutdown', [ __CLASS__, 'purge_services_page_cache' ], 999 );
	}

	public static function purge_services_page_cache() : void {
		self::$services_cache_purge_scheduled = false;

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	private static function render_services_cache_notice() : void {
		$updated_raw = filter_input( INPUT_GET, 'settings-updated', FILTER_UNSAFE_RAW );
		$updated     = is_string( $updated_raw ) ? sanitize_key( wp_unslash( $updated_raw ) ) : '';

		if ( 'true' !== $updated ) {
			return;
		}
		?>
		<div class="notice notice-warning inline f152-services-cache-notice">
			<p>
				<strong>Настройки сервисов сохранены.</strong>
				Если значения изменились, FZ-152 автоматически очищает кэш WP Rocket.
				Если сайт дополнительно использует серверный full-page cache (например Nginx FastCGI Cache, Varnish, CDN или кэш хостинга), очистите его средствами сервера/хостинга, чтобы новые правила начали действовать сразу. Иначе старая HTML-версия страницы может сохраняться до окончания TTL этого кэша.
			</p>
		</div>
		<?php
	}

	private static function render_jivosite_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="consultants">
			<div class="f152-service-card__heading">
				<div>
					<h3>JivoSite <small style="font-weight:400;color:#646970;">BETA</small></h3>
					<p class="description">Управление загрузкой онлайн-консультанта JivoSite с учётом решения посетителя по cookie и локальная визуальная заглушка чата, когда настоящий виджет заблокирован.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Когда загружать настоящий Jivo</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Загружать до явного отказа</div>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Что увидит посетитель, пока Jivo заблокирован</span>
				<div class="f152-service-preview__control" aria-hidden="true">Локальная копия кнопки и окна чата</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Бесплатный FZ-152 RF умеет обнаруживать JivoSite при проверке сайта, но управление его загрузкой и визуальная заглушка доступны в FZ-152 RF Pro.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_2gis_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="maps">
			<div class="f152-service-card__heading">
				<div>
					<h3>2ГИС</h3>
					<p class="description">Автоматическое управление встроенными картами 2ГИС с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Показ встроенных карт 2ГИС</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Показывать только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Бесплатный плагин может обнаружить карту 2ГИС при проверке сайта, но не управляет её загрузкой.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_google_maps_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="maps">
			<div class="f152-service-card__heading">
				<div>
					<h3>Google Maps</h3>
					<p class="description">Автоматическое управление встроенными картами Google с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Показ встроенных Google Maps</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Показывать только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Бесплатный плагин может обнаружить Google Maps при проверке сайта, но не управляет их загрузкой.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_vk_video_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="video">
			<div class="f152-service-card__heading">
				<div>
					<h3>VK Видео</h3>
					<p class="description">Автоматическое управление встроенными видео VK с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Показ встроенных VK Видео</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Показывать только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Бесплатный плагин может обнаружить VK Видео при проверке сайта, но не управляет их загрузкой.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_rutube_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="video">
			<div class="f152-service-card__heading">
				<div>
					<h3>RUTUBE</h3>
					<p class="description">Автоматическое управление встроенными видео RUTUBE с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Показ встроенных RUTUBE-видео</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Показывать только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Бесплатный плагин может обнаружить RUTUBE при проверке сайта, но не управляет загрузкой плеера.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_youtube_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="video">
			<div class="f152-service-card__heading">
				<div>
					<h3>YouTube</h3>
					<p class="description">Автоматическое управление стандартными встроенными YouTube-плеерами с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>
			<div class="f152-field">
				<span class="f152-service-preview__label">Показ встроенных YouTube-видео</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Показывать только после «Принять»</div>
			</div>
			<p class="description f152-service-card__pro-note">Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Бесплатный плагин может обнаружить стандартный iframe YouTube при проверке сайта, но не управляет его загрузкой.</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_dzen_video_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="video">
			<div class="f152-service-card__heading">
				<div>
					<h3>Дзен Видео</h3>
					<p class="description">Автоматическое управление встроенными видео Дзена с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>
			<div class="f152-field">
				<span class="f152-service-preview__label">Показ встроенных видео Дзена</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Показывать только после «Принять»</div>
			</div>
			<p class="description f152-service-card__pro-note">Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Бесплатный плагин может обнаружить iframe Дзена при проверке сайта, но не управляет его загрузкой.</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_ga4_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>Google Analytics 4</h3>
					<p class="description">Управление загрузкой Google Analytics с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Measurement ID</span>
				<div class="f152-service-preview__control" aria-hidden="true">G-XXXXXXXXXX</div>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Загрузка Google Analytics</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Включить только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Код загрузки Google Analytics не входит в бесплатный плагин.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_gtm_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>Google Tag Manager</h3>
					<p class="description">Управление загрузкой контейнера Google Tag Manager с учётом решения посетителя по необязательным cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Container ID</span>
				<div class="f152-service-preview__control" aria-hidden="true">GTM-XXXXXXX</div>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Загрузка Google Tag Manager</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Включить только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Код загрузки Google Tag Manager не входит в бесплатный плагин.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_roistat_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>Roistat</h3>
					<p class="description">Управление загрузкой аналитического кода Roistat с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Tracking code key</span>
				<div class="f152-service-preview__control" aria-hidden="true">project-key</div>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Загрузка Roistat</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Включить только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Бесплатный плагин может обнаружить прямую вставку Roistat при проверке сайта, а управление его загрузкой доступно в FZ-152 RF Pro.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_digital_culture_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>Цифровая культура (PRO.Культура.РФ)</h3>
					<p class="description">Управление загрузкой счётчика culturaltracking.ru с учётом решения посетителя по аналитическим cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Pixel ID</span>
				<div class="f152-service-preview__control" aria-hidden="true">8519</div>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Загрузка счётчика «Цифровая культура»</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Включить только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Бесплатный плагин может обнаружить прямую вставку culturaltracking.ru при проверке сайта, а управление её загрузкой доступно в FZ-152 RF Pro.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_vk_ads_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>VK Реклама Pixel</h3>
					<p class="description">Управление загрузкой пикселя VK Рекламы с учётом решения посетителя по маркетинговым cookie.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Pixel ID</span>
				<div class="f152-service-preview__control" aria-hidden="true">1234567</div>
			</div>

			<div class="f152-field">
				<span class="f152-service-preview__label">Загрузка VK Реклама Pixel</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">Включить только после «Принять»</div>
			</div>

			<p class="description f152-service-card__pro-note">
				Эта интеграция поставляется отдельным дополнением FZ-152 RF Pro. Код загрузки пикселя VK Рекламы не входит в бесплатный плагин.
			</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_tab_custom_css() : void {
		?>
		<div class="wrap f152-custom-css-wrap">
			<?php settings_errors( 'f152_custom_css' ); ?>

			<h2>Кастомный CSS</h2>

			<p class="description">
				<?php esc_html_e( 'Внимание: ошибочные стили могут изменить или скрыть элементы баннера. Стили выводятся после основного CSS плагина. Для переопределения более специфичных правил может потребоваться более точный селектор или !important.', 'fz-152-rf' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'f152_custom_css' ); ?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( self::tab_url( 'custom_css' ) ); ?>">

				<p>
					<textarea
						id="f152-custom-css"
						name="f152_custom_css"
						rows="24"
						class="large-text code"
						spellcheck="false"
						style="font-family:monospace;"
					><?php echo esc_textarea( (string) get_option( 'f152_custom_css', '' ) ); ?></textarea>
				</p>

				<?php submit_button( __( 'Сохранить CSS', 'fz-152-rf' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function is_pro_license_active() : bool {
		if ( ! defined( 'F152_PRO_VERSION' ) ) {
			return false;
		}

		if ( class_exists( '\\F152Pro\\License\\LicenseGate' ) && is_callable( [ '\\F152Pro\\License\\LicenseGate', 'is_valid' ] ) ) {
			try {
				return (bool) \F152Pro\License\LicenseGate::is_valid();
			} catch ( \Throwable $e ) {
				return false;
			}
		}

		if ( class_exists( '\\F152Pro\\License\\LicenseManager' ) && is_callable( [ '\\F152Pro\\License\\LicenseManager', 'is_active' ] ) ) {
			try {
				return (bool) \F152Pro\License\LicenseManager::is_active();
			} catch ( \Throwable $e ) {
				return false;
			}
		}

		return false;
	}

	public static function handle_browser_scan_home(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Недостаточно прав.' ], 403 );
		}

		check_ajax_referer( 'f152_browser_scan_home', 'nonce' );
		$html_raw = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- intentionally inspecting raw rendered homepage HTML.
		$html = is_string( $html_raw ) ? $html_raw : '';
		if ( '' === trim( $html ) ) {
			wp_send_json_error( [ 'message' => 'Браузер вернул пустую главную страницу.' ], 400 );
		}

		$max = 5 * MB_IN_BYTES;
		if ( strlen( $html ) > $max ) {
			$html = substr( $html, 0, $max );
		}

		if ( ! class_exists( '\F152\ServiceScanner' ) || ! is_callable( [ '\F152\ServiceScanner', 'store_browser_home_html' ] ) ) {
			wp_send_json_error( [ 'message' => 'Сканер недоступен.' ], 500 );
		}

		$result = ServiceScanner::store_browser_home_html( $html );
		wp_send_json_success( is_array( $result ) ? $result : [] );
	}

	public static function handle_rescan_site(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для выполнения этого действия.', 'fz-152-rf' ) );
		}

		check_admin_referer( 'f152_rescan_site' );
		if ( class_exists( '\\F152\\ServiceScanner' ) && is_callable( [ '\\F152\\ServiceScanner', 'clear_cache' ] ) ) {
			ServiceScanner::clear_cache();
		}

		$url = add_query_arg(
			[
				'page' => 'f152',
				'tab'  => 'main',
			],
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url . '#f152-site-audit' );
		exit;
	}

	private static function policy_issue_user_help( array $issue, bool $pro_active ): array {
		$label = sanitize_text_field( (string) ( $issue['label'] ?? 'сервис' ) );
		$owner = sanitize_key( (string) ( $issue['policy_owner'] ?? '' ) );
		$mode  = sanitize_key( (string) ( $issue['policy_mode'] ?? 'manual_review' ) );
		$category = sanitize_key( (string) ( $issue['category'] ?? '' ) );
		$detection_basis = sanitize_key( (string) ( $issue['detection_basis'] ?? 'confirmed' ) );
		$texts_url = self::subtab_url( 'policy_pd' );

		if ( 'data_transfer' === $category ) {
			$plugin_name = '';
			if ( ! empty( $issue['active_plugins'] ) && is_array( $issue['active_plugins'] ) ) {
				$first_plugin = reset( $issue['active_plugins'] );
				if ( is_array( $first_plugin ) ) {
					$plugin_name = sanitize_text_field( (string) ( $first_plugin['name'] ?? '' ) );
				}
			}

			if ( 'active_plugin' === $detection_basis ) {
				$reason = '' !== $plugin_name
					? sprintf( 'На сайте активен плагин «%s», который умеет передавать заявки, клиентов или заказы в %s.', $plugin_name, $label )
					: sprintf( 'На сайте активен плагин интеграции с %s.', $label );
				$reason .= ' По одному факту установки нельзя подтвердить, что передача включена, поэтому проверьте настройки интеграции. Если данные реально уходят во внешнюю систему, это нужно отразить в политике обработки персональных данных.';
				$chip = 'Возможная передача ПД';
			} elseif ( 'theme_code' === $detection_basis ) {
				$reason = sprintf( 'В коде активной темы найден признак интеграции с %s. Код может быть старым или неиспользуемым, поэтому проверьте, передаются ли туда заявки, клиенты или заказы. Если передача работает, это нужно отразить в политике обработки персональных данных.', $label );
				$chip = 'Проверьте передачу ПД';
			} else {
				$reason = sprintf( 'На сайте найден работающий признак %s. Если через эту интеграцию передаются заявки, клиенты или заказы, персональные данные уходят во внешнюю систему и это должно быть отражено в политике обработки персональных данных.', $label );
				$chip = 'Передача ПД';
			}

			if ( $pro_active ) {
				return [
					'message' => $reason . ' В Pro откройте «Тексты» → «Политика обработки ПД» и отметьте этот сервис, если интеграция действительно используется. Pro добавит и дальше будет поддерживать нужный блок автоматически.',
					'url'     => '',
					'button'  => '',
					'external'=> false,
					'group_action' => 'pro_policy',
					'chip'    => $chip,
				];
			}

			return [
				'message' => $reason . ' Если интеграция действительно используется, откройте вкладку «Тексты»: бесплатная версия покажет, какие сведения нужно проверить и отразить в политике вручную.',
				'url'     => '',
				'button'  => '',
				'external'=> false,
				'chip'    => $chip,
			];
		}

		if ( 'free' === $owner && 'managed' === $mode ) {
			$managed_pages = class_exists( '\\F152\\PolicyServices' )
				&& is_callable( [ '\\F152\\PolicyServices', 'generated_page_id' ] )
				&& \F152\PolicyServices::generated_page_id( 'policy_pd' ) > 0
				&& \F152\PolicyServices::generated_page_id( 'policy_cookie' ) > 0;

			if ( $managed_pages ) {
				return [
					'message' => sprintf( 'Мы нашли на сайте «%s», а в политиках нет нужного текста. Откройте вкладку «Тексты» и нажмите «Обновить тексты политик». FZ-152 сам добавит нужный блок.', $label ),
					'url'     => $texts_url,
					'button'  => 'Открыть тексты',
					'external'=> false,
				];
			}

			return [
				'message' => sprintf( 'Мы нашли на сайте «%s», а в политиках нет нужного текста. Нажмите «Перейти к замене текстов»: FZ-152 прокрутит страницу к нужной кнопке ниже. Плагин возьмёт страницы из настроек, заменит их актуальными текстами и добавит нужный блок. Новые страницы создаваться не будут. Внимание: ваши ручные изменения на этих страницах будут перезаписаны.', $label ),
				'url'     => self::tab_url( 'main' ) . '#f152-replace-policy-pages',
				'button'  => 'Перейти к замене текстов',
				'external'=> false,
			];
		}

		if ( 'pro' === $owner && 'managed' === $mode ) {
			$configured_by_pro = empty( $issue['frontend'] ) && ! empty( $issue['configured'] ) && ! empty( $issue['active_control'] );
			$intro = $configured_by_pro
				? sprintf( 'В FZ-152 Pro настроен сервис «%s», а в политиках нет нужного текста.', $label )
				: sprintf( 'Мы нашли на сайте «%s», а в политиках нет нужного текста.', $label );
			if ( $pro_active ) {
				return [
					'message' => $intro . ' Откройте вкладку «Тексты» → «Политика обработки ПД», отметьте этот сервис в блоке Pro и нажмите «Сохранить». Если страницы политик созданы FZ-152, Pro добавит нужный текст и дальше сможет обновлять его автоматически.',
					'url'     => '',
					'button'  => '',
					'external'=> false,
					'group_action' => 'pro_policy',
				];
			}

			return [
				'message' => $intro . ' Откройте вкладку «Тексты»: бесплатная версия покажет, что именно нужно добавить вручную.',
				'url'     => '',
				'button'  => '',
				'external'=> false,
			];
		}

		return [
			'message' => sprintf( 'Мы нашли на сайте «%s», а в политиках он не указан. FZ-152 пока не добавляет текст для этого сервиса автоматически. Откройте политики и добавьте понятный абзац: что сайт использует %s, зачем он нужен и какие сведения получает сервис (например cookie, IP-адрес или данные о просмотрах).', $label, $label ),
			'url'     => $texts_url,
			'button'  => 'Открыть тексты политик',
			'external'=> false,
		];
	}

	private static function render_tab_main() : void {
		if ( class_exists( '\F152\ServiceScanner' ) && is_callable( [ '\F152\ServiceScanner', 'clear_server_cache' ] ) ) {
			\F152\ServiceScanner::clear_server_cache();
		}

		$pro_active = self::is_pro_license_active();
		$tracker_issues = class_exists( '\F152\ServiceScanner' )
			? \F152\ServiceScanner::get_issues()
			: [];
		$scan_snapshot = class_exists( '\F152\ServiceScanner' )
			? \F152\ServiceScanner::get_scan_snapshot()
			: [];
		$scan_meta = isset( $scan_snapshot['scan_meta'] ) && is_array( $scan_snapshot['scan_meta'] )
			? $scan_snapshot['scan_meta']
			: [];
		$scan_home_ok = ! empty( $scan_meta['home_ok'] );
		$scan_failed_pages = isset( $scan_meta['failed_pages'] ) ? max( 0, (int) $scan_meta['failed_pages'] ) : 0;
		$scan_incomplete = ! $scan_home_ok || $scan_failed_pages > 0;
		$detected_frontend_labels = [];
		$scan_services = isset( $scan_snapshot['services'] ) && is_array( $scan_snapshot['services'] ) ? $scan_snapshot['services'] : [];
		foreach ( $scan_services as $service_id => $detection ) {
			if ( ! is_array( $detection ) || ( empty( $detection['found_external'] ) && empty( $detection['found_managed'] ) ) ) {
				continue;
			}
			$definition = class_exists( '\F152\ServiceCatalog' ) ? ServiceCatalog::get( (string) $service_id ) : [];
			$detected_frontend_labels[] = sanitize_text_field( (string) ( $definition['label'] ?? $service_id ) );
		}
		$detected_frontend_labels = array_values( array_unique( array_filter( $detected_frontend_labels ) ) );
		$service_inventory = class_exists( '\F152\ServiceInventory' )
			? \F152\ServiceInventory::get()
			: [];
		$policy_issues = class_exists( '\F152\ServiceInventory' )
			? \F152\ServiceInventory::policy_review_items()
			: [];
		$detected_plugins = ( class_exists( '\F152\PluginDetector' ) && \F152\PluginDetector::should_show_block() )
			? \F152\PluginDetector::detected_plugins()
			: [];
		$audit_count = count( $tracker_issues ) + count( $policy_issues ) + count( $detected_plugins ) + ( $scan_incomplete ? 1 : 0 );
		?>

		<div class="f152-onboarding">
			<div class="f152-onboarding__hero">
				<div>
					<h2>Настройка ФЗ-152 РФ</h2>
					<p>Пройдите короткий чеклист: заполните данные сайта, создайте юридические страницы, включите нужные чекбоксы и настройте cookie-баннер.</p>
				</div>
			</div>

			<div class="f152-onboarding__grid">
				<div class="f152-onboarding__card">
					<div class="f152-onboarding__step">1</div>
					<h3>Заполните данные</h3>
					<p>Укажите URL сайта, название ИП/ООО или ФИО физлица и email ответственного. ИНН можно добавить по желанию.</p>
				</div>
				<div class="f152-onboarding__card">
					<div class="f152-onboarding__step">2</div>
					<h3>Создайте страницы</h3>
					<p>Сохраните реквизиты и нажмите «Создать страницы». Плагин создаст только недостающие юридические страницы и заполнит ссылки.</p>
				</div>
				<div class="f152-onboarding__card">
					<div class="f152-onboarding__step">3</div>
					<h3>Проверьте тексты политик и согласий</h3>
					<p>Откройте созданные страницы, проверьте реквизиты и при необходимости адаптируйте тексты под свой сайт.</p>
				</div>
				<div class="f152-onboarding__card">
					<div class="f152-onboarding__step">4</div>
					<h3>Включите согласия</h3>
					<p>Отметьте места, где плагин должен добавлять обязательный чекбокс согласия и фиксировать событие в журнале.</p>
				</div>
				<div class="f152-onboarding__card">
					<div class="f152-onboarding__step">5</div>
					<h3>Настройте cookie и сервисы</h3>
					<p>Оформите cookie-баннер и выберите нужный режим работы сервисов на вкладке «Сервисы и трекеры».</p>
				</div>
			</div>


			<section class="f152-audit-card" id="f152-site-audit" aria-labelledby="f152-audit-title">
				<div class="f152-audit-card__header">
					<div>
						<span class="f152-card-eyebrow">Диагностика</span>
						<h3 id="f152-audit-title">Проверка сайта</h3>
						<p>Для первичной проверки сайта вам больше не нужны отдельные онлайн-сервисы: FZ-152 сам проверит главную и опубликованные страницы, найдёт известные формы, трекеры и внешние сервисы и подскажет, что нужно проверить или изменить. Сканирование запускается автоматически при открытии этой вкладки. Записи блога и товары не сканируются; браузер дополнительно перепроверяет главную страницу.</p>
					</div>
					<?php if ( $audit_count > 0 ) : ?><span class="f152-audit-card__count f152-audit-card__count--attention"><?php echo esc_html( sprintf( 'Нужно проверить: %d', $audit_count ) ); ?></span><?php else : ?><span class="f152-audit-card__count">Проблем не найдено</span><?php endif; ?>
				</div>

				<div class="f152-audit-list">
					<div class="f152-audit-item" data-f152-browser-scan>
						<div class="f152-audit-item__icon"><span class="dashicons dashicons-search" aria-hidden="true"></span></div>
						<div class="f152-audit-item__body">
							<div class="f152-audit-item__title"><strong>Результат проверки сайта</strong><span class="f152-status-chip f152-status-chip--scanning" data-f152-browser-scan-chip aria-live="polite"><span class="f152-status-chip__spinner" aria-hidden="true"></span><span data-f152-browser-scan-chip-text>Сканирование…</span></span></div>
							<?php if ( ! empty( $detected_frontend_labels ) ) : ?>
								<p><strong>Найдены сервисы:</strong> <?php echo esc_html( implode( ', ', $detected_frontend_labels ) ); ?>.</p>
							<?php else : ?>
								<p>На проверенных страницах известные внешние сервисы пока не найдены. Браузер дополнительно перепроверит главную страницу.</p>
							<?php endif; ?>
							<p class="f152-audit-item__meta" data-f152-browser-scan-status aria-live="polite">Сканирование ещё выполняется. Результат обновится автоматически.</p>
							<?php $rescan_url = wp_nonce_url( admin_url( 'admin-post.php?action=f152_rescan_site' ), 'f152_rescan_site' ); ?>
							<p><a class="button button-secondary" href="<?php echo esc_url( $rescan_url ); ?>">Проверить сайт сейчас</a></p>
						</div>
					</div>

					<?php if ( $scan_incomplete ) : ?>
						<?php
						$home_error = isset( $scan_meta['home_error'] ) && is_array( $scan_meta['home_error'] ) ? $scan_meta['home_error'] : [];
						$failed_page_details = isset( $scan_meta['failed_page_details'] ) && is_array( $scan_meta['failed_page_details'] ) ? $scan_meta['failed_page_details'] : [];
						?>
						<div class="f152-audit-item">
							<div class="f152-audit-item__icon"><span class="dashicons dashicons-warning" aria-hidden="true"></span></div>
							<div class="f152-audit-item__body">
								<div class="f152-audit-item__title"><strong>Не получилось проверить все страницы</strong><span class="f152-status-chip f152-status-chip--attention">Повторите проверку</span></div>
								<?php if ( ! $scan_home_ok && $scan_failed_pages > 0 ) : ?>
									<p><?php echo esc_html( sprintf( 'FZ-152 не смог автоматически открыть главную страницу и ещё %d страниц. Сам сайт при этом может работать нормально: иногда хостинг или защита сайта блокирует запрос, который WordPress делает сам к себе.', $scan_failed_pages ) ); ?></p>
								<?php elseif ( ! $scan_home_ok ) : ?>
									<p>FZ-152 не смог автоматически открыть главную страницу. Сам сайт при этом может работать нормально: иногда хостинг или защита сайта блокирует запрос, который WordPress делает сам к себе.</p>
								<?php else : ?>
									<p><?php echo esc_html( sprintf( 'FZ-152 не смог автоматически открыть %d страниц. Остальные найденные проблемы ниже можно исправлять — они не зависят от этого сообщения.', $scan_failed_pages ) ); ?></p>
								<?php endif; ?>
								<p><strong>Что делать:</strong> нажмите «Проверить ещё раз». Если сообщение останется — ничего на сервере самостоятельно настраивать не нужно, просто пришлите это сообщение в поддержку FZ-152.</p>
								<?php $rescan_url = wp_nonce_url( admin_url( 'admin-post.php?action=f152_rescan_site' ), 'f152_rescan_site' ); ?>
								<p><a class="button button-secondary" href="<?php echo esc_url( $rescan_url ); ?>">Проверить ещё раз</a></p>
								<?php if ( ! empty( $home_error ) || ! empty( $failed_page_details ) ) : ?>
									<details style="margin-top:8px;">
										<summary>Данные для поддержки</summary>
										<?php if ( ! empty( $home_error ) ) : ?>
											<p class="f152-audit-item__meta"><strong>Главная страница:</strong> <?php
											$parts = [];
											if ( ! empty( $home_error['http_code'] ) ) $parts[] = 'HTTP ' . (int) $home_error['http_code'];
											if ( ! empty( $home_error['code'] ) ) $parts[] = (string) $home_error['code'];
											if ( ! empty( $home_error['message'] ) ) $parts[] = (string) $home_error['message'];
											echo esc_html( implode( ' — ', $parts ) );
											?></p>
										<?php endif; ?>
										<?php foreach ( array_slice( $failed_page_details, 0, 3 ) as $failed_page ) : ?>
											<?php if ( ! is_array( $failed_page ) ) continue; ?>
											<?php
											$page_parts = [];
											if ( ! empty( $failed_page['status_code'] ) ) $page_parts[] = 'HTTP ' . (int) $failed_page['status_code'];
											if ( ! empty( $failed_page['error_code'] ) ) $page_parts[] = (string) $failed_page['error_code'];
											if ( ! empty( $failed_page['error_message'] ) ) $page_parts[] = (string) $failed_page['error_message'];
											?>
											<p class="f152-audit-item__meta"><strong><?php echo esc_html( (string) ( $failed_page['title'] ?? 'Страница' ) ); ?>:</strong> <?php echo esc_html( implode( ' — ', $page_parts ) ); ?></p>
										<?php endforeach; ?>
									</details>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php foreach ( $detected_plugins as $detected_plugin ) : ?>
						<div class="f152-audit-item">
							<div class="f152-audit-item__icon"><span class="dashicons dashicons-forms" aria-hidden="true"></span></div>
							<div class="f152-audit-item__body">
								<div class="f152-audit-item__title"><strong><?php echo esc_html( $detected_plugin['name'] ); ?></strong><span class="f152-status-chip f152-status-chip--attention">Нужно настроить</span></div>
								<p>На сайте есть эта форма, но бесплатный FZ-152 не умеет автоматически добавлять в неё согласие и записывать его в журнал. Если посетители отправляют через форму имя, телефон, email или другие данные — добавьте чекбокс согласия вручную. Возможности автоматической интеграции собраны в одном блоке Pro ниже.</p>
							</div>
						</div>
					<?php endforeach; ?>

					<?php foreach ( $tracker_issues as $tracker_issue ) : ?>
						<div class="f152-audit-item">
							<div class="f152-audit-item__icon"><span class="dashicons dashicons-search" aria-hidden="true"></span></div>
							<div class="f152-audit-item__body">
								<div class="f152-audit-item__title"><strong><?php echo esc_html( $tracker_issue['label'] ); ?></strong><span class="f152-status-chip f152-status-chip--attention">Нужно исправить</span></div>
								<p><?php echo esc_html( $tracker_issue['message'] ); ?></p>
								<?php if ( ! empty( $tracker_issue['ids'] ) ) : ?>
									<p class="f152-audit-item__meta">ID сервиса: <?php echo esc_html( implode( ', ', array_slice( $tracker_issue['ids'], 0, 3 ) ) ); ?></p>
								<?php endif; ?>
								<?php if ( ! empty( $tracker_issue['locations'] ) && is_array( $tracker_issue['locations'] ) ) : ?>
									<?php
									$location_labels = [];
									foreach ( array_slice( $tracker_issue['locations'], 0, 3 ) as $location ) {
										if ( ! is_array( $location ) ) continue;
										$location_labels[] = ! empty( $location['title'] ) ? (string) $location['title'] : (string) ( $location['url'] ?? '' );
									}
									?>
									<?php if ( ! empty( array_filter( $location_labels ) ) ) : ?><p class="f152-audit-item__meta">Страница: <?php echo esc_html( implode( ', ', array_filter( $location_labels ) ) ); ?></p><?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>

					<?php $show_pro_policy_action = false; ?>
					<?php foreach ( $policy_issues as $policy_issue ) : ?>
						<?php $policy_help = self::policy_issue_user_help( is_array( $policy_issue ) ? $policy_issue : [], $pro_active ); ?>
						<?php if ( 'pro_policy' === ( $policy_help['group_action'] ?? '' ) ) $show_pro_policy_action = true; ?>
						<div class="f152-audit-item">
							<div class="f152-audit-item__icon"><span class="dashicons dashicons-media-text" aria-hidden="true"></span></div>
							<div class="f152-audit-item__body">
								<div class="f152-audit-item__title"><strong><?php echo esc_html( $policy_issue['label'] ); ?></strong><span class="f152-status-chip f152-status-chip--attention"><?php echo esc_html( (string) ( $policy_help['chip'] ?? 'Нужно добавить в политику' ) ); ?></span></div>
								<p><?php echo esc_html( (string) $policy_help['message'] ); ?></p>
								<?php if ( ! empty( $policy_help['url'] ) ) : ?>
									<p><a class="button button-secondary" href="<?php echo esc_url( (string) $policy_help['url'] ); ?>"<?php if ( ! empty( $policy_help['external'] ) ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( (string) $policy_help['button'] ); ?></a></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $pro_active && $show_pro_policy_action ) : ?>
				<div class="f152-audit-pro-cta">
					<div>
						<strong>Тексты Pro требуют настройки</strong>
						<p>Все отмеченные выше сервисы настраиваются в одном месте. Проверьте, какие из них реально используются, отметьте их в блоке Pro и сохраните — политика обновится одним действием.</p>
					</div>
					<a class="button button-primary" href="<?php echo esc_url( self::subtab_url( 'policy_pd' ) ); ?>">Открыть сервисы и тексты Pro</a>
				</div>
				<?php endif; ?>

				<?php if ( ! $pro_active && $audit_count > 0 ) : ?>
				<div class="f152-audit-pro-cta">
					<div>
						<strong>FZ-152 RF Pro может исправить это автоматически</strong>
						<p>Интеграции с другими плагинами, управление трекерами и внешними сервисами, автоматическое добавление недостающих текстов в политики — всё это Pro-версия плагина может настроить и дальше поддерживать автоматически за вас.</p>
					</div>
					<a class="button button-primary" href="https://kotikblog.ru/product/fz-152-rf-pro/?utm_source=free_plugin&amp;utm_medium=audit_summary&amp;utm_campaign=pro_upgrade" target="_blank" rel="noopener noreferrer">Подробнее о FZ-152 Pro</a>
				</div>
				<?php endif; ?>
			</section>

			<p class="f152-onboarding__legal-note">Если у вас есть юрист, передайте ему тексты политик и согласий для проверки и адаптации под ваш бизнес.</p>
		</div>

		<div class="f152-settings-stack">
			<section class="f152-settings-card">
				<div class="f152-settings-card__header">
					<div><span class="f152-card-eyebrow">Реквизиты</span><h2>Данные сайта</h2><p>Эти значения используются в шаблонах политик и согласий при создании юридических страниц.</p></div>
				</div>
				<div class="f152-settings-card__body">
					<div class="f152-grid">
						<div class="f152-field"><label for="f152_site_url">URL сайта</label><input type="url" class="regular-text" id="f152_site_url" name="f152_site_url" value="<?php echo esc_attr( get_option('f152_site_url', home_url('/')) ); ?>"></div>
						<div class="f152-field"><label for="f152_company_name">Название компании / ИП / ФЛ</label><input type="text" class="regular-text" id="f152_company_name" name="f152_company_name" value="<?php echo esc_attr( get_option('f152_company_name', '') ); ?>"></div>
						<div class="f152-field"><label for="f152_company_inn">ИНН (необязательно)</label><input type="text" class="regular-text" id="f152_company_inn" name="f152_company_inn" value="<?php echo esc_attr( get_option('f152_company_inn', '') ); ?>"><small>Если заполнен, ИНН будет указан в создаваемых политиках и согласиях. Если оставить поле пустым, шаблоны сформируются без него.</small></div>
						<div class="f152-field"><label for="f152_company_email">Email ответственного</label><input type="email" class="regular-text" id="f152_company_email" name="f152_company_email" value="<?php echo esc_attr( get_option('f152_company_email', '') ); ?>"></div>
					</div>
				</div>
			</section>


			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Политики и согласия</span><h2>Юридические страницы</h2><p>Ссылки используются в баннере, чекбоксах и шаблонах политик и согласий.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="f152-grid">
						<div class="f152-field"><label for="f152_link_policy_pd">Политика обработки ПД (URL)</label><input type="url" class="regular-text" id="f152_link_policy_pd" name="f152_link_policy_pd" value="<?php echo esc_attr( get_option('f152_link_policy_pd', '') ); ?>"></div>
						<div class="f152-field"><label for="f152_link_consent_pd">Согласие на обработку ПД (URL)</label><input type="url" class="regular-text" id="f152_link_consent_pd" name="f152_link_consent_pd" value="<?php echo esc_attr( get_option('f152_link_consent_pd', '') ); ?>"></div>
						<div class="f152-field"><label for="f152_link_policy_cookie">Политика использования cookie (URL)</label><input type="url" class="regular-text" id="f152_link_policy_cookie" name="f152_link_policy_cookie" value="<?php echo esc_attr( get_option('f152_link_policy_cookie', '') ); ?>"></div>
						<div class="f152-field"><label for="f152_link_consent_marketing">Согласие на рассылку (URL)</label><input type="url" class="regular-text" id="f152_link_consent_marketing" name="f152_link_consent_marketing" value="<?php echo esc_attr( get_option('f152_link_consent_marketing', '') ); ?>"></div>
						<div class="f152-field"><label for="f152_policy_version">Версия политики персональных данных</label><input type="text" class="regular-text" id="f152_policy_version" name="f152_policy_version" value="<?php echo esc_attr( get_option('f152_policy_version', '') ); ?>" placeholder="1.0"><small>Версия сохраняется в журнале. После её изменения ранее сохранённый выбор cookie считается устаревшим и баннер показывается снова. При наличии кэша/CDN очистите его.</small></div>
						<div class="f152-field f152-field--full f152-create-pages-field"><label>Действия со страницами</label><?php self::render_create_pages_button(); ?></div>
					</div>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Cookie</span><h2>Баннер и поведение</h2><p>Основной текст баннера, кнопка изменения решения и область действия cookie.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="f152-field f152-field--full">
						<label for="f152_banner_text">Текст строки снизу сайта</label>
						<textarea class="large-text code" id="f152_banner_text" name="f152_banner_text"><?php echo esc_textarea( get_option('f152_banner_text', '') ); ?></textarea>
						<small>В тексте можно использовать кнопки баннера: <code>[f152_btn_accept]</code> <code>[f152_btn_settings]</code> <code>[f152_btn_reject]</code>. Если какая-то кнопка не нужна — просто удалите её шорткод из текста.</small>
					</div>
					<div class="f152-grid">
						<div class="f152-field">
							<label for="f152_btn_settings_view">Как показывать <code>[f152_btn_settings]</code></label>
							<select id="f152_btn_settings_view" name="f152_btn_settings_view">
								<option value="button" <?php selected( get_option('f152_btn_settings_view', 'button'), 'button' ); ?>>Кнопка</option>
								<option value="link" <?php selected( get_option('f152_btn_settings_view', 'button'), 'link' ); ?>>Ссылка</option>
							</select>
							<small><code>[f152_btn_settings]</code> выводит кнопку или ссылку «Настроить», чтобы посетитель мог повторно открыть окно настроек cookie. Внутри самого баннера «Настроить» всегда остаётся кнопкой.</small>
						</div>
						<div class="f152-field"><label for="f152_cookie_domain">Cookie для поддоменов</label><input type="text" class="regular-text" id="f152_cookie_domain" name="f152_cookie_domain" value="<?php echo esc_attr( get_option('f152_cookie_domain', '') ); ?>" placeholder=".example.ru"><small>Оставьте пустым для текущего хоста или укажите <code>.example.ru</code> для всех поддоменов.</small></div>
					</div>
					<details class="f152-settings-help">
						<summary>Как вставить кнопку изменения решения на сайт</summary>
						<div class="f152-macros">
							<p><strong>Шорткод:</strong> <code>[f152_btn_settings]</code></p>
							<p><strong>PHP для темы:</strong> <code>&lt;?php echo do_shortcode('[f152_btn_settings]'); ?&gt;</code></p>
							<p><strong>Кнопки баннера:</strong> <code>[f152_btn_accept]</code> <code>[f152_btn_settings]</code> <code>[f152_btn_reject]</code></p>
						</div>
					</details>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Тексты</span><h2>Окно настроек cookie</h2><p>Тексты, которые пользователь видит при открытии персональных настроек cookie.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="f152-field f152-field--full"><label for="f152_popup_upper">Верхний текст (поддерживаются ссылки)</label><textarea class="large-text code" id="f152_popup_upper" name="f152_popup_upper"><?php echo esc_textarea( get_option('f152_popup_upper', Templates::default_for_option( 'f152_popup_upper' )) ); ?></textarea></div>
					<div class="f152-grid">
						<div class="f152-field"><label for="f152_popup_func">Функциональные / технические</label><textarea class="large-text code" id="f152_popup_func" name="f152_popup_func"><?php echo esc_textarea( get_option('f152_popup_func', Templates::default_for_option( 'f152_popup_func' )) ); ?></textarea></div>
						<div class="f152-field"><label for="f152_popup_anal">Аналитические</label><textarea class="large-text code" id="f152_popup_anal" name="f152_popup_anal"><?php echo esc_textarea( get_option('f152_popup_anal', Templates::default_for_option( 'f152_popup_anal' )) ); ?></textarea></div>
					</div>
					<div class="f152-field f152-field--full"><label for="f152_popup_mark">Рекламные / маркетинговые</label><textarea class="large-text code" id="f152_popup_mark" name="f152_popup_mark"><?php echo esc_textarea( get_option('f152_popup_mark', Templates::default_for_option( 'f152_popup_mark' )) ); ?></textarea></div>
				</div>
			</section>


			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Формы</span><h2>Чекбоксы согласия</h2><p>Встроенные места WordPress и WooCommerce можно включать независимо друг от друга.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="f152-grid f152-consent-grid">
						<div class="f152-field f152-consent-field"><label><input type="checkbox" name="f152_comment_enable" value="1" <?php checked( get_option('f152_comment_enable', 1) ); ?>> Комментарии WordPress</label><textarea class="large-text code" name="f152_comment_text" placeholder="Текст рядом с чекбоксом (поддерживаются ссылки)"><?php echo esc_textarea( get_option('f152_comment_text', Helpers::default_personal_consent_text()) ); ?></textarea></div>
						<div class="f152-field f152-consent-field"><label><input type="checkbox" name="f152_reviews_enable" value="1" <?php checked( get_option('f152_reviews_enable', 1) ); ?>> Отзывы WooCommerce</label><textarea class="large-text code" name="f152_reviews_text" placeholder="Текст рядом с чекбоксом (поддерживаются ссылки)"><?php echo esc_textarea( get_option('f152_reviews_text', Helpers::default_personal_consent_text()) ); ?></textarea></div>
						<div class="f152-field f152-consent-field"><label><input type="checkbox" name="f152_checkout_enable" value="1" <?php checked( get_option('f152_checkout_enable', 1) ); ?>> Оформление заказа WooCommerce</label><textarea class="large-text code" name="f152_checkout_text" placeholder="Текст рядом с чекбоксом (поддерживаются ссылки)"><?php echo esc_textarea( get_option('f152_checkout_text', Helpers::default_personal_consent_text()) ); ?></textarea></div>
						<div class="f152-field f152-consent-field"><label><input type="checkbox" name="f152_register_enable" value="1" <?php checked( get_option('f152_register_enable', 1) ); ?>> Регистрация WooCommerce / «Мой аккаунт»</label><textarea class="large-text code" name="f152_register_text" placeholder="Текст рядом с чекбоксом (поддерживаются ссылки)"><?php echo esc_textarea( get_option('f152_register_text', Helpers::default_personal_consent_text()) ); ?></textarea></div>
					</div>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Дизайн</span><h2>Оформление cookie-баннера</h2><p>Тема, цвета, размеры текста и кнопок. Предпросмотр обновляется при изменении полей.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="f152-appearance-group">
						<h3>Тема и основные цвета</h3>
						<div class="f152-grid f152-grid--colors">
							<div class="f152-field"><label>Тема</label><div class="f152-radio-row"><label><input type="radio" name="f152_theme" value="light" <?php checked( get_option('f152_theme','light') === 'light' ); ?>> Светлая</label><label><input type="radio" name="f152_theme" value="dark" <?php checked( get_option('f152_theme','light') === 'dark' ); ?>> Тёмная</label></div></div>
							<?php self::render_color_field('f152_color_bg', 'Цвет фона (баннер/попап)', '#ffffff'); ?>
							<?php self::render_color_field('f152_color_text', 'Цвет текста', '#111111'); ?>
							<?php self::render_color_field('f152_color_link', 'Цвет ссылок', '#1e73be'); ?>
						</div>
					</div>

					<div class="f152-appearance-group">
						<h3>Размеры</h3>
						<div class="f152-grid">
							<div class="f152-field"><label for="f152_font_size_text">Размер текста баннера, px</label><input type="number" class="regular-text" id="f152_font_size_text" name="f152_font_size_text" value="<?php echo esc_attr( get_option('f152_font_size_text', '') ); ?>" placeholder="13" min="8" max="32" step="1"><small>Пусто — 13px.</small></div>
							<div class="f152-field"><label for="f152_font_size_btn">Размер шрифта кнопок, px</label><input type="number" class="regular-text" id="f152_font_size_btn" name="f152_font_size_btn" value="<?php echo esc_attr( get_option('f152_font_size_btn', '') ); ?>" placeholder="14" min="8" max="32" step="1"><small>Пусто — 14px.</small></div>
							<div class="f152-field"><label for="f152_btn_radius">Скругление кнопок, px</label><input type="number" class="regular-text" id="f152_btn_radius" name="f152_btn_radius" value="<?php echo esc_attr( get_option('f152_btn_radius', '') ); ?>" placeholder="8" min="0" max="32" step="1"><small>Пусто — значение темы.</small></div>
						</div>
					</div>

					<div class="f152-appearance-group">
						<h3>Цвета кнопок</h3>
						<div class="f152-grid f152-grid--colors">
							<?php self::render_color_field('f152_color_btn_accept_bg', 'Принять — фон', '#27ae60'); ?>
							<?php self::render_color_field('f152_color_btn_accept_text', 'Принять — текст', '#ffffff'); ?>
							<?php self::render_color_field('f152_color_btn_settings_bg', 'Настроить — фон', '#f3f4f6', 'Если задать прозрачный цвет или снизить прозрачность до 0%, кнопка станет вторичной с обводкой.'); ?>
							<?php self::render_color_field('f152_color_btn_settings_text', 'Настроить — текст', '#1e73be'); ?>
							<?php self::render_color_field('f152_color_btn_reject_bg', 'Отклонить — фон', '#b32424'); ?>
							<?php self::render_color_field('f152_color_btn_reject_text', 'Отклонить — текст', '#ffffff'); ?>
						</div>
					</div>

					<?php
					$popup_defaults = [
						'upper' => \F152\Templates::default_for_option('f152_popup_upper'),
						'func'  => \F152\Templates::default_for_option('f152_popup_func'),
						'anal'  => \F152\Templates::default_for_option('f152_popup_anal'),
						'mark'  => \F152\Templates::default_for_option('f152_popup_mark'),
					];
					$theme_mode = self::sanitize_theme_mode( (string) get_option('f152_theme', 'light') );
					?>
					<div class="f152-preview" data-f152-preview
						data-f152-default-banner="<?php echo esc_attr( Helpers::default_banner_text() ); ?>"
						data-f152-default-upper="<?php echo esc_attr( (string) $popup_defaults['upper'] ); ?>"
						data-f152-default-func="<?php echo esc_attr( (string) $popup_defaults['func'] ); ?>"
						data-f152-default-anal="<?php echo esc_attr( (string) $popup_defaults['anal'] ); ?>"
						data-f152-default-mark="<?php echo esc_attr( (string) $popup_defaults['mark'] ); ?>"
						data-f152-label-accept="<?php echo esc_attr__( 'Принять', 'fz-152-rf' ); ?>"
						data-f152-label-settings="<?php echo esc_attr__( 'Настроить', 'fz-152-rf' ); ?>"
						data-f152-label-reject="<?php echo esc_attr__( 'Отклонить', 'fz-152-rf' ); ?>"
						data-f152-label-save="<?php echo esc_attr__( 'Сохранить', 'fz-152-rf' ); ?>"
						data-f152-label-close="<?php echo esc_attr__( 'Закрыть', 'fz-152-rf' ); ?>">
						<div class="f152-preview__header"><div><strong>Предпросмотр</strong><p class="f152-preview__hint">Обновляется при изменении настроек выше. Мобильный вариант проверяйте на самом сайте.</p></div></div>
						<div class="f152-preview__frames"><div class="f152-preview__frame" data-f152-preview-frame="desktop"><div class="f152-preview__viewport" data-f152-theme="<?php echo esc_attr( $theme_mode ); ?>"><div class="f152-preview__banner" data-f152-preview-banner></div></div></div></div>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

        private static function render_color_field(string $option, string $label, string $placeholder = '', string $description = '') : void {
                $value = get_option($option, '');
                $placeholder_value = $placeholder !== '' ? $placeholder : '#000000';
                $default_color = self::color_picker_fallback($placeholder_value);
                ?>
                <div class="f152-field f152-color-field">
                        <label for="<?php echo esc_attr($option); ?>"><?php echo esc_html($label); ?></label>
                        <div class="f152-color-control" data-f152-color-default="<?php echo esc_attr($default_color); ?>">
                                <div class="f152-color-control__row">
                                        <input type="text" class="regular-text f152-color-input" id="<?php echo esc_attr($option); ?>" name="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr($placeholder_value); ?>">
                                        <button type="button" class="button f152-color-button" aria-label="Открыть палитру">
                                                <span class="dashicons dashicons-art" aria-hidden="true"></span>
                                        </button>
                                        <input type="color" class="f152-color-picker" value="#000000" aria-hidden="true">
                                </div>
                                <div class="f152-color-opacity">
                                        <label>
                                                <span class="f152-opacity__label">Прозрачность: <output class="f152-opacity__value" aria-live="polite">100%</output></span>
                                                <input type="range" min="0" max="100" step="1" value="100" class="f152-opacity__range">
                                        </label>
                                </div>
                        </div>
                        <?php if ( $description !== '' ) : ?>
                                <small><?php echo esc_html($description); ?></small>
                        <?php endif; ?>
                </div>
                <?php
        }

        private static function color_picker_fallback(string $placeholder) : string {
                if ( preg_match('/#([0-9a-fA-F]{3}){1,2}/', $placeholder, $m) ) {
                        return $m[0];
                }

                if ( stripos($placeholder, 'transparent') !== false ) {
                        return 'transparent';
                }

                return '#000000';
        }
		
	private static function render_tab_texts() : void {
		$map = [
			'policy_pd'         => ['opt' => 'f152_text_policy_pd',        'label' => 'Политика обработки ПД',         'full' => 'Политика обработки персональных данных'],
			'consent_pd'        => ['opt' => 'f152_text_consent_pd',       'label' => 'Согласие на обработку ПД',      'full' => 'Согласие на обработку персональных данных'],
			'policy_cookie'     => ['opt' => 'f152_text_policy_cookie',    'label' => 'Политика cookie',               'full' => 'Политика использования cookie'],
			'consent_marketing' => ['opt' => 'f152_text_consent_marketing','label' => 'Согласие на рассылку',          'full' => 'Согласие на рассылку'],
		];

		$sub_raw = filter_input( INPUT_GET, 'subtab', FILTER_UNSAFE_RAW );
		$subtab  = is_string( $sub_raw ) ? sanitize_key( wp_unslash( $sub_raw ) ) : 'policy_pd';
		if ( ! isset( $map[ $subtab ] ) ) {
			$subtab = 'policy_pd';
		}

		?>
		<h2 class="nav-tab-wrapper f152-subtabs">
			<?php foreach ( $map as $key => $info ) : ?>
				<a href="<?php echo esc_url( self::subtab_url($key) ); ?>" class="nav-tab <?php echo esc_attr( $subtab === $key ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $info['label'] ); ?></a>
			<?php endforeach; ?>
		</h2>
		<?php self::render_replace_configured_pages_button( false, 'texts' ); ?>
		<?php

		if ( in_array( $subtab, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			do_action( 'f152_render_policy_sync_panel', $subtab );
			if ( ! defined( 'F152_PRO_VERSION' ) ) {
				self::render_manual_policy_guidance( $subtab );
			}
		}

		self::render_text_block( $subtab, $map );
	}


	private static function render_manual_policy_guidance( string $document ) : void {
		if ( ! current_user_can( 'manage_options' )
			|| ! class_exists( '\F152\ServiceInventory' )
			|| ! class_exists( '\F152\ServiceCatalog' ) ) {
			return;
		}

		$document = sanitize_key( $document );
		if ( ! in_array( $document, [ 'policy_pd', 'policy_cookie' ], true ) ) {
			return;
		}

		$issues = \F152\ServiceInventory::policy_review_items();
		if ( empty( $issues ) || ! is_array( $issues ) ) {
			return;
		}

		$relevant = [];
		foreach ( $issues as $service_id => $issue ) {
			$service_id = sanitize_key( (string) $service_id );
			$issue = is_array( $issue ) ? $issue : [];
			$definition = \F152\ServiceCatalog::get( $service_id );
			if ( empty( $definition ) ) {
				continue;
			}

			$documents = isset( $definition['policy_documents'] ) && is_array( $definition['policy_documents'] )
				? array_values( array_filter( array_map( 'sanitize_key', $definition['policy_documents'] ) ) )
				: [];
			$mode = sanitize_key( (string) ( $definition['policy_mode'] ?? 'manual_review' ) );
			$owner = sanitize_key( (string) ( $definition['policy_owner'] ?? '' ) );
			if ( ! in_array( $document, $documents, true ) || 'container_info' === $mode || 'free' === $owner ) {
				continue;
			}

			$issue['_definition'] = $definition;
			$relevant[ $service_id ] = $issue;
		}

		if ( empty( $relevant ) ) {
			return;
		}

		$document_label = 'policy_cookie' === $document
			? __( 'политику cookie', 'fz-152-rf' )
			: __( 'политику обработки персональных данных', 'fz-152-rf' );
		$pro_items = [];
		?>
		<section class="f152-settings-card f152-manual-policy-card" aria-labelledby="f152-manual-policy-title-<?php echo esc_attr( $document ); ?>">
			<div class="f152-settings-card__header">
				<div>
					<span class="f152-card-eyebrow"><?php echo esc_html__( 'Проверка политик', 'fz-152-rf' ); ?></span>
					<h2 id="f152-manual-policy-title-<?php echo esc_attr( $document ); ?>"><?php echo esc_html__( 'Что нужно проверить или добавить вручную', 'fz-152-rf' ); ?></h2>
					<p><?php
						/* translators: %s: human-readable policy name, for example cookie policy or personal data processing policy. */
						echo esc_html( sprintf( __( 'Сканер нашёл внешние сервисы, для которых в %s не удалось подтвердить нужное описание. Бесплатная версия ничего не дописывает за вас: ниже указано, что нужно проверить и какие сведения следует отразить вручную.', 'fz-152-rf' ), $document_label ) );
					?></p>
				</div>
			</div>
			<div class="f152-settings-card__body">
				<div class="f152-manual-policy-list">
					<?php foreach ( $relevant as $service_id => $issue ) : ?>
						<?php
						$definition = isset( $issue['_definition'] ) && is_array( $issue['_definition'] ) ? $issue['_definition'] : [];
						$label = sanitize_text_field( (string) ( $issue['label'] ?? $definition['label'] ?? $service_id ) );
						$mode = sanitize_key( (string) ( $definition['policy_mode'] ?? 'manual_review' ) );
						$owner = sanitize_key( (string) ( $definition['policy_owner'] ?? '' ) );
						$category = sanitize_key( (string) ( $definition['category'] ?? ( $issue['category'] ?? '' ) ) );
						$detection_basis = sanitize_key( (string) ( $issue['detection_basis'] ?? 'confirmed' ) );
						$reflection = sanitize_key( (string) ( $issue['reflection_status'] ?? 'missing' ) );
						$confirmed = ! empty( $issue['confirmed'] ) || 'confirmed' === $detection_basis;
						$is_candidate = in_array( $detection_basis, [ 'active_plugin', 'theme_code' ], true );
						$is_data_transfer = 'data_transfer' === $category;
						if ( 'managed' === $mode && 'pro' === $owner ) {
							$pro_items[ $service_id ] = [
								'label' => $label,
								'confirmed' => $confirmed,
								'data_transfer' => $is_data_transfer,
							];
						}
						$chip = 'unreadable' === $reflection
							? __( 'Проверьте вручную', 'fz-152-rf' )
							: ( $is_candidate ? __( 'Сначала подтвердите', 'fz-152-rf' ) : __( 'Нужно добавить', 'fz-152-rf' ) );
						?>
						<div class="f152-manual-policy-item">
							<div class="f152-manual-policy-item__heading">
								<strong><?php echo esc_html( $label ); ?></strong>
								<span class="f152-status-chip f152-status-chip--attention"><?php echo esc_html( $chip ); ?></span>
							</div>

							<?php if ( 'unreadable' === $reflection ) : ?>
								<p><?php
									/* translators: 1: human-readable policy name, 2: external service name. */
									echo esc_html( sprintf( __( 'FZ-152 не смог прочитать указанную %1$s и поэтому не может подтвердить, есть ли там описание «%2$s». Откройте вашу реальную страницу политики и проверьте её вручную.', 'fz-152-rf' ), $document_label, $label ) );
								?></p>
							<?php elseif ( $is_candidate ) : ?>
								<p><?php
									/* translators: %s: external service name. */
									echo esc_html( sprintf( __( 'Сканер нашёл только признак интеграции с «%s», но не может доказать, что данные действительно передаются. Сначала проверьте настройки этой интеграции. Если она реально используется — добавьте описание ниже.', 'fz-152-rf' ), $label ) );
								?></p>
							<?php else : ?>
								<p><?php
									/* translators: %s: external service name. */
									echo esc_html( sprintf( __( '«%s» обнаружен на сайте, а нужное описание в выбранной политике не найдено.', 'fz-152-rf' ), $label ) );
								?></p>
							<?php endif; ?>

							<div class="f152-manual-policy-instruction">
								<p><strong><?php echo esc_html__( 'Что нужно отразить вручную:', 'fz-152-rf' ); ?></strong></p>
								<?php if ( 'policy_cookie' === $document ) : ?>
									<p><?php
										/* translators: %s: external service name. */
										echo esc_html( sprintf( __( 'Добавьте в политику cookie описание использования «%s»: назначение сервиса, используемые cookie или технические идентификаторы, условия его загрузки относительно согласия пользователя и ссылку на сведения самого сервиса о конфиденциальности.', 'fz-152-rf' ), $label ) );
									?></p>
								<?php elseif ( $is_data_transfer ) : ?>
									<p><?php
										/* translators: %s: external CRM or accounting service name. */
										echo esc_html( sprintf( __( 'Если интеграция «%s» действительно передаёт данные, дополните политику обработки персональных данных: укажите конкретную цель обработки, категорию и перечень передаваемых персональных данных, категории субъектов, правовое основание, получателя или лицо, обрабатывающее данные по поручению (если применимо), перечень действий с данными, способ и сроки обработки и хранения, порядок уничтожения, а также ссылку на официальные условия сервиса.', 'fz-152-rf' ), $label ) );
									?></p>
								<?php else : ?>
									<p><?php
										/* translators: %s: external service name. */
										echo esc_html( sprintf( __( 'Дополните политику обработки персональных данных описанием использования «%s»: укажите цель обработки, категорию и перечень данных, категории субъектов, правовое основание, действия с данными, способ и сроки обработки и хранения, порядок уничтожения, сведения о внешнем получателе данных (если он есть) и ссылку на официальные условия сервиса.', 'fz-152-rf' ), $label ) );
									?></p>
								<?php endif; ?>
								<p class="description"><?php echo esc_html__( 'Готовые управляемые формулировки для поддерживаемых сервисов доступны в FZ-152 RF Pro.', 'fz-152-rf' ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php if ( ! empty( $pro_items ) ) : ?>
		<section class="f152-settings-card f152-policy-pro-preview" aria-labelledby="f152-policy-pro-title-<?php echo esc_attr( $document ); ?>">
			<div class="f152-settings-card__header">
				<div>
					<span class="f152-card-eyebrow"><?php echo esc_html__( 'FZ-152 RF Pro', 'fz-152-rf' ); ?></span>
					<h2 id="f152-policy-pro-title-<?php echo esc_attr( $document ); ?>"><?php echo esc_html__( 'Pro может сделать это за вас', 'fz-152-rf' ); ?></h2>
					<p><?php echo esc_html__( 'Для поддерживаемых сервисов Pro добавляет выбранные блоки в политики, созданные FZ-152, и дальше обновляет их автоматически при изменении конфигурации сайта.', 'fz-152-rf' ); ?></p>
				</div>
			</div>
			<div class="f152-settings-card__body">
				<div class="f152-policy-pro-checklist" aria-label="<?php echo esc_attr__( 'Предпросмотр возможностей Pro', 'fz-152-rf' ); ?>">
					<?php foreach ( $pro_items as $service_id => $item ) : ?>
						<label>
							<input type="checkbox" disabled <?php checked( ! empty( $item['confirmed'] ) ); ?>>
							<span><strong><?php echo esc_html( (string) $item['label'] ); ?></strong> — <?php echo esc_html( ! empty( $item['data_transfer'] ) && empty( $item['confirmed'] ) ? __( 'после вашей проверки подтвердить использование и добавить блок', 'fz-152-rf' ) : __( 'добавить недостающий блок и поддерживать его автоматически', 'fz-152-rf' ) ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="f152-policy-pro-preview__note"><?php echo esc_html__( 'Это только предпросмотр: бесплатная версия ничего не меняет автоматически.', 'fz-152-rf' ); ?></p>
				<p><a class="button button-primary" href="https://kotikblog.ru/product/fz-152-rf-pro/?utm_source=free_plugin&amp;utm_medium=texts_policy_preview&amp;utm_campaign=pro_upgrade" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Подробнее о FZ-152 Pro', 'fz-152-rf' ); ?></a></p>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}

	private static function render_text_block(string $tab, array $map) : void {
		if ( ! isset($map[$tab]) ) return;

		$opt   = $map[$tab]['opt'];
		$label = $map[$tab]['full'];
		$raw = class_exists('\\F152\\Templates') ? \F152\Templates::default_for_option($opt) : '';
		$final = Helpers::replace_common_macros( (string) $raw );

		?>
	               <h2><?php echo esc_html( $label ); ?></h2>
	               <p>Содержимое ниже не хранится в БД. Это готовый HTML из файла шаблона с подстановкой ваших данных. Скопируйте и вставьте на страницу сайта.</p>

	               <div class="f152-row-actions">
	                       <button type="button" class="button button-primary f152-copy-btn" data-f152-copy-target="f152-copy-src" data-f152-copy-status="f152-copy-status" data-f152-copy-success="Скопировано" data-f152-copy-error="Не удалось скопировать">Скопировать</button>
	                       <span id="f152-copy-status" class="f152-copy-status" aria-live="polite"></span>
	               </div>

	               <div class="f152-copybox">
	                       <textarea readonly id="f152-copy-src" class="f152-copybox__textarea"><?php echo esc_textarea($final); ?></textarea>
	               </div>
	               <?php
		if ( class_exists('\\F152\\Templates') ) {
			$path = \F152\Templates::path_for_option($opt);
			if ( $path ) {
				echo '<p><em>Источник файла: <code>' . esc_html( str_replace( rtrim(ABSPATH,'/\\'), '', $path ) ) . '</code></em></p>';
			}
		} else {
			echo '<p><em>Класс Templates не найден. Проверьте подключение includes/class-templates.php</em></p>';
		}
	}

	private static function subtab_url(string $subtab) : string {
		return add_query_arg([
			'page'   => 'f152',
			'tab'    => 'texts',
			'subtab' => $subtab,
		], admin_url('options-general.php'));
	}

	private static function render_tab_copyonly(string $tab) : void {
		$map = [
			'policy_pd'         => ['opt' => 'f152_text_policy_pd',        'label' => 'Политика обработки персональных данных'],
			'consent_pd'        => ['opt' => 'f152_text_consent_pd',       'label' => 'Согласие на обработку персональных данных'],
			'policy_cookie'     => ['opt' => 'f152_text_policy_cookie',    'label' => 'Политика использования cookie'],
			'consent_marketing' => ['opt' => 'f152_text_consent_marketing','label' => 'Согласие на рассылку'],
		];

		if ( ! isset($map[$tab]) ) return;

		$opt   = $map[$tab]['opt'];
		$label = $map[$tab]['label'];
		$raw = class_exists('\\F152\\Templates') ? \F152\Templates::default_for_option($opt) : '';
		$final = Helpers::replace_common_macros( (string) $raw );

		?>
                <h2><?php echo esc_html( $label ); ?></h2>
                <p>Содержимое ниже не хранится в БД. Это готовый HTML из файла шаблона с подстановкой ваших данных. Скопируйте и вставьте на страницу сайта.</p>

                <div class="f152-row-actions">
                        <button type="button" class="button button-primary f152-copy-btn" data-f152-copy-target="f152-copy-src" data-f152-copy-status="f152-copy-status" data-f152-copy-success="Скопировано" data-f152-copy-error="Не удалось скопировать">Скопировать</button>
                        <span id="f152-copy-status" class="f152-copy-status" aria-live="polite"></span>
                </div>

                <div class="f152-copybox">
                        <textarea readonly id="f152-copy-src" class="f152-copybox__textarea"><?php echo esc_textarea($final); ?></textarea>
                </div>
                <?php
		if ( class_exists('\\F152\\Templates') ) {
			$path = \F152\Templates::path_for_option($opt);
			if ( $path ) {
				echo '<p><em>Источник файла: <code>' . esc_html( str_replace( rtrim(ABSPATH,'/\\'), '', $path ) ) . '</code></em></p>';
			}
		} else {
			echo '<p><em>Класс Templates не найден. Проверьте подключение includes/class-templates.php</em></p>';
		}
	}
	private static function render_locked_integrations_tab() : void {
		$form_cards = [
			[ 'title' => 'Contact Form 7',        'desc' => 'Автоматический чекбокс согласия и логирование отправленных форм.' ],
			[ 'title' => 'WPForms',               'desc' => 'Добавление согласия и запись отправки в журнал согласий.' ],
			[ 'title' => 'Ninja Forms',           'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'Fluent Forms',          'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'Forminator',            'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'MetForm',               'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'Gravity Forms',         'desc' => 'Автоматический чекбокс согласия, серверная проверка и логирование отправок.' ],
			[ 'title' => 'wpDiscuz',              'desc' => 'Чекбокс согласия в комментариях wpDiscuz с логированием.' ],
			[ 'title' => 'Elementor Pro Forms',   'desc' => 'Поле согласия в Legacy-формах Elementor Pro.' ],
			[ 'title' => 'Elementor Atomic Forms','desc' => 'Поддержка нового формата Atomic Forms Elementor.' ],
			[ 'title' => 'Universal Forms',       'desc' => 'Подключение самописных и других HTML-форм через CSS-селекторы.' ],
		];

		$service_cards = [
			[ 'title' => 'Google Analytics 4',                  'desc' => 'Управление загрузкой счётчика в зависимости от выбора посетителя.' ],
			[ 'title' => 'Google Tag Manager',                  'desc' => 'Управление загрузкой контейнера GTM по выбранному режиму cookie.' ],
			[ 'title' => 'VK Реклама Pixel',                    'desc' => 'Управление загрузкой рекламного пикселя VK.' ],
			[ 'title' => 'Roistat',                              'desc' => 'Управление загрузкой аналитического счётчика Roistat по согласию посетителя.' ],
			[ 'title' => 'Цифровая культура (PRO.Культура.РФ)', 'desc' => 'Управление счётчиком culturaltracking.ru по аналитическому согласию.' ],
			[ 'title' => 'Google Maps',                         'desc' => 'Контроль встроенных карт до или после решения посетителя.' ],
			[ 'title' => '2ГИС',                                'desc' => 'Контроль встроенных карт и виджетов 2ГИС.' ],
			[ 'title' => 'VK Видео',                            'desc' => 'Контроль загрузки встроенных видео VK.' ],
			[ 'title' => 'RUTUBE',                              'desc' => 'Контроль загрузки встроенных роликов RUTUBE.' ],
			[ 'title' => 'YouTube',                             'desc' => 'Контроль загрузки стандартных iframe-плееров YouTube.' ],
			[ 'title' => 'Дзен Видео',                          'desc' => 'Контроль загрузки встроенных видео Дзена.' ],
			[ 'title' => 'Пользовательские сервисы',            'desc' => 'Собственный JavaScript-код для сервисов, у которых пока нет готовой интеграции.' ],
			[ 'title' => 'JivoSite (beta)',                     'desc' => 'Управление загрузкой онлайн-консультанта и локальной заглушкой до разрешения.' ],
		];

		$policy_cards = [
			[ 'title' => 'Автосинхронизация политик', 'desc' => 'Сверяет поддерживаемые сервисы с текстами политик FZ-152 и добавляет или обновляет выбранные Pro-блоки автоматически.' ],
			[ 'title' => 'amoCRM',                    'desc' => 'Управляемый блок о передаче данных из форм, заявок и заказов в amoCRM.' ],
			[ 'title' => 'Битрикс24',                 'desc' => 'Управляемый блок о передаче данных и обработке в Битрикс24.' ],
			[ 'title' => 'RetailCRM / Simla.com',     'desc' => 'Управляемый блок для клиентских и заказных данных, передаваемых в CRM.' ],
			[ 'title' => 'МойСклад',                  'desc' => 'Управляемый блок для данных клиентов и заказов, передаваемых в систему учёта.' ],
			[ 'title' => 'Roistat, VK Реклама, 2ГИС и другие', 'desc' => 'Автоматическое добавление и дальнейшее обновление юридических описаний поддерживаемых внешних сервисов.' ],
		];

		$buy_url = apply_filters( 'f152_pro_buy_url', 'https://kotikblog.ru/product/fz-152-rf-pro/' );
		?>
		<div class="f152-integrations-locked">
			<div class="f152-addon-intro">
				<span class="f152-card-eyebrow">FZ-152 RF Pro</span>
				<h2>Автоматизация согласий, сервисов и текстов политик</h2>
				<p>Pro добавляет интеграции с популярными формами, управляет дополнительными трекерами, картами и видео, а также помогает поддерживать тексты политик в соответствии с реально используемыми на сайте сервисами.</p>
			</div>

			<section class="f152-addon-section" aria-labelledby="f152-addon-forms-title">
				<div class="f152-addon-section__header">
					<h2 id="f152-addon-forms-title">Интеграции с формами</h2>
					<p>Чекбоксы согласия, проверка отправки и запись полученного согласия в журнал.</p>
				</div>
				<div class="f152-integrations-grid">
					<?php foreach ( $form_cards as $card ) : ?>
						<div class="f152-integrations-card f152-integrations-card--locked">
							<div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div>
							<div class="f152-integrations-card__body">
								<h3 class="f152-integrations-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
								<p class="f152-integrations-card__desc"><?php echo esc_html( $card['desc'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="f152-addon-section" aria-labelledby="f152-addon-services-title">
				<div class="f152-addon-section__header">
					<h2 id="f152-addon-services-title">Трекеры, карты, видео и внешние сервисы</h2>
					<p>Для поддерживаемых сервисов можно выбрать режим загрузки с учётом решения посетителя по cookie.</p>
				</div>
				<div class="f152-integrations-grid">
					<?php foreach ( $service_cards as $card ) : ?>
						<div class="f152-integrations-card f152-integrations-card--locked">
							<div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div>
							<div class="f152-integrations-card__body">
								<h3 class="f152-integrations-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
								<p class="f152-integrations-card__desc"><?php echo esc_html( $card['desc'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="f152-addon-section" aria-labelledby="f152-addon-policy-title">
				<div class="f152-addon-section__header">
					<h2 id="f152-addon-policy-title">Тексты политик и передача персональных данных</h2>
					<p>Сканер показывает расхождения и в бесплатной версии. Pro позволяет выбрать подтверждённые сервисы и автоматически добавить или обновить их управляемые блоки в политиках, созданных FZ-152.</p>
				</div>
				<div class="f152-integrations-grid">
					<?php foreach ( $policy_cards as $card ) : ?>
						<div class="f152-integrations-card f152-integrations-card--locked">
							<div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div>
							<div class="f152-integrations-card__body">
								<h3 class="f152-integrations-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
								<p class="f152-integrations-card__desc"><?php echo esc_html( $card['desc'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="f152-integrations-locked__cta">
				<?php if ( ! empty( $buy_url ) ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a>
				<?php else : ?>
					<span class="button button-primary disabled" aria-disabled="true">Подробнее о Pro</span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}



	private static function render_multisite_discount_card() : void {
		$buy_url = apply_filters(
			'f152_pro_buy_url',
			'https://kotikblog.ru/product/fz-152-rf-pro/?utm_source=free_plugin&utm_medium=addon_tab&utm_campaign=owner_multisite_discount'
		);
		?>
		<section class="f152-partner-card f152-multisite-discount-card" aria-labelledby="f152-multisite-discount-title">
			<div class="f152-partner-card__icon" aria-hidden="true"><span class="dashicons dashicons-admin-site-alt3"></span></div>
			<div class="f152-partner-card__content">
				<span class="f152-card-eyebrow">Для владельцев нескольких сайтов</span>
				<h2 id="f152-multisite-discount-title">Pro на нескольких своих сайтах — со скидкой на последующие лицензии</h2>
				<p>Если решите использовать FZ-152 RF Pro на нескольких своих сайтах, для второй и последующих лицензий действует скидка.</p>
				<?php if ( ! empty( $buy_url ) ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Перейти к FZ-152 RF Pro</a>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}


	private static function render_partner_program_card() : void {
		$partner_url = apply_filters(
			'f152_partner_program_url',
			'https://kotikblog.ru/fz152-partner/?utm_source=free_plugin&utm_medium=addon_tab&utm_campaign=partner_program'
		);
		?>
		<section class="f152-partner-card" aria-labelledby="f152-partner-title">
			<div class="f152-partner-card__icon" aria-hidden="true"><span class="dashicons dashicons-groups"></span></div>
			<div class="f152-partner-card__content">
				<span class="f152-card-eyebrow">Партнёрская программа для вебмастеров и агентств</span>
				<h2 id="f152-partner-title">Подключаете ФЗ-152 Pro клиентам?</h2>
				<p>Я сделал партнёрскую программу и готов дать вам скидку на все следующие лицензии, за то, что вы берете на себя его настройку и поддержку для клиентов.</p>
				<p class="f152-partner-card__note">*При оформлении лицензии на клиента, она будет закреплена за ним, а не за вами.</p>
				<?php if ( ! empty( $partner_url ) ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $partner_url ); ?>" target="_blank" rel="noopener noreferrer">Перейти в партнёрскую программу</a>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

		private static function render_review_cta() : void {
			$reviews_url = 'https://wordpress.org/support/plugin/fz-152-rf/reviews/#new-post';
			?>
			<aside class="f152-review-cta" aria-label="<?php echo esc_attr__( 'Отзыв о FZ-152 RF', 'fz-152-rf' ); ?>">
				<div class="f152-review-cta__copy">
					<span class="f152-review-cta__stars" aria-hidden="true">★★★★★</span>
					<strong><?php echo esc_html__( 'Нравится FZ-152 RF?', 'fz-152-rf' ); ?></strong>
					<span><?php echo esc_html__( 'Поддержите плагин отзывом на WordPress.org.', 'fz-152-rf' ); ?></span>
				</div>
				<a class="button button-primary f152-review-cta__button" href="<?php echo esc_url( $reviews_url ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="f152-review-cta__button-star" aria-hidden="true">★</span>
					<?php echo esc_html__( 'Оставить отзыв', 'fz-152-rf' ); ?>
				</a>
			</aside>
			<?php
		}

		private static function render_enable_toggle() : void {
		?>
		<form method="post" action="options.php" class="f152-enable-form">
			<?php settings_fields('f152_toggle'); ?>
			<label>
				<input type="checkbox" name="f152_enabled" value="1" <?php checked( get_option('f152_enabled', 1) ); ?>>
				<strong>Включить плагин</strong>
			</label>
			<?php submit_button('Сохранить', 'secondary', '', false); ?>
		</form>
		<?php
	}

        private static function tab_url(string $tab) : string {
                return add_query_arg([
                        'page' => 'f152',
                        'tab'  => $tab,
                ], admin_url('options-general.php'));
        }

        public static function get_metrika_behavior() : string {
                $raw = (string) get_option('f152_ym_behavior', '');

                if ($raw === '') {
                        return get_option('f152_ym_disable_on_reject', 0) ? 'disable_on_reject' : 'always';
                }

                return self::sanitize_metrika_mode($raw);
        }

        public static function get_yandex_maps_behavior() : string {
                return self::sanitize_metrika_mode(
                        (string) get_option('f152_yandex_maps_behavior', 'always')
                );
        }

        public static function sanitize_checkbox($val) : int {
                return empty($val) ? 0 : 1;
        }

	public static function sanitize_email_soft($val) : string {
		$val = trim((string)$val);
		return $val === '' ? '' : sanitize_email($val);
	}

	public static function sanitize_banner_text($val) : string {
		$val = (string) $val;
		$allowed = [
			'a'      => ['href'=>true,'title'=>true,'target'=>true,'rel'=>true],
			'br'     => [],
			'em'     => [],
			'strong' => [],
			'span'   => ['class'=>true],
		];
		return wp_kses($val, $allowed);
	}

	public static function sanitize_cookie_domain($val) : string {
		$val = trim((string)$val);
		if ($val === '') return '';
		$val = strtolower($val);
		if ( preg_match('~[^a-z0-9\.\-]~', $val) ) {
			return '';
		}
		return $val;
	}

        public static function sanitize_theme_mode($val) : string {
                return $val === 'dark' ? 'dark' : 'light';
        }

        public static function sanitize_metrika_mode($val) : string {
                $val = (string) $val;
                $allowed = ['always', 'disable_on_reject', 'require_accept'];

                return in_array($val, $allowed, true) ? $val : 'always';
        }

	public static function sanitize_settings_button_view($val) : string {
		$val = (string) $val;
		return $val === 'link' ? 'link' : 'button';
	}
	
        public static function sanitize_radius($val) : string {
                $val = trim( (string) $val );

                if ( $val === '' ) {
                        return '';
                }

                $number = (float) $val;

                if ( $number < 0 ) {
                        $number = 0;
                }

                if ( $number > 64 ) {
                        $number = 64;
                }

                return (string) $number;
        }

        public static function sanitize_font_size($val) : string {
                $val = trim( (string) $val );

                if ( $val === '' ) {
                        return '';
                }

                $number = (float) $val;

                if ( $number < 8 ) {
                        $number = 8;
                }

                if ( $number > 32 ) {
                        $number = 32;
                }

                return (string) $number;
        }

  public static function register_csv_export_handler(): void {
  	add_action('admin_action_f152_export_csv', [__CLASS__, 'handle_csv_export']);
  	add_action('admin_action_f152_clear_logs', [__CLASS__, 'handle_clear_logs']);
  	add_action('admin_action_f152_create_pages', [__CLASS__, 'handle_create_pages']);
  	add_action('admin_post_f152_replace_configured_pages', [__CLASS__, 'handle_replace_configured_pages']);
  	add_action('admin_action_f152_clear_banner_stats', [__CLASS__, 'handle_clear_banner_stats']);
  	add_filter('wp_redirect', [__CLASS__, 'intercept_save_and_create_redirect']);
  }

  private static function render_banner_stats_block() : void {
  	if ( ! class_exists('\\F152\\BannerStats') ) {
  		return;
  	}

  	$counts   = \F152\BannerStats::get_counts();
  	$accept   = (int) ( $counts['accept']  ?? 0 );
  	$reject   = (int) ( $counts['reject']  ?? 0 );
  	$custom   = (int) ( $counts['custom']  ?? 0 );
  	$ignored  = (int) ( $counts['ignored'] ?? 0 );
  	$decided  = $accept + $reject + $custom;

  	$pct = function ( int $val ) use ( $decided ) : string {
  		if ( $decided <= 0 ) {
  			return '0%';
  		}
  		return round( ( $val / $decided ) * 100, 1 ) . '%';
  	};

  	$retention_days = \F152\BannerStats::get_retention_days();

  	$clear_url = wp_nonce_url(
  		add_query_arg( [ 'action' => 'f152_clear_banner_stats' ], admin_url('admin.php') ),
  		'f152_clear_banner_stats',
  		'f152_clear_banner_stats_nonce'
  	);

  	$cleared_raw = filter_input( INPUT_GET, 'f152_bs_cleared', FILTER_UNSAFE_RAW );
  	$cleared = is_string( $cleared_raw ) ? sanitize_text_field( wp_unslash( $cleared_raw ) ) : '';
  	?>
  	<div class="f152-banner-stats">
  		<h2 class="f152-section-title"><?php echo esc_html__( 'Статистика cookie-баннера', 'fz-152-rf' ); ?></h2>

  		<?php if ( $cleared === '1' ) : ?>
  			<div class="notice notice-success is-dismissible">
  				<p><?php echo esc_html__( 'Статистика cookie-баннера успешно очищена.', 'fz-152-rf' ); ?></p>
  			</div>
  		<?php endif; ?>

  		<div class="f152-grid">
  			<div class="f152-onboarding__card">
  				<h3><?php echo esc_html__( 'Приняли всё', 'fz-152-rf' ); ?></h3>
  				<p style="font-size:24px;font-weight:700;"><?php echo esc_html( number_format_i18n( $accept ) ); ?></p>
  				<p><?php echo esc_html( $pct( $accept ) ); ?></p>
  			</div>
  			<div class="f152-onboarding__card">
  				<h3><?php echo esc_html__( 'Отклонили всё', 'fz-152-rf' ); ?></h3>
  				<p style="font-size:24px;font-weight:700;"><?php echo esc_html( number_format_i18n( $reject ) ); ?></p>
  				<p><?php echo esc_html( $pct( $reject ) ); ?></p>
  			</div>
  			<div class="f152-onboarding__card">
  				<h3><?php echo esc_html__( 'Настроили выборочно', 'fz-152-rf' ); ?></h3>
  				<p style="font-size:24px;font-weight:700;"><?php echo esc_html( number_format_i18n( $custom ) ); ?></p>
  				<p><?php echo esc_html( $pct( $custom ) ); ?></p>
  			</div>
  			<div class="f152-onboarding__card">
  				<h3><?php echo esc_html__( 'Без решения', 'fz-152-rf' ); ?></h3>
  				<p style="font-size:24px;font-weight:700;"><?php echo esc_html( number_format_i18n( $ignored ) ); ?></p>
  				<p><?php echo esc_html( '—' ); ?></p>
  			</div>
  		</div>

  		<p>
  			<strong><?php echo esc_html__( 'Всего завершённых решений:', 'fz-152-rf' ); ?></strong>
  			<?php echo esc_html( number_format_i18n( $decided ) ); ?>
  		</p>
  		<p style="color:#666;">
  			<?php
  			echo esc_html( sprintf(
  				/* translators: %d: количество дней хранения */
  				__( 'Статистика за последние %d дней.', 'fz-152-rf' ),
  				$retention_days
  			) );
  			?>
  			<br>
  			<?php echo esc_html__( '«Без решения» — сеансы, где баннер был показан, но в течение 30 минут не нажаты «Принять», «Отклонить» или «Сохранить».', 'fz-152-rf' ); ?>
  			<br>
  			<?php echo esc_html__( 'Счётчики показывают сеансы с показом cookie-баннера, а не гарантированно уникальных людей. Известные и очевидные боты отфильтровываются, однако полностью исключить автоматический трафик технически невозможно.', 'fz-152-rf' ); ?>
  		</p>

  		<p>
  			<a href="<?php echo esc_url( $clear_url ); ?>" class="button" style="color:#a00;" onclick="return confirm('<?php echo esc_attr__( 'Очистить статистику cookie-баннера? Это действие необратимо. Журнал согласий форм затронут не будет.', 'fz-152-rf' ); ?>');">
  				<?php echo esc_html__( 'Очистить статистику', 'fz-152-rf' ); ?>
  			</a>
  		</p>
  	</div>
  	<?php
  }

  public static function handle_clear_banner_stats() : void {
  	if ( ! current_user_can('manage_options') ) {
  		wp_die( esc_html__( 'Недостаточно прав для выполнения операции.', 'fz-152-rf' ) );
  	}

  	check_admin_referer( 'f152_clear_banner_stats', 'f152_clear_banner_stats_nonce' );

  	if ( class_exists('\\F152\\BannerStats') ) {
  		\F152\BannerStats::truncate();
  	}

  	wp_safe_redirect( add_query_arg( [
  		'page' => 'f152',
  		'tab' => 'stats',
  		'f152_bs_cleared' => '1',
  	], admin_url('options-general.php') ) );
  	exit;
  }

  private static function legal_pages_config() : array {
  	return [
  		'policy_pd' => [
  			'title'    => 'Политика обработки персональных данных',
  			'slug'     => 'politika-obrabotki-personalnyh-dannyh',
  			'url_opt'  => 'f152_link_policy_pd',
  			'text_opt' => 'f152_text_policy_pd',
  		],
  		'consent_pd' => [
  			'title'    => 'Согласие на обработку персональных данных',
  			'slug'     => 'soglasie-na-obrabotku-personalnyh-dannyh',
  			'url_opt'  => 'f152_link_consent_pd',
  			'text_opt' => 'f152_text_consent_pd',
  		],
  		'policy_cookie' => [
  			'title'    => 'Политика использования cookie',
  			'slug'     => 'politika-ispolzovaniya-cookie',
  			'url_opt'  => 'f152_link_policy_cookie',
  			'text_opt' => 'f152_text_policy_cookie',
  		],
  		'consent_marketing' => [
  			'title'    => 'Согласие на рассылку',
  			'slug'     => 'soglasie-na-rassylku',
  			'url_opt'  => 'f152_link_consent_marketing',
  			'text_opt' => 'f152_text_consent_marketing',
  		],
  	];
  }

 public static function handle_csv_export(): void {
 	if ( ! current_user_can('manage_options') ) {
 		wp_die('Недостаточно прав для выполнения операции.');
 	}

 	$nonce_raw = filter_input(INPUT_GET, 'f152_export_nonce', FILTER_UNSAFE_RAW);
 	$nonce = is_string($nonce_raw) ? sanitize_text_field(wp_unslash($nonce_raw)) : '';

 	if ( ! wp_verify_nonce($nonce, 'f152_export_csv') ) {
 		wp_die('Ошибка проверки безопасности. Попробуйте ещё раз.');
 	}

 	$logs = [];
 	if ( class_exists('\\F152\\ConsentLog') ) {
 		$logs = \F152\ConsentLog::get_all_for_export();
 	}

 	$filename = 'fz-152-consent-log-' . gmdate( 'Y-m-d' ) . '.csv';

 	while ( ob_get_level() > 0 ) {
 		ob_end_clean();
 	}

 	header('Content-Type: text/csv; charset=utf-8');
 	header('Content-Disposition: attachment; filename="' . $filename . '"');
 	header('Pragma: no-cache');
 	header('Expires: 0');

 	echo "\xEF\xBB\xBF";

 	$output = fopen('php://output', 'w');

 	$headers = [
 		'created_at',
 		'source_type',
 		'source_id',
 		'source_uid',
 		'source_plugin',
 		'source_label',
 		'page_url',
 		'form_id',
 		'form_title',
 		'email',
 		'full_name',
 		'phone',
 		'user_id',
 		'ip_address',
 		'user_agent',
 		'consent_kind',
 		'decision',
 		'required',
 		'consent_text',
 		'policy_version',
 		'policy_url',
 		'consent_hash',
 		'context',
 	];
 	fputcsv($output, $headers, ';');

 	foreach ( $logs as $row ) {
 		$event_meta = self::get_log_event_meta( is_array( $row ) ? $row : [] );
 		$csv_row = [
 			$row['created_at'] ?? '',
 			$row['source_type'] ?? '',
 			$row['source_id'] ?? '',
 			$row['source_uid'] ?? '',
 			$row['source_plugin'] ?? '',
 			$row['source_label'] ?? '',
 			$row['page_url'] ?? '',
 			$row['form_id'] ?? '',
 			$row['form_title'] ?? '',
 			$row['email'] ?? '',
 			$row['full_name'] ?? '',
 			$row['phone'] ?? '',
 			$row['user_id'] ?? '',
 			$row['ip_address'] ?? '',
 			$row['user_agent'] ?? '',
 			$event_meta['kind'],
 			$event_meta['decision'],
 			$event_meta['required'],
 			$row['consent_text'] ?? '',
 			$row['policy_version'] ?? '',
 			$row['policy_url'] ?? '',
 			$row['consent_hash'] ?? '',
 			self::csv_context_to_string( $row['context'] ?? '' ),
 		];

 		$csv_row = array_map( [ __CLASS__, 'csv_safe_cell' ], $csv_row );

 		fputcsv($output, $csv_row, ';');
 	}

 	exit;
 }

 public static function handle_clear_logs(): void {
 	if ( ! current_user_can('manage_options') ) {
 		wp_die('Недостаточно прав для выполнения операции.');
 	}

 	$nonce_raw = filter_input(INPUT_GET, 'f152_clear_nonce', FILTER_UNSAFE_RAW);
 	$nonce = is_string($nonce_raw) ? sanitize_text_field(wp_unslash($nonce_raw)) : '';

 	if ( ! wp_verify_nonce($nonce, 'f152_clear_logs') ) {
 		wp_die('Ошибка проверки безопасности. Попробуйте ещё раз.');
 	}

 	if ( class_exists('\\F152\\ConsentLog') ) {
 		\F152\ConsentLog::truncate_table();
 	}

 	wp_safe_redirect(add_query_arg([
 		'page' => 'f152',
 		'tab' => 'logs',
 		'f152_cleared' => '1',
 	], admin_url('options-general.php')));
 	exit;
 }

 	public static function handle_replace_configured_pages(): void {
 		if ( ! current_user_can( 'edit_pages' ) ) {
 			wp_die( 'Недостаточно прав для выполнения операции.' );
 		}

 		check_admin_referer( 'f152_replace_configured_pages', 'f152_replace_pages_nonce' );

 		$return_tab_raw = filter_input( INPUT_POST, 'return_tab', FILTER_UNSAFE_RAW );
 		if ( ! is_string( $return_tab_raw ) ) {
 			$return_tab_raw = filter_input( INPUT_GET, 'return_tab', FILTER_UNSAFE_RAW );
 		}
 		$return_tab = is_string( $return_tab_raw ) ? sanitize_key( wp_unslash( $return_tab_raw ) ) : 'main';
 		if ( ! in_array( $return_tab, [ 'main', 'texts' ], true ) ) {
 			$return_tab = 'main';
 		}

 		$config = self::legal_pages_config();
 		$targets = [];
 		$errors = [];
 		$seen_page_ids = [];

 		$company_name = trim( (string) get_option( 'f152_company_name', '' ) );
 		$company_email = trim( (string) get_option( 'f152_company_email', '' ) );
 		if ( '' === $company_name ) {
 			$errors[] = 'Сначала заполните «Название компании / ИП / ФИО» в основных настройках.';
 		}
 		if ( '' === $company_email || ! is_email( $company_email ) ) {
 			$errors[] = 'Сначала укажите корректный email в основных настройках.';
 		}

 		foreach ( $config as $key => $cfg ) {
 			$url = trim( (string) get_option( $cfg['url_opt'], '' ) );
 			if ( '' === $url ) {
 				continue;
 			}

 			$page_id = self::configured_page_id_from_url( $url );
 			$post = $page_id > 0 ? get_post( $page_id ) : null;
 			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
 				$errors[] = sprintf( '«%s»: по указанной ссылке не найдена доступная страница WordPress на этом сайте.', $cfg['title'] );
 				continue;
 			}
 			if ( ! current_user_can( 'edit_post', $page_id ) ) {
 				$errors[] = sprintf( '«%s»: у вашей учётной записи нет права изменять эту страницу.', $cfg['title'] );
 				continue;
 			}
 			if ( isset( $seen_page_ids[ $page_id ] ) ) {
 				$errors[] = sprintf( 'Одна и та же страница указана сразу для «%s» и «%s». Для автоматической замены укажите разные страницы.', $seen_page_ids[ $page_id ], $cfg['title'] );
 				continue;
 			}
 			$seen_page_ids[ $page_id ] = $cfg['title'];

 			$raw = class_exists( '\\F152\\Templates' ) ? (string) \F152\Templates::default_for_option( $cfg['text_opt'] ) : '';
 			$html = class_exists( '\\F152\\Helpers' ) ? (string) \F152\Helpers::replace_common_macros( $raw ) : $raw;
			if ( in_array( $key, [ 'policy_pd', 'policy_cookie' ], true )
				&& class_exists( '\\F152\\PolicyServices' )
				&& is_callable( [ '\\F152\\PolicyServices', 'preserve_existing_free_services_on_full_replace' ] ) ) {
				$html = \F152\PolicyServices::preserve_existing_free_services_on_full_replace(
					$html,
					(string) $post->post_content,
					$key
				);
			}
 			if ( '' === trim( $html ) ) {
 				$errors[] = sprintf( '«%s»: не удалось получить актуальный шаблон текста.', $cfg['title'] );
 				continue;
 			}
 			if ( preg_match( '#\[f152_(link_[a-z_]+|company_name|company_inn|operator_identity|company_email|site_url|policy_version|services_policy_(?:pd|cookie)|order_personal_data_items|order_consent_purpose_item)\]#i', $html ) ) {
 				$errors[] = sprintf( '«%s»: в готовом тексте остался нераскрытый макрос. Страница не изменена.', $cfg['title'] );
 				continue;
 			}

 			$targets[ $key ] = [
 				'page_id'     => $page_id,
 				'title'       => $cfg['title'],
 				'old_content' => (string) $post->post_content,
 				'new_content' => $html,
 				'view_url'    => get_permalink( $page_id ) ?: $url,
 				'edit_url'    => get_edit_post_link( $page_id, 'raw' ) ?: '',
 			];
 		}

 		if ( empty( $targets ) && empty( $errors ) ) {
 			$errors[] = 'В настройках не указано ни одной страницы. Сначала добавьте ссылки на существующие юридические страницы.';
 		}

 		if ( ! empty( $errors ) ) {
 			self::flash_create_result([
 				'type'    => 'error',
 				'message' => 'Тексты не были заменены. Исправьте указанные проблемы и повторите попытку:',
 				'items'   => $errors,
 			]);
 			self::redirect_back_to_tab( $return_tab );
 		}

 		$updated = [];
 		$written = [];
 		$failed_message = '';
 		foreach ( $targets as $key => $target ) {
 			$current = get_post( (int) $target['page_id'] );
 			if ( ! $current instanceof \WP_Post || (string) $current->post_content !== (string) $target['old_content'] ) {
 				$failed_message = sprintf( 'Страница «%s» изменилась во время операции. Ничего не перезаписано поверх более свежего текста.', $target['title'] );
 				break;
 			}

 			if ( function_exists( 'wp_save_post_revision' ) ) {
 				wp_save_post_revision( (int) $target['page_id'] );
 			}

 			$result = wp_update_post([
 				'ID'           => (int) $target['page_id'],
 				'post_content' => wp_slash( (string) $target['new_content'] ),
 			], true);
 			if ( is_wp_error( $result ) ) {
 				$failed_message = sprintf( 'Не удалось обновить страницу «%s»: %s', $target['title'], $result->get_error_message() );
 				break;
 			}

 			$written[ $key ] = $target;
 			$updated[] = [
 				'title'    => $target['title'],
 				'view_url' => $target['view_url'],
 				'edit_url' => $target['edit_url'],
 			];
 		}

 		if ( '' !== $failed_message ) {
 			foreach ( array_reverse( $written, true ) as $target ) {
 				$current = get_post( (int) $target['page_id'] );
 				if ( $current instanceof \WP_Post && (string) $current->post_content === (string) $target['new_content'] ) {
 					wp_update_post([
 						'ID'           => (int) $target['page_id'],
 						'post_content' => wp_slash( (string) $target['old_content'] ),
 					]);
 				}
 			}
 			self::flash_create_result([
 				'type'    => 'error',
 				'message' => 'Операция отменена.',
 				'items'   => [ $failed_message ],
 			]);
 			self::redirect_back_to_tab( $return_tab );
 		}

 		$generated = get_option( 'f152_generated_page_ids', [] );
 		if ( ! is_array( $generated ) ) {
 			$generated = [];
 		}
 		foreach ( $targets as $key => $target ) {
 			$generated[ $key ] = (int) $target['page_id'];
 		}
 		update_option( 'f152_generated_page_ids', $generated, false );

 		if ( class_exists( '\\F152\\ServiceScanner' ) && is_callable( [ '\\F152\\ServiceScanner', 'clear_cache' ] ) ) {
 			\F152\ServiceScanner::clear_cache();
 		}
 		if ( class_exists( '\\F152\\Assets' ) && is_callable( [ '\\F152\\Assets', 'schedule_page_cache_purge' ] ) ) {
 			\F152\Assets::schedule_page_cache_purge();
 		}

 		self::flash_create_result([
 			'type'   => 'success',
 			'result' => [ 'updated' => $updated ],
 		]);
 		self::redirect_back_to_tab( $return_tab );
 	}

 	private static function configured_page_id_from_url( string $url ): int {
 		$url = trim( $url );
 		if ( '' === $url ) {
 			return 0;
 		}

 		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
 		$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
 		$home_host = preg_replace( '/^www\./', '', $home_host );
 		$url_host = preg_replace( '/^www\./', '', $url_host );
 		if ( '' === $url_host || '' === $home_host || $url_host !== $home_host ) {
 			return 0;
 		}

 		$parts = wp_parse_url( $url );
 		if ( ! is_array( $parts ) ) {
 			return 0;
 		}
 		$clean = $url;
 		$fragment_pos = strpos( $clean, '#' );
 		if ( false !== $fragment_pos ) {
 			$clean = substr( $clean, 0, $fragment_pos );
 		}

 		$page_id = (int) url_to_postid( $clean );
 		if ( $page_id > 0 ) {
 			return $page_id;
 		}

 		if ( isset( $parts['query'] ) && '' !== (string) $parts['query'] ) {
 			parse_str( (string) $parts['query'], $query_args );
 			if ( is_array( $query_args ) && ! empty( $query_args ) ) {
 				$without_query = remove_query_arg( array_keys( $query_args ), $clean );
 				return (int) url_to_postid( $without_query );
 			}
 		}
 		return 0;
 	}

 	private static function redirect_back_to_tab( string $tab ): void {
 		$tab = in_array( $tab, [ 'main', 'texts' ], true ) ? $tab : 'main';
 		wp_safe_redirect( add_query_arg([
 			'page' => 'f152',
 			'tab'  => $tab,
 		], admin_url( 'options-general.php' ) ) );
 		exit;
 	}

 	public static function handle_create_pages(): void {
 		if ( ! current_user_can('edit_pages') ) {
 			wp_die('Недостаточно прав для выполнения операции.');
 		}

 		check_admin_referer('f152_create_pages', 'f152_create_pages_nonce');

 		$config = self::legal_pages_config();

 		$old_urls = [];
 		$old_generated = get_option('f152_generated_page_ids', []);
 		if ( ! is_array($old_generated) ) {
 			$old_generated = [];
 		}
 		foreach ( $config as $cfg ) {
 			$old_urls[$cfg['url_opt']] = (string) get_option($cfg['url_opt'], '');
 		}

 		$missing = [];
 		$company_name = trim((string) get_option('f152_company_name', ''));
 		$company_email = trim((string) get_option('f152_company_email', ''));

 		if ( $company_name === '' ) {
 			$missing[] = 'Название компании / ИП / ФИО (f152_company_name)';
 		}
 		if ( $company_email === '' ) {
 			$missing[] = 'Email (f152_company_email)';
 		} elseif ( ! is_email($company_email) ) {
 			$missing[] = 'Email указан в некорректном формате (f152_company_email)';
 		}

 		if ( $missing ) {
 			self::flash_create_result([
 				'type'    => 'error',
 				'message' => 'Не удалось создать страницы. Сначала заполните обязательные реквизиты в настройках:',
 				'items'   => $missing,
 			]);
 			self::redirect_back();
 		}

 		$result = [
 			'created'    => [],
 			'restored'   => [],
 			'skipped'    => [],
 			'manual'     => [],
 			'errors'     => [],
 		];

 		$created_ids = [];
 		$final_urls  = [];
 		$new_generated = $old_generated;
 		$failed = false;
 		$to_create = [];

 		foreach ( $config as $key => $cfg ) {
 			$manual_url = trim($old_urls[$cfg['url_opt']]);
 			if ( $manual_url !== '' ) {
 				$final_urls[$cfg['url_opt']] = $manual_url;
 				$result['skipped'][] = [ 'title' => $cfg['title'] ];
 				continue;
 			}

 			$restored = false;
 			if ( ! empty($new_generated[$key]) ) {
 				$pid = (int) $new_generated[$key];
 				$post = $pid > 0 ? get_post($pid) : null;
 				if ( $post && $post->post_type === 'page' ) {
 					if ( $post->post_status === 'trash' ) {
 					} elseif ( $post->post_status === 'publish' ) {
 						$permalink = get_permalink($pid);
 						if ( $permalink ) {
 							$final_urls[$cfg['url_opt']] = $permalink;
 							$result['restored'][] = [
 								'title' => $cfg['title'],
 								'url'   => $permalink,
 							];
 							$restored = true;
 						}
 					} else {
 						$edit = get_edit_post_link($pid, 'raw');
 						$result['manual'][] = [
 							'title'    => $cfg['title'],
 							'edit_url' => $edit ?: '',
 						];
 						$restored = true;
 					}
 				}
 			}

 			if ( ! $restored ) {
 				$to_create[$key] = $cfg;
 			}
 		}

 		foreach ( $to_create as $key => $cfg ) {
 			$post_id = wp_insert_post([
 				'post_type'   => 'page',
 				'post_status' => 'publish',
 				'post_title'  => $cfg['title'],
 				'post_name'   => $cfg['slug'],
 				'post_content'=> '',
 			], true);

 			if ( is_wp_error($post_id) ) {
 				$result['errors'][] = 'Ошибка создания страницы «' . $cfg['title'] . '»: ' . $post_id->get_error_message();
 				$failed = true;
 				break;
 			}

 			$created_ids[$key] = (int) $post_id;
 			$permalink = get_permalink($post_id);
 			if ( ! $permalink ) {
 				$result['errors'][] = 'Не удалось получить адрес страницы «' . $cfg['title'] . '».';
 				$failed = true;
 				break;
 			}
 			$final_urls[$cfg['url_opt']] = $permalink;
 		}

 		if ( ! $failed ) {
 			foreach ( $final_urls as $opt => $url ) {
 				if ( trim((string) get_option($opt, '')) === '' ) {
 					update_option($opt, $url);
 				}
 			}
 		}

 		if ( ! $failed ) {
 			foreach ( $created_ids as $key => $pid ) {
 				$cfg = $to_create[$key];
 				$raw = '';
 				if ( class_exists('\\F152\\Templates') ) {
 					$raw = (string) \F152\Templates::default_for_option($cfg['text_opt']);
 				}
 				$html = '';
 				if ( class_exists('\\F152\\Helpers') ) {
 					$html = \F152\Helpers::replace_common_macros($raw);
 				} else {
 					$html = $raw;
 				}

 				if ( preg_match('#\[f152_(link_[a-z_]+|company_name|company_inn|operator_identity|company_email|site_url|policy_version|services_policy_(?:pd|cookie)|order_personal_data_items|order_consent_purpose_item)\]#i', $html) ) {
 					$result['errors'][] = 'В тексте страницы «' . $cfg['title'] . '» остался нераскрытый макрос. Создание прервано.';
 					$failed = true;
 					break;
 				}

 				$up = wp_update_post([
 					'ID'           => $pid,
 					'post_content' => wp_slash($html),
 				], true);

 				if ( is_wp_error($up) ) {
 					$result['errors'][] = 'Ошибка заполнения страницы «' . $cfg['title'] . '»: ' . $up->get_error_message();
 					$failed = true;
 					break;
 				}

 				$result['created'][] = [
 					'title'    => $cfg['title'],
 					'view_url' => get_permalink($pid),
 					'edit_url' => get_edit_post_link($pid, 'raw'),
 				];
 			}
 		}

 		if ( $failed ) {
 			foreach ( $created_ids as $pid ) {
 				wp_delete_post($pid, true);
 			}
 			foreach ( $old_urls as $opt => $val ) {
 				update_option($opt, $val);
 			}
 			self::flash_create_result([
 				'type'    => 'error',
 				'message' => 'Операция прервана из-за ошибок. Созданные в этом запросе страницы удалены, настройки не изменены.',
 				'items'   => $result['errors'],
 			]);
 			self::redirect_back();
 		}

 		foreach ( $created_ids as $key => $pid ) {
 			$new_generated[$key] = $pid;
 		}
 		update_option('f152_generated_page_ids', $new_generated, false);

 		self::flash_create_result([
 			'type'   => 'success',
 			'result' => $result,
 		]);
 		self::redirect_back();
 	}

 	private static function flash_create_result(array $data): void {
 		set_transient('f152_create_result_' . get_current_user_id(), $data, 60);
 	}

 	private static function redirect_back(): void {
 		wp_safe_redirect(add_query_arg([
 			'page' => 'f152',
 			'tab'  => 'main',
 		], admin_url('options-general.php')));
 		exit;
 	}

 	private static function render_create_pages_button(): void {
 		$config = self::legal_pages_config();

 		$empty_count = 0;
 		foreach ( $config as $cfg ) {
 			if ( trim((string) get_option($cfg['url_opt'], '')) === '' ) {
 				$empty_count++;
 			}
 		}

 		$total = count($config);

 		$label = ( $empty_count === $total )
 			? 'Создать страницы'
 			: 'Создать недостающие страницы';

 		?>
		<div class="f152-create-pages-actions">
			<div class="f152-create-pages-action">
				<button type="submit" name="f152_save_and_create_pages" value="1" class="button button-primary"><?php echo esc_html($label); ?></button>
				<span class="description">Создаёт только недостающие юридические страницы и заполняет ссылки в настройках.</span>
				<?php if ( $empty_count === 0 ) : ?>
					<span class="description">Все ссылки уже заполнены — новые страницы созданы не будут.</span>
				<?php endif; ?>
			</div>
			<?php self::render_replace_configured_pages_button( true, 'main' ); ?>
		</div>
 		<?php
 	}

 	private static function configured_pages_are_managed(): bool {
 		$generated = get_option( 'f152_generated_page_ids', [] );
 		if ( ! is_array( $generated ) ) {
 			return false;
 		}

 		$has_configured = false;
 		foreach ( self::legal_pages_config() as $key => $cfg ) {
 			$url = trim( (string) get_option( $cfg['url_opt'], '' ) );
 			if ( '' === $url ) {
 				continue;
 			}

 			$has_configured = true;
 			$page_id = self::configured_page_id_from_url( $url );
 			if ( $page_id <= 0 || (int) ( $generated[ $key ] ?? 0 ) !== $page_id ) {
 				return false;
 			}
 		}

 		return $has_configured;
 	}

 	private static function configured_pages_need_full_template_refresh(): bool {
		$generated = get_option( 'f152_generated_page_ids', [] );
		if ( ! is_array( $generated ) ) {
			return false;
		}

		foreach ( self::legal_pages_config() as $key => $cfg ) {
			$url = trim( (string) get_option( $cfg['url_opt'], '' ) );
			if ( '' === $url ) {
				continue;
			}

			$page_id = self::configured_page_id_from_url( $url );
			if ( $page_id <= 0 || (int) ( $generated[ $key ] ?? 0 ) !== $page_id ) {
				continue;
			}

			$post = get_post( $page_id );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type || 'trash' === $post->post_status ) {
				continue;
			}

			$raw = class_exists( '\\F152\\Templates' ) ? (string) \F152\Templates::default_for_option( $cfg['text_opt'] ) : '';
			$expected = class_exists( '\\F152\\Helpers' ) ? (string) \F152\Helpers::replace_common_macros( $raw ) : $raw;
			if ( '' === trim( $expected ) ) {
				continue;
			}

			if ( self::normalize_legal_page_for_template_compare( (string) $post->post_content, $key )
				!== self::normalize_legal_page_for_template_compare( $expected, $key ) ) {
				return true;
			}
		}

		return false;
	}

	private static function normalize_legal_page_for_template_compare( string $content, string $key ): string {
		$content = str_replace( [ "\r\n", "\r" ], "\n", $content );
		$document = '';
		if ( 'policy_pd' === $key ) {
			$document = 'policy_pd';
		} elseif ( 'policy_cookie' === $key ) {
			$document = 'policy_cookie';
		}

		if ( '' !== $document ) {
			$doc = preg_quote( $document, '#' );
			$pattern = '#<!--\\s*f152-services:start:' . $doc . ':v[0-9]+\\s*-->.*?<!--\\s*f152-services:end:' . $doc . '\\s*-->#s';
			$normalized = preg_replace( $pattern, '<!-- f152-services:managed-zone -->', $content, 1 );
			if ( is_string( $normalized ) ) {
				$content = $normalized;
			}
		}

		return trim( $content );
	}

	private static function render_replace_configured_pages_button( bool $save_first = true, string $return_tab = 'main' ): void {
		$managed = self::configured_pages_are_managed();
		if ( $managed && ! self::configured_pages_need_full_template_refresh() ) {
			return;
		}
		$return_tab = in_array( $return_tab, [ 'main', 'texts' ], true ) ? $return_tab : 'main';
		$has_configured = false;
		foreach ( self::legal_pages_config() as $cfg ) {
			if ( '' !== trim( (string) get_option( $cfg['url_opt'], '' ) ) ) {
				$has_configured = true;
				break;
			}
		}

		if ( $managed ) {
			$warning = 'Базовый шаблон юридических страниц изменился. Кнопки обновления Метрики, Карт и других сервисов меняют только управляемые блоки внутри политик и не переписывают остальной документ. Чтобы применить актуальную редакцию всего шаблона, пересоздайте тексты полностью. Внимание: ручные изменения на этих страницах будут перезаписаны.';
			$confirm = 'Все указанные юридические страницы будут полностью пересозданы по актуальным шаблонам FZ-152. Ручные изменения будут перезаписаны. Продолжить?';
			$button_label = 'Пересоздать страницы полностью';
		} else {
			$warning = 'Внимание! Плагин полностью заменит текст на указанных страницах актуальными шаблонами FZ-152 и добавит необходимые блоки о найденных сервисах. Ваши ручные изменения на этих страницах будут перезаписаны.';
			$confirm = 'Текст на указанных страницах будет полностью заменён. Ручные изменения будут перезаписаны. Продолжить?';
			$button_label = 'Заменить тексты на указанных страницах';
		}

		if ( $save_first ) : ?>
			<div id="f152-replace-policy-pages" class="f152-create-pages-action f152-create-pages-action--danger" style="scroll-margin-top:32px;">
				<button type="submit" name="f152_save_and_replace_pages" value="1" class="button" onclick="return confirm('<?php echo esc_js( $confirm ); ?>');"><?php echo esc_html( $button_label ); ?></button>
				<span class="description"><strong><?php echo esc_html( $warning ); ?></strong></span>
			</div>
		<?php else : ?>
			<div id="f152-replace-policy-pages" class="notice notice-warning inline" style="margin:16px 0;padding:12px 14px;scroll-margin-top:32px;">
				<p style="margin-top:0;"><strong><?php echo esc_html( $warning ); ?></strong></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
					<input type="hidden" name="action" value="f152_replace_configured_pages">
					<input type="hidden" name="return_tab" value="<?php echo esc_attr( $return_tab ); ?>">
					<?php wp_nonce_field( 'f152_replace_configured_pages', 'f152_replace_pages_nonce' ); ?>
					<button type="submit" class="button button-primary" <?php disabled( ! $has_configured ); ?> onclick="return confirm('<?php echo esc_js( $confirm ); ?>');"><?php echo esc_html( $button_label ); ?></button>
				</form>
				<?php if ( ! $has_configured ) : ?><p class="description">Сначала укажите ссылки на существующие юридические страницы в основных настройках.</p><?php endif; ?>
			</div>
		<?php endif;
	}

	public static function intercept_save_and_create_redirect( string $location ) : string {
		if ( ! is_admin() ) {
			return $location;
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: '';

		if ( 'post' !== $request_method ) {
			return $location;
		}

		$pagenow = $GLOBALS['pagenow'] ?? '';
		if ( 'options.php' !== $pagenow ) {
			return $location;
		}

		$nonce = isset( $_POST['_wpnonce'] )
			? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'f152-options' ) ) {
			return $location;
		}

		$option_page = isset( $_POST['option_page'] )
			? sanitize_key( wp_unslash( $_POST['option_page'] ) )
			: '';

		if ( 'f152' !== $option_page ) {
			return $location;
		}

		$save_and_create = isset( $_POST['f152_save_and_create_pages'] )
			? sanitize_text_field( wp_unslash( $_POST['f152_save_and_create_pages'] ) )
			: '';
		$save_and_replace = isset( $_POST['f152_save_and_replace_pages'] )
			? sanitize_text_field( wp_unslash( $_POST['f152_save_and_replace_pages'] ) )
			: '';

		if ( '1' !== $save_and_create && '1' !== $save_and_replace ) {
			return $location;
		}

		if ( ! current_user_can( 'edit_pages' ) ) {
			return $location;
		}

		if ( '1' === $save_and_replace ) {
			return add_query_arg(
				[
					'action'                   => 'f152_replace_configured_pages',
					'f152_replace_pages_nonce' => wp_create_nonce( 'f152_replace_configured_pages' ),
					'return_tab'               => 'main',
				],
				admin_url( 'admin-post.php' )
			);
		}

		return add_query_arg(
			[
				'action'                   => 'f152_create_pages',
				'f152_create_pages_nonce' => wp_create_nonce( 'f152_create_pages' ),
			],
			admin_url( 'admin.php' )
		);
	}

 	private static function render_create_pages_notice(): void {
 		$key = 'f152_create_result_' . get_current_user_id();
 		$data = get_transient($key);
 		if ( ! is_array($data) ) {
 			return;
 		}
 		delete_transient($key);

 		$type = $data['type'] ?? 'info';
 		$class = in_array($type, ['error', 'success', 'warning', 'info'], true) ? $type : 'info';

 		echo '<div class="notice notice-' . esc_attr($class) . ' is-dismissible"><div style="padding:10px 0;">';

 		if ( $type === 'error' ) {
 			echo '<p><strong>' . esc_html($data['message'] ?? 'Ошибка.') . '</strong></p>';
 			if ( ! empty($data['items']) && is_array($data['items']) ) {
 				echo '<ul style="list-style:disc;padding-left:20px;">';
 				foreach ( $data['items'] as $item ) {
 					echo '<li>' . esc_html($item) . '</li>';
 				}
 				echo '</ul>';
 			}
 		} else {
 			$result = $data['result'] ?? [];

 			if ( ! empty($result['updated']) ) {
 				echo '<p><strong>Тексты обновлены на страницах: ' . count($result['updated']) . '</strong></p><ul style="list-style:disc;padding-left:20px;">';
 				foreach ( $result['updated'] as $p ) {
 					echo '<li>' . esc_html($p['title']);
 					if ( ! empty($p['view_url']) ) {
 						echo ' — <a href="' . esc_url($p['view_url']) . '" target="_blank">просмотр</a>';
 					}
 					if ( ! empty($p['edit_url']) ) {
 						echo ' | <a href="' . esc_url($p['edit_url']) . '">редактировать</a>';
 					}
 					echo '</li>';
 				}
 				echo '</ul><p>Эти страницы теперь считаются управляемыми FZ-152 и смогут получать следующие обновления текстов автоматически.</p>';
 			}

 			if ( ! empty($result['created']) ) {
 				echo '<p><strong>Создано и опубликовано страниц: ' . count($result['created']) . '</strong></p><ul style="list-style:disc;padding-left:20px;">';
 				foreach ( $result['created'] as $p ) {
 					echo '<li>' . esc_html($p['title']);
 					if ( ! empty($p['view_url']) ) {
 						echo ' — <a href="' . esc_url($p['view_url']) . '" target="_blank">просмотр</a>';
 					}
 					if ( ! empty($p['edit_url']) ) {
 						echo ' | <a href="' . esc_url($p['edit_url']) . '">редактировать</a>';
 					}
 					echo '</li>';
 				}
 				echo '</ul>';
 			}

 			if ( ! empty($result['restored']) ) {
 				echo '<p>Восстановлены ссылки на ранее созданные страницы:</p><ul style="list-style:disc;padding-left:20px;">';
 				foreach ( $result['restored'] as $p ) {
 					echo '<li>' . esc_html($p['title']) . ' — <a href="' . esc_url($p['url']) . '" target="_blank">' . esc_html($p['url']) . '</a></li>';
 				}
 				echo '</ul>';
 			}

 			if ( ! empty($result['skipped']) ) {
 				echo '<p>Уже заполнены и пропущены:</p><ul style="list-style:disc;padding-left:20px;">';
 				foreach ( $result['skipped'] as $p ) {
 					echo '<li>' . esc_html($p['title']) . '</li>';
 				}
 				echo '</ul>';
 			}

 			if ( ! empty($result['manual']) ) {
 				echo '<p>Требуют ручного действия (страница существует, но не опубликована):</p><ul style="list-style:disc;padding-left:20px;">';
 				foreach ( $result['manual'] as $p ) {
 					echo '<li>' . esc_html($p['title']);
 					if ( ! empty($p['edit_url']) ) {
 						echo ' — <a href="' . esc_url($p['edit_url']) . '">открыть</a>';
 					}
 					echo '</li>';
 				}
 				echo '</ul>';
 			}

 			if ( empty($result['updated']) && empty($result['created']) && empty($result['restored']) && empty($result['skipped']) && empty($result['manual']) ) {
 				echo '<p>Операция выполнена, но изменений не потребовалось.</p>';
 			}
 		}

 		echo '</div></div>';
 	}

 private static function render_tab_logs(): void {
 	$table_ready = false;
 	if ( class_exists('\\F152\\ConsentLog') ) {
 		$table_ready = \F152\ConsentLog::maybe_create_table();
 	}

 	$paged_raw = filter_input(INPUT_GET, 'paged', FILTER_UNSAFE_RAW);
 	$paged = is_string($paged_raw) ? (int) sanitize_text_field(wp_unslash($paged_raw)) : 1;
 	$paged = max(1, $paged);
 	$per_page = 50;

 	$logs = [];
 	$total = 0;

 	if ( $table_ready && class_exists('\\F152\\ConsentLog') ) {
 		$logs = \F152\ConsentLog::get_logs($paged, $per_page);
 		$total = \F152\ConsentLog::get_total_count();
 	}

 	$total_pages = (int) ceil($total / $per_page);

 	$type_labels = [
 	'comment'           => 'Комментарий',
 	'review'            => 'Отзыв',
 	'order'             => 'Заказ',
 	'register'          => 'Регистрация',
 	'contact_form_7'    => 'Contact Form 7',
 	'contact-form-7'    => 'Contact Form 7',
 	'cf7'               => 'Contact Form 7',
 	'wpforms'           => 'WPForms',
 	'wpdiscuz'          => 'wpDiscuz',
 	'elementor_forms'   => 'Elementor Forms',
 	'elementor-forms'   => 'Elementor Forms',
 	'forminator'        => 'Forminator',
 	'fluent_forms'      => 'Fluent Forms',
 	'fluent-forms'      => 'Fluent Forms',
 	'metform'           => 'MetForm',
 	'met_form'          => 'MetForm',
 	'ninja_forms'       => 'Ninja Forms',
 	'ninja-forms'       => 'Ninja Forms',
 	'gravity_forms'     => 'Gravity Forms',
 	'gravity-forms'     => 'Gravity Forms',
 	'universal_form'    => 'Универсальная форма',
 	'universal'         => 'Универсальная форма',
 	'custom_form_rule'  => 'Пользовательское правило',
 	'custom'            => 'Пользовательская форма',
 	'external'          => 'Внешняя форма',
 	];

 	$export_url = add_query_arg([
 			'action' => 'f152_export_csv',
 			'f152_export_nonce' => wp_create_nonce('f152_export_csv'),
 		], admin_url('admin.php'));

 	$clear_url = add_query_arg([
 			'action' => 'f152_clear_logs',
 			'f152_clear_nonce' => wp_create_nonce('f152_clear_logs'),
 		], admin_url('admin.php'));
 	?>
 	<div class="f152-logs-wrap">
 		<h2>Журнал согласий</h2>
		<div class="f152-onboarding__notice">
			<h3>Что попадает в журнал</h3>
			<p>В журнал не записываются принятия или отказы от cookie. Здесь сохраняются согласия на обработку персональных данных, согласия на рассылку и произвольные подтверждения из поддерживаемых форм. Для необязательного произвольного подтверждения фиксируется и ответ «Да», и ответ «Нет».</p>
		</div>

 		<?php if ( ! $table_ready ) : ?>
 			<div class="notice notice-error">
 				<p>Не удалось создать таблицу логов. Обратитесь к администратору сайта.</p>
 			</div>
 		<?php else : ?>
 			<?php
 			$cleared_raw = filter_input(INPUT_GET, 'f152_cleared', FILTER_UNSAFE_RAW);
 			$cleared = is_string($cleared_raw) ? sanitize_text_field(wp_unslash($cleared_raw)) : '';
 			if ( $cleared === '1' ) : ?>
 				<div class="notice notice-success is-dismissible">
 					<p>Журнал согласий успешно очищен.</p>
 				</div>
 			<?php endif; ?>

 			<div class="f152-log-actions">
 				<a href="<?php echo esc_url($export_url); ?>" class="button f152-log-actions__export">Экспорт в CSV</a>
 				<a href="<?php echo esc_url($clear_url); ?>" class="button f152-log-actions__clear" onclick="return confirm('Вы уверены, что хотите очистить журнал согласий? Это действие необратимо.');">Очистить журнал</a>
 			</div>

 			<?php if ( empty($logs) ) : ?>
 				<p>Записей пока нет.</p>
 			<?php else : ?>
 				<div class="f152-log-table-card">
				<table class="widefat f152-log-table">
 					<thead>
 						<tr>
 							<th>Дата и время</th>
 							<th>Источник</th>
 							<th>Событие</th>
 							<th>Страница</th>
 							<th>Email</th>
 							<th>ФИО</th>
 							<th>Телефон</th>
 							<th>IP</th>
 							<th>Версия политики</th>
 						</tr>
 					</thead>
 					<tbody>
 						<?php foreach ( $logs as $row ) : ?>
 							<?php

 							$src_type_raw = (string) ( $row['source_type'] ?? '' );
 							$src_type_lbl = $src_type_raw !== '' && isset( $type_labels[ $src_type_raw ] )
 								? $type_labels[ $src_type_raw ]
 								: $src_type_raw;

 							$src_plugin_name = trim( (string) ( $row['source_label'] ?? '' ) );
 							if ( $src_plugin_name === '' ) {
 								$src_plugin_name = $src_type_lbl;
 							}

 							$src_uid    = trim( (string) ( $row['source_uid'] ?? '' ) );
 							$src_id_int = (int) ( $row['source_id'] ?? 0 );
 							$form_id    = trim( (string) ( $row['form_id'] ?? '' ) );
 							$is_wpdiscuz = ( $src_type_raw === 'wpdiscuz' );

 							$src_identity = '';
 							if ( $is_wpdiscuz ) {
 								if ( $src_id_int > 0 ) {
 									$src_identity = 'ID комментария: ' . $src_id_int;
 								} elseif ( $src_uid !== '' ) {
 									$uid_parts = explode( ':', $src_uid );
 									$uid_tail  = end( $uid_parts );
 									if ( $uid_tail !== '' && ctype_digit( $uid_tail ) ) {
 										$src_identity = 'ID комментария: ' . $uid_tail;
 									} else {
 										$src_identity = 'ID комментария: ' . $src_uid;
 									}
 								}
 							} else {

 								if ( $form_id !== '' ) {
 									$src_identity = $form_id;
 								} elseif ( $src_uid !== '' ) {
 									$uid_parts = explode( ':', $src_uid );
 									if ( count( $uid_parts ) > 1 ) {
 										$src_identity = implode( ':', array_slice( $uid_parts, 1 ) );
 									} else {
 										$src_identity = $src_uid;
 									}
 								} elseif ( $src_id_int > 0 ) {
 									$src_identity = (string) $src_id_int;
 								}
 								if ( $src_identity !== '' ) {
 									$src_identity = 'ID формы: ' . $src_identity;
 								}
 							}

 							$source_lines   = [];
 							$source_lines[] = $src_plugin_name;
 							if ( $src_identity !== '' ) {
 								$source_lines[] = $src_identity;
 							}
 
 							$event_meta = self::get_log_event_meta( is_array( $row ) ? $row : [] );
 							$consent_text_plain = trim( wp_strip_all_tags( (string) ( $row['consent_text'] ?? '' ) ) );
 							$consent_text_short = $consent_text_plain;
 							if ( function_exists( 'mb_strlen' ) && mb_strlen( $consent_text_short ) > 90 ) {
 								$consent_text_short = mb_substr( $consent_text_short, 0, 90 ) . '…';
 							} elseif ( strlen( $consent_text_short ) > 90 ) {
 								$consent_text_short = substr( $consent_text_short, 0, 90 ) . '…';
 							}

 							$page_url_raw = trim( (string) ( $row['page_url'] ?? '' ) );
 							$page_url_esc = $page_url_raw !== '' ? esc_url( $page_url_raw ) : '';
 							?>
 							<tr>
 								<td><?php echo esc_html( $row['created_at'] ?? '' ); ?></td>
 								<td class="f152-log-table__source">
									<div class="f152-log-source">
 									<?php foreach ( $source_lines as $sl_index => $sl_value ) : ?>
 										<?php if ( $sl_index > 0 ) : ?><br><span class="description"><?php endif; ?>
 										<?php echo esc_html( $sl_value ); ?>
 										<?php if ( $sl_index > 0 ) : ?></span><?php endif; ?>
 									<?php endforeach; ?>
									</div>
 								</td>
								<td class="f152-log-table__event">
									<div class="f152-log-event">
										<span class="f152-log-event__kind"><?php echo esc_html( $event_meta['label'] ); ?></span>
										<span class="f152-log-decision <?php echo esc_attr( $event_meta['decision_class'] ); ?>"><?php echo esc_html( $event_meta['decision_label'] ); ?></span>
									</div>
									<?php if ( $event_meta['kind'] === 'custom' ) : ?>
										<div class="f152-log-event__meta"><?php echo esc_html( $event_meta['required'] === '1' ? 'Обязательное' : 'Необязательное' ); ?></div>
										<?php if ( $consent_text_short !== '' ) : ?>
											<div class="f152-log-event__text" title="<?php echo esc_attr( $consent_text_plain ); ?>"><?php echo esc_html( $consent_text_short ); ?></div>
										<?php endif; ?>
									<?php endif; ?>
								</td>
 								<td>
 									<?php if ( $page_url_esc !== '' ) : ?>
 										<?php
 										$short_url = mb_strlen( $page_url_raw ) > 40 ? mb_substr( $page_url_raw, 0, 40 ) . '…' : $page_url_raw;
 										?>
 										<a href="<?php echo esc_attr( $page_url_esc ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $page_url_raw ); ?>">
 											<?php echo esc_html( $short_url ); ?>
 										</a>
 									<?php else : ?>
 										&mdash;
 									<?php endif; ?>
 								</td>
 								<td><?php echo esc_html( $row['email'] ?? '' ); ?></td>
 								<td><?php echo esc_html( $row['full_name'] ?? '' ); ?></td>
 								<td><?php if ( ! empty( $row['phone'] ) ) : ?>
 									<?php echo esc_html( $row['phone'] ); ?>
 								<?php else : ?>
 									&mdash;
 								<?php endif; ?></td>
 								<td><?php echo esc_html( $row['ip_address'] ?? '' ); ?></td>
 								<td><?php echo ! empty( $row['policy_version'] ) ? esc_html( $row['policy_version'] ) : '&mdash;'; ?></td>
 							</tr>
 						<?php endforeach; ?>
 					</tbody>
 				</table>
				</div>

 				<?php if ( $total_pages > 1 ) : ?>
 					<div class="tablenav bottom">
 						<div class="tablenav-pages">
 							<?php
 							echo wp_kses_post( paginate_links([
 								'base'      => add_query_arg('paged', '%#%', self::tab_url('logs')),
 								'format'    => '',
 								'prev_text' => '&laquo;',
 								'next_text' => '&raquo;',
 								'total'     => $total_pages,
 								'current'   => $paged,
 							]) );
 							?>
 						</div>
 					</div>
 				<?php endif; ?>
 			<?php endif; ?>
 		<?php endif; ?>
 	</div>
 	<?php
 }

 private static function csv_safe_cell( $value ): string {
 	if ( is_array( $value ) || is_object( $value ) ) {
 		$json = wp_json_encode( $value, defined( 'JSON_UNESCAPED_UNICODE' ) ? JSON_UNESCAPED_UNICODE : 0 );
 		$value = is_string( $json ) ? $json : '';
 	} elseif ( $value === null ) {
 		$value = '';
 	} else {
 		$value = (string) $value;
 	}

 	if ( $value === '' ) {
 		return '';
 	}

 	$first = $value[0];

 	if ( $first === '=' || $first === '+' || $first === '-' || $first === '@'
 		|| $first === "\t" || $first === "\r" ) {
 		$value = "'" . $value;
 	}

 	return $value;
 }

 private static function parse_log_context( $context ): array {
	if ( is_array( $context ) ) return $context;
	if ( ! is_string( $context ) || trim( $context ) === '' ) return [];
	$decoded = json_decode( $context, true );
	return is_array( $decoded ) ? $decoded : [];
 }

 private static function get_log_event_meta( array $row ): array {
	$context = self::parse_log_context( $row['context'] ?? '' );
	$kind = isset( $context['consent_kind'] ) && is_scalar( $context['consent_kind'] ) ? sanitize_key( (string) $context['consent_kind'] ) : 'personal';
	if ( $kind === 'marketing' ) { $label = 'Рассылка'; }
	elseif ( $kind === 'custom' ) { $label = 'Своё подтверждение'; }
	else { $kind = 'personal'; $label = 'Обработка ПД'; }
	$decision = isset( $context['decision'] ) && is_scalar( $context['decision'] ) ? sanitize_key( (string) $context['decision'] ) : 'accepted';
	$accepted = $decision !== 'declined';
	$required = $kind === 'custom' ? ( ! empty( $context['required'] ) ? '1' : '0' ) : '';
	return [
		'kind' => $kind, 'label' => $label,
		'decision' => $accepted ? 'accepted' : 'declined',
		'decision_label' => $accepted ? 'Да' : 'Нет',
		'decision_class' => $accepted ? 'is-accepted' : 'is-declined',
		'required' => $required,
	];
 }

 private static function csv_context_to_string( $context ): string {
 	if ( $context === null || $context === '' ) {
 		return '';
 	}

 	if ( is_string( $context ) ) {
 		return $context;
 	}

 	if ( is_array( $context ) || is_object( $context ) ) {
 		$json = wp_json_encode( $context, defined( 'JSON_UNESCAPED_UNICODE' ) ? JSON_UNESCAPED_UNICODE : 0 );
 		return is_string( $json ) && $json !== 'null' ? $json : '';
 	}

 	return (string) $context;
 }
}
