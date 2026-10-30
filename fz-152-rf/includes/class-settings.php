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
		CookieConsentLog::register_settings();
                add_action( 'admin_post_f152_rescan_site', [ __CLASS__, 'handle_rescan_site' ] );
                add_action( 'wp_ajax_f152_browser_scan_home', [ __CLASS__, 'handle_browser_scan_home' ] );
                add_action( 'wp_ajax_f152_browser_scan_site', [ __CLASS__, 'handle_browser_scan_site' ] );

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
			'sanitize_callback' => [API::class, 'sanitize_policy_version'],
			'default'           => API::DEFAULT_POLICY_VERSION,
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

                if ( class_exists( '\F152\FeatureRequest' ) ) {
                        wp_localize_script(
                                'f152-admin',
                                'F152FeatureRequest',
                                [
                                        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                                        'action'    => \F152\FeatureRequest::ajax_action(),
                                        'nonce'     => \F152\FeatureRequest::nonce(),
                                        'maxLength' => 4000,
                                ]
                        );
                }

                $tab_raw = filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW );
                $tab     = is_string( $tab_raw ) ? sanitize_key( wp_unslash( $tab_raw ) ) : 'main';

                if ( 'api' === $tab ) {
                        wp_enqueue_script(
                                'f152-api-admin',
                                F152_URL . 'assets/js/api-admin.js',
                                [],
                                Assets::asset_version( 'assets/js/api-admin.js' ),
                                true
                        );
                        wp_localize_script(
                                'f152-api-admin',
                                'F152DeveloperAPIAdmin',
                                [
                                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                                        'nonce'   => wp_create_nonce( 'f152_developer_api_admin' ),
                                ]
                        );
                }

                $force_scan_raw = filter_input( INPUT_GET, 'f152_force_scan', FILTER_UNSAFE_RAW );
                $force_scan = is_string( $force_scan_raw ) && '1' === sanitize_text_field( wp_unslash( $force_scan_raw ) );

                if ( in_array( $tab, [ 'main', 'rkn' ], true ) ) {
                        $scan_plan = class_exists( '\F152\ServiceScanner' ) && is_callable( [ '\F152\ServiceScanner', 'get_browser_scan_plan' ] )
                                ? \F152\ServiceScanner::get_browser_scan_plan()
                                : [ 'targets' => [ [ 'url' => home_url( '/' ), 'title' => 'Главная страница', 'home' => true ] ], 'total_pages' => 1, 'truncated' => false ];
                        wp_localize_script(
                                'f152-admin',
                                'F152AdminScanner',
                                [
                                        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                                        'nonce'     => wp_create_nonce( 'f152_browser_scan_site' ),
                                        'targets'   => array_values( (array) ( $scan_plan['targets'] ?? [] ) ),
                                        'totalPages'=> (int) ( $scan_plan['total_pages'] ?? 0 ),
                                        'truncated' => ! empty( $scan_plan['truncated'] ),
                                        'autoRun'   => $force_scan || ( 'rkn' === $tab ? ( (bool) get_option( 'f152_pro_rkn_pending_rebuild', 0 ) || ! get_option( 'f152_pro_rkn_signals', [] ) ) : ( class_exists( '\F152\ServiceScanner' ) && is_callable( [ '\F152\ServiceScanner', 'has_fresh_browser_snapshot' ] )
                                                ? ! \F152\ServiceScanner::has_fresh_browser_snapshot()
                                                : true ) ),
                                        'forceRun'  => $force_scan,
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

		$allowed_tabs = apply_filters( 'f152/admin/allowed_tabs', [ 'main', 'services', 'texts', 'policy_pd', 'consent_pd', 'policy_cookie', 'consent_marketing', 'logs', 'stats', 'integrations', 'rkn', 'languages', 'custom_css', 'api' ] );
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
				<div class="f152-settings-header__actions">
					<?php self::render_feature_request_cta(); ?>
					<?php self::render_review_cta(); ?>
				</div>
			</div>

			<?php if ( class_exists( '\\F152\\Survey' ) ) { \F152\Survey::render(); } ?>

			<h2 class="nav-tab-wrapper f152-nav-tabs">
			                             <a href="<?php echo esc_url( self::tab_url('main') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='main'?'nav-tab-active':'' ); ?>">Основные настройки</a>
			                             <a href="<?php echo esc_url( self::tab_url('services') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='services'?'nav-tab-active':'' ); ?>">Сервисы и трекеры</a>
			                             <a href="<?php echo esc_url( self::tab_url('texts') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='texts'?'nav-tab-active':'' ); ?>">Тексты</a>
			                             <a href="<?php echo esc_url( self::tab_url('logs') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='logs'?'nav-tab-active':'' ); ?>">Журнал согласий</a>
			                             <a href="<?php echo esc_url( self::tab_url('stats') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='stats'?'nav-tab-active':'' ); ?>">Статистика</a>
			                             <a href="<?php echo esc_url( self::tab_url('integrations') ); ?>" class="nav-tab f152-nav-tab-pro <?php echo esc_attr( $tab==='integrations'?'nav-tab-active':'' ); ?>"><?php echo esc_html( apply_filters( 'f152_integrations_tab_label', 'Add-on' ) ); ?></a>
			                             <a href="<?php echo esc_url( self::tab_url('rkn') ); ?>" class="nav-tab f152-nav-tab-pro <?php echo esc_attr( $tab==='rkn'?'nav-tab-active':'' ); ?>">Роскомнадзор</a>
			                             <a href="<?php echo esc_url( self::tab_url('languages') ); ?>" class="nav-tab f152-nav-tab-pro <?php echo esc_attr( $tab==='languages'?'nav-tab-active':'' ); ?>">Языки</a>
			                             <?php do_action( 'f152/admin/tabs', $tab ); ?>
			                             <a href="<?php echo esc_url( self::tab_url('custom_css') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='custom_css'?'nav-tab-active':'' ); ?>">Кастомный CSS</a>
			                             <a href="<?php echo esc_url( self::tab_url('api') ); ?>" class="nav-tab <?php echo esc_attr( $tab==='api'?'nav-tab-active':'' ); ?>">API для разработчиков</a>
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
			} elseif ( $tab === 'languages' ) {
				if ( has_action( 'f152/admin/render_languages' ) ) {
					do_action( 'f152/admin/render_languages' );
				} else {
					self::render_locked_languages_tab();
				}
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
			} elseif ( $tab === 'api' ) {
				self::render_tab_api();
			} elseif ( $tab === 'rkn' ) {
				if ( has_action( 'f152_render_settings_tab_rkn' ) ) {
					do_action( 'f152_render_settings_tab_rkn' );
				} else {
					self::render_locked_rkn_tab();
				}
			}
			?>
			<?php self::render_feature_request_modal(); ?>
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
					<h3>Яндекс.Карты и отзывы</h3>
					<p class="description">FZ-152 RF управляет загрузкой карт из конструктора, iframe Яндекс.Карт и официального виджета отзывов Яндекс.Карт (maps-reviews-widget). Для всех действует один режим по аналитическим cookie.</p>
				</div>

				<?php $yandex_maps_behavior = self::get_yandex_maps_behavior(); ?>
				<div class="f152-field">
					<label for="f152_yandex_maps_behavior">Показ карт и отзывов Яндекса</label>
					<select id="f152_yandex_maps_behavior" name="f152_yandex_maps_behavior">
						<option value="always" <?php selected( $yandex_maps_behavior, 'always' ); ?>>Всегда показывать</option>
						<option value="disable_on_reject" <?php selected( $yandex_maps_behavior, 'disable_on_reject' ); ?>>Скрывать после нажатия «Отклонить»</option>
						<option value="require_accept" <?php selected( $yandex_maps_behavior, 'require_accept' ); ?>>Показывать только после «Принять»</option>
					</select>
					<small>
						<b>Всегда показывать</b> — карты и отзывы загружаются независимо от решения посетителя.<br>
						<b>Скрывать после нажатия «Отклонить»</b> — карты и отзывы доступны до явного отказа от аналитических cookie; после отказа вместо них показывается заглушка.<br>
						<b>Показывать только после «Принять»</b> — код конструктора/iframe карты и виджета отзывов не загружается до явного разрешения аналитических cookie.<br>
						Если виджет скрыт, кнопка «Разрешить и показать карту» или «Разрешить и показать отзывы» включает только аналитические cookie и сразу загружает виджет, не включая маркетинговые cookie.<br>
						Поддерживается стандартный iframe-код Яндекс Отзывов, присутствующий в HTML страницы. Виджеты, добавляемые сторонними скриптами уже после загрузки страницы, могут требовать отдельной интеграции.<br>
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
					<?php self::render_custom_services_pro_preview(); ?>
					<?php self::render_custom_iframe_pro_preview(); ?>
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'jivosite' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => '2gis' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'google_maps' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'vk_video' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'rutube' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'youtube' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'dzen_video' ], F152_PURCHASE_URL ) );
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

	 
	private static function render_custom_services_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'custom_services' ], F152_PURCHASE_URL ) );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>Пользовательские сервисы и скрипты</h3>
					<p class="description">Подключение собственного кода сторонних сервисов с управлением загрузкой по категориям cookie и решению посетителя.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>
			<div class="f152-field">
				<span class="f152-service-preview__label">Название, категория, режим загрузки и код скрипта</span>
				<div class="f152-service-preview__control" aria-hidden="true">Собственный сервис · включать после согласия</div>
			</div>
			<p class="description f152-service-card__pro-note">Пользовательские скрипты доступны в FZ-152 RF Pro. В Free этот блок служит только для ознакомления — код не запускается.</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_custom_iframe_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'custom_iframes' ], F152_PURCHASE_URL ) );
		?>
		<div class="f152-service-card f152-service-card--pro-preview" data-f152-service-category="trackers">
			<div class="f152-service-card__heading">
				<div>
					<h3>Пользовательские iframe</h3>
					<p class="description">Управление загрузкой сторонних iframe по адресу или домену: отдельные правила, категории cookie и режимы согласия.</p>
				</div>
				<span class="f152-service-card__badge">PRO</span>
			</div>
			<div class="f152-field">
				<span class="f152-service-preview__label">URL или домен iframe</span>
				<div class="f152-service-preview__control f152-service-preview__control--select" aria-hidden="true">widget.example.com/embed/</div>
			</div>
			<p class="description f152-service-card__pro-note">Произвольные iframe на страницах управляются дополнением FZ-152 RF Pro. В базовой версии доступны только встроенные поддерживаемые сервисы.</p>
			<?php if ( ! empty( $buy_url ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о Pro</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_ga4_pro_preview() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'ga4' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'gtm' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'roistat' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'digital_culture' ], F152_PURCHASE_URL ) );
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
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'services_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'vk_ads' ], F152_PURCHASE_URL ) );
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

	private static function render_tab_api() : void {
		$api_version = class_exists( '\F152\API' ) ? API::get_api_version() : '1.0';
		$tokens = class_exists( '\F152\DeveloperAPI' ) ? DeveloperAPI::admin_tokens() : [];
		$max_tokens = class_exists( '\F152\DeveloperAPI' ) ? DeveloperAPI::max_tokens() : 0;
		$status_url = rest_url( 'f152/v1/status' );
		$consents_url = rest_url( 'f152/v1/consents' );
		?>
		<div class="f152-settings-stack f152-api-docs">
			<section class="f152-settings-card">
				<div class="f152-settings-card__header">
					<div>
						<span class="f152-card-eyebrow">Public API v<?php echo esc_html( $api_version ); ?></span>
						<h2>API для разработчиков</h2>
						<p>По предложениям разработчиков других плагинов и некоторых SaaS сервисов добавил API. Теперь вы можете добавить свои интеграции с плагином и не создавать лишнего кода в своих проектах. Если вам не хватает функционала, напишите мне на почту <a href="mailto:support@fz152rf.ru">support@fz152rf.ru</a>. Если используете нейросети для разработки, то скопируйте текст с этой страницы и вставьте в свою нейросеть, она подскажет вам, как правильно создать интеграцию своего плагина или сервиса с ФЗ 152.</p>
						<p>Интегрируйте свои формы, плагины, CRM и SaaS с FZ-152 RF без прямого доступа к внутренним классам и базе данных.</p>
					</div>
				</div>
				<div class="f152-settings-card__body">
					<div class="notice notice-warning inline"><p><strong>Граница безопасности:</strong> удалённый REST API работает только на чтение. Через токен нельзя создать, изменить или удалить запись журнала. Для записи согласия используйте PHP API внутри WordPress.</p></div>
					<p><strong>Стабильный контракт:</strong> функции <code>f152_*</code>, перечисленные на этой странице, считаются публичными. Классы пространства имён <code>F152\*</code> — внутренние и могут меняться.</p>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">PHP</span><h2>Безопасная интеграция формы</h2><p>Для новых интеграций используйте одну высокоуровневую функцию: она сама проверит согласие и запишет доказательную часть на сервере.</p></div></div>
				<div class="f152-settings-card__body">
					<h3>1. Выведите чекбокс</h3>
					<pre class="f152-api-code"><code><?php echo esc_html("echo f152_get_consent_checkbox_html( [\n    'source_type'   => 'my_plugin',\n    'source_id'     => (string) \$form_id,\n    'source_plugin' => 'my-plugin/my-plugin.php',\n    'source_label'  => 'My Plugin',\n    'form_id'       => (string) \$form_id,\n    'form_title'    => \$form_title,\n] );"); ?></code></pre>
					<h3>2. В серверном обработчике после своей валидации/антиспама, но до принятия заявки проверьте и запишите согласие</h3>
					<pre class="f152-api-code"><code><?php echo esc_html("\$result = f152_process_external_consent( \$_POST, [\n    'source_type'   => 'my_plugin',\n    'source_id'     => (string) \$form_id,\n    'source_plugin' => 'my-plugin/my-plugin.php',\n    'source_label'  => 'My Plugin',\n    'form_id'       => (string) \$form_id,\n    'form_title'    => \$form_title,\n    'email'         => \$email,\n    'full_name'     => \$name,\n    'phone'         => \$phone,\n    'log_context'   => [ 'submission_id' => \$submission_id ],\n] );\n\nif ( is_wp_error( \$result ) ) {\n    // Не помечайте отправку как согласованную. Обработайте ошибку по логике своей формы.\n}"); ?></code></pre>
					<p>Сначала выполните штатную серверную валидацию и антиспам своей формы. Затем вызовите FZ-152 до финального принятия/отправки заявки. Если метод вернул <code>WP_Error</code>, обработчик не должен продолжать отправку. Успешный вызов фиксирует событие согласия; он не является подтверждением успешной доставки самой заявки.</p>
					<p>Поля <code>IP</code>, <code>User-Agent</code>, <code>user_id</code>, текст и версия согласия, URL политики, timestamp и <code>consent_hash</code> формируются FZ-152 на сервере и не принимаются от интеграции.</p>
					<p><strong>Для безопасного high-level flow используйте стандартный текст согласия FZ-152:</strong> не передавайте в <code>f152_get_consent_checkbox_html()</code> собственные <code>text</code> или нестандартный <code>context</code>. Если интеграции нужен отдельный юридический текст, используйте специализированный адаптер и низкоуровневый API с собственной серверной проверкой.</p>
					<p><code>log_context</code> предназначен только для небольшой служебной метаинформации. Не передавайте туда весь <code>$_POST</code>, пароли, платёжные реквизиты, cookies, API-ключи или токены. FZ-152 отбрасывает очевидно секретные ключи и ограничивает размер context, но интеграция всё равно должна соблюдать принцип минимизации данных.</p>
					<details><summary><strong>Низкоуровневые функции совместимости</strong></summary><p><code>f152_verify_consent()</code> и <code>f152_log_external_consent()</code> сохранены без изменения для существующих интеграций FZ-152 RF Pro. Для нового кода предпочтительнее <code>f152_process_external_consent()</code>, чтобы случайно не записать непроверенное согласие.</p></details>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">PHP reference</span><h2>Публичные функции</h2></div></div>
				<div class="f152-settings-card__body">
					<table class="widefat striped"><tbody>
					<tr><td><code>f152_get_api_version()</code></td><td>Версия публичного API.</td></tr>
					<tr><td><code>f152_get_capabilities()</code></td><td>Карта доступных возможностей API.</td></tr>
					<tr><td><code>f152_has_capability( $name, $min_version )</code></td><td>Проверка конкретной возможности без привязки к версии плагина.</td></tr>
					<tr><td><code>f152_get_consent_checkbox_html( $args )</code></td><td>HTML стандартного чекбокса с nonce и контекстом.</td></tr>
					<tr><td><code>f152_render_consent_checkbox( $args )</code></td><td>Вывод стандартного чекбокса.</td></tr>
					<tr><td><code>f152_process_external_consent( $request, $integration )</code></td><td>Рекомендуемый безопасный verify + log. Возвращает ID записи или <code>WP_Error</code>.</td></tr>
					<tr><td><code>f152_verify_consent( $request, $args )</code></td><td>Низкоуровневая проверка nonce и чекбокса. Сохранена для совместимости существующих интеграций.</td></tr>
					<tr><td><code>f152_log_external_consent( $data )</code></td><td>Низкоуровневая запись. Не проверяет согласие сама; для нового кода используйте <code>f152_process_external_consent()</code>.</td></tr>
					<tr><td><code>f152_get_consent_text( $context )</code></td><td>Текущий серверный текст согласия.</td></tr>
					<tr><td><code>f152_get_policy_version()</code></td><td>Текущая версия политики.</td></tr>
					<tr><td><code>f152_get_policy_url()</code></td><td>URL политики обработки ПД.</td></tr>
					<tr><td><code>f152_get_cookie_consent_state()</code></td><td>Состояние cookie-согласия текущего посетителя.</td></tr>
					<tr><td><code>f152_is_cookie_category_allowed( 'analytics' )</code></td><td>Разрешена ли категория <code>analytics</code> или <code>marketing</code>.</td></tr>
					<tr><td><code>f152_has_current_cookie_consent()</code></td><td>Есть ли выбор для текущей версии политики.</td></tr>
					<tr><td><code>f152_get_cookie_consent_version()</code></td><td>Версия cookie-согласия.</td></tr>
					</tbody></table>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">JavaScript</span><h2>JS API</h2><p>JS API предназначен для чтения выбора посетителя и открытия штатных настроек. Запись журнала из браузера намеренно отсутствует.</p></div></div>
				<div class="f152-settings-card__body">
					<pre class="f152-api-code"><code><?php echo esc_html("F152Consent.getState();\nF152Consent.isAllowed('analytics');\nF152Consent.isAllowed('marketing');\nF152Consent.hasCurrentConsent();\nF152Consent.getVersion();\nF152Consent.openSettings();\n\ndocument.addEventListener('f152:consent-changed', function (event) {\n    console.log(event.detail);\n});"); ?></code></pre>
					<p><code>allowCategory()</code> используется внутренними компонентами FZ-152 для кнопок «разрешить и показать» и не является частью публичного стабильного API.</p>
				</div>
			</section>

			<section class="f152-settings-card" id="f152-api-tokens">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Server-to-server</span><h2>Токены удалённого API</h2><p>Для CRM и SaaS. Токен передаётся только в HTTP-заголовке и даёт только чтение.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="notice notice-warning inline"><p><strong>Используйте HTTPS.</strong> Никогда не помещайте токен в JavaScript сайта, HTML, URL или query string. Храните его только на сервере подключаемой CRM/SaaS. В WordPress сохраняется только HMAC-хэш токена.</p></div>
					<div data-f152-api-message hidden></div>
					<form data-f152-api-create-form>
						<p><label><strong>Название токена</strong><br><input type="text" name="token_name" class="regular-text" maxlength="100" placeholder="Например: CRM production" required></label></p>
						<p><label><input type="checkbox" name="read_consents" value="1"> Разрешить чтение журнала согласий</label><br><span class="description">Включает email, имя, телефон, источник формы, текст/версию согласия и связанные URL.</span></p>
						<p><label><input type="checkbox" name="read_technical" value="1" disabled> Также отдавать технические поля журнала</label><br><span class="description">IP-адрес, User-Agent, WordPress user_id и context. Включайте только если сервису они действительно нужны.</span></p>
						<?php submit_button( 'Создать токен', 'secondary', 'submit', false ); ?>
					</form>
					<div data-f152-api-token-result hidden style="margin-top:16px;">
						<p><strong>Скопируйте токен сейчас. Повторно показать его будет невозможно.</strong></p>
						<pre class="f152-api-code"><code data-f152-api-token-value></code></pre>
						<p><button type="button" class="button" data-f152-api-copy>Копировать</button></p>
					</div>

					<h3>Активные токены</h3>
					<p class="description">Максимум токенов: <?php echo esc_html( (string) $max_tokens ); ?>. Секреты здесь не отображаются.</p>
					<table class="widefat striped"><thead><tr><th>Название</th><th>Разрешения</th><th>Создан</th><th></th></tr></thead><tbody>
					<?php if ( empty( $tokens ) ) : ?>
						<tr><td colspan="4">Токенов пока нет.</td></tr>
					<?php else : foreach ( $tokens as $token ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $token['name'] ); ?><br><code><?php echo esc_html( substr( (string) $token['id'], 0, 8 ) ); ?>…</code></td>
							<td><?php echo esc_html( implode( ', ', (array) $token['scopes'] ) ); ?></td>
							<td><?php echo esc_html( (string) $token['created_at'] ); ?></td>
							<td><button type="button" class="button button-link-delete" data-f152-api-revoke data-token-id="<?php echo esc_attr( (string) $token['id'] ); ?>">Отозвать</button></td>
						</tr>
					<?php endforeach; endif; ?>
					</tbody></table>
				</div>
			</section>

			<section class="f152-settings-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">REST read-only</span><h2>Удалённое чтение</h2></div></div>
				<div class="f152-settings-card__body">
					<p><strong>Статус:</strong> <code><?php echo esc_html( $status_url ); ?></code></p>
					<p><strong>Журнал:</strong> <code><?php echo esc_html( $consents_url ); ?></code></p>
					<pre class="f152-api-code"><code><?php echo esc_html("curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n  \"{$consents_url}?after_id=123&limit=50&order=asc\""); ?></code></pre>
					<p>Если хостинг удаляет заголовок <code>Authorization</code>, используйте <code>X-F152-Token: YOUR_TOKEN</code>. Токены в URL намеренно не поддерживаются.</p>
					<p><strong>Удалённый API работает только на HTTPS и только server-to-server.</strong> Запросы с браузерным заголовком <code>Origin</code> отклоняются. Не вызывайте эти endpoints из JavaScript.</p>
					<p>Параметры списка: <code>limit</code> (1–100), <code>after_id</code>, <code>before_id</code>, <code>order=asc|desc</code>, <code>source_type</code>, <code>source_plugin</code>, <code>source_uid</code>, <code>created_after</code>, <code>created_before</code>.</p>
					<p>Обычное разрешение чтения журнала возвращает ID, дату, источник, страницу/форму, email, имя, телефон, текст и версию согласия, URL политики, hash и нормализованные <code>consent_kind</code>/<code>decision</code>/<code>required</code>. Техническое разрешение дополнительно открывает <code>IP</code>, <code>User-Agent</code>, <code>user_id</code> и raw <code>context</code>.</p>
					<p>Для синхронизации CRM удобно читать <code>order=asc</code> и передавать следующий <code>after_id</code> из ответа. Поле <code>has_more</code> показывает, есть ли следующая пачка. API не предоставляет удалённых методов POST/PUT/PATCH/DELETE.</p>
					<p class="description">Ответы помечаются <code>no-store</code>. Смена WordPress salts в <code>wp-config.php</code> делает ранее созданные API-токены недействительными — после такой смены создайте новые токены.</p>
				</div>
			</section>
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

	public static function handle_browser_scan_site(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Недостаточно прав.' ], 403 );
		}

		check_ajax_referer( 'f152_browser_scan_site', 'nonce' );
		$mode_raw = filter_input( INPUT_POST, 'mode', FILTER_UNSAFE_RAW );
		$mode = is_string( $mode_raw ) ? sanitize_key( wp_unslash( $mode_raw ) ) : '';
		if ( ! class_exists( '\F152\ServiceScanner' ) ) {
			wp_send_json_error( [ 'message' => 'Сканер недоступен.' ], 500 );
		}

		if ( 'begin' === $mode ) {
			if ( is_callable( [ '\\F152\\ServiceScanner', 'acquire_browser_scan_lock' ] ) && ! ServiceScanner::acquire_browser_scan_lock() ) {
				wp_send_json_error( [ 'message' => 'Проверка сайта уже выполняется в другой вкладке или только что была запущена. Дождитесь её завершения.' ], 409 );
			}
			try {
				ServiceScanner::begin_browser_scan();
			} catch ( \Throwable $e ) {
				if ( is_callable( [ '\\F152\\ServiceScanner', 'release_browser_scan_lock' ] ) ) ServiceScanner::release_browser_scan_lock();
				throw $e;
			}
			wp_send_json_success( [ 'started' => true ] );
		}

		if ( 'page' === $mode ) {
			if ( is_callable( [ '\\F152\\ServiceScanner', 'touch_browser_scan_lock' ] ) ) ServiceScanner::touch_browser_scan_lock();
			$url_raw = filter_input( INPUT_POST, 'url', FILTER_UNSAFE_RAW );
			$title_raw = filter_input( INPUT_POST, 'title', FILTER_UNSAFE_RAW );
			$home_raw = filter_input( INPUT_POST, 'home', FILTER_UNSAFE_RAW );
			$html_raw = filter_input( INPUT_POST, 'html', FILTER_UNSAFE_RAW );
			$url = is_string( $url_raw ) ? esc_url_raw( wp_unslash( $url_raw ) ) : '';
			$title = is_string( $title_raw ) ? sanitize_text_field( wp_unslash( $title_raw ) ) : '';
			$home = is_string( $home_raw ) && '1' === sanitize_text_field( wp_unslash( $home_raw ) );
			$html = is_string( $html_raw ) ? wp_unslash( $html_raw ) : '';
			if ( '' === $url || '' === trim( $html ) || ! ServiceScanner::store_browser_page_html( $url, $title, $html, $home ) ) {
				wp_send_json_error( [ 'message' => 'Не удалось сохранить результат страницы.' ], 400 );
			}
			wp_send_json_success( [ 'stored' => true ] );
		}

		if ( 'heartbeat' === $mode ) {
			if ( is_callable( [ '\\F152\\ServiceScanner', 'touch_browser_scan_lock' ] ) ) ServiceScanner::touch_browser_scan_lock();
			wp_send_json_success( [ 'alive' => true ] );
		}

		if ( 'finish' === $mode ) {
			$failures_raw = filter_input( INPUT_POST, 'failures', FILTER_UNSAFE_RAW );
			$failures_json = is_string( $failures_raw ) ? wp_unslash( $failures_raw ) : '[]';
			$failures = json_decode( $failures_json, true );
			$failures = is_array( $failures ) ? $failures : [];
			try {
				$result = ServiceScanner::finish_browser_scan( $failures );
			} finally {
				if ( is_callable( [ '\\F152\\ServiceScanner', 'release_browser_scan_lock' ] ) ) ServiceScanner::release_browser_scan_lock();
			}
			wp_send_json_success( $result );
		}

		wp_send_json_error( [ 'message' => 'Неизвестный режим проверки.' ], 400 );
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
				'page'            => 'f152',
				'tab'             => 'main',
				'f152_force_scan' => '1',
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

		 
		$multilingual = class_exists( '\F152\MultilingualSafety' ) && MultilingualSafety::is_multilingual();
		if ( $multilingual && 'managed' === $mode && 'data_transfer' !== $category ) {
			return [
				'message' => $pro_active
					? sprintf( 'Сервис «%s» обнаружен, но его описание не подтверждено во всех доступных языковых версиях политики. Проверьте выбранные сервисы и выполните синхронное обновление документов во вкладке «Языки». Существующие ручные правки защищены.', $label )
					: sprintf( 'Сервис «%s» обнаружен, но его описание не найдено в проверенных языковых версиях политики. Проверьте обе версии вручную; автоматическое обновление переводов доступно в Pro.', $label ),
				'url' => $pro_active ? self::tab_url( 'languages' ) : $texts_url,
				'button' => $pro_active ? 'Открыть генератор RU / EN' : 'Открыть тексты',
				'external' => false,
				'chip' => 'Проверьте языковые версии',
			];
		}

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
					'message' => $reason . ( $multilingual ? ' Если передача действительно используется, отметьте её во вкладке «Языки» и обновите документы RU / EN через генератор.' : ' В Pro откройте «Тексты» → «Политика обработки ПД» и отметьте этот сервис, если интеграция действительно используется. Pro добавит и дальше будет поддерживать нужный блок автоматически.' ),
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
					'message' => $intro . ( $multilingual ? ' Откройте вкладку «Языки», выберите сервис и обновите документы. Обычная синхронизация вкладки «Тексты» не изменяет переводы.' : ' Откройте вкладку «Тексты» → «Политика обработки ПД», отметьте этот сервис в блоке Pro и нажмите «Сохранить». Если страницы политик созданы FZ-152, Pro добавит нужный текст и дальше сможет обновлять его автоматически.' ),
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

	private static function render_scanner_check_item( array $scanner_check ) : void {
		$severity = sanitize_key( (string) ( $scanner_check['severity'] ?? 'info' ) );
		$chip_class = 'success' === $severity ? 'f152-status-chip--success' : ( in_array( $severity, [ 'warning', 'error' ], true ) ? 'f152-status-chip--attention' : 'f152-status-chip--info' );
		$icon = sanitize_key( (string) ( $scanner_check['icon'] ?? 'info-outline' ) );
		$evidence = isset( $scanner_check['evidence'] ) && is_array( $scanner_check['evidence'] ) ? $scanner_check['evidence'] : [];
		$location_labels = [];
		foreach ( array_slice( (array) ( $evidence['urls'] ?? [] ), 0, 3 ) as $location_url ) {
			if ( is_string( $location_url ) && '' !== $location_url ) $location_labels[] = $location_url;
		}
		foreach ( array_slice( (array) ( $evidence['locations'] ?? [] ), 0, 3 ) as $location ) {
			if ( ! is_array( $location ) ) continue;
			$location_labels[] = ! empty( $location['title'] ) ? (string) $location['title'] : (string) ( $location['url'] ?? '' );
		}
		$location_labels = array_values( array_unique( array_filter( $location_labels ) ) );
		$recommendation = trim( (string) ( $scanner_check['recommendation'] ?? '' ) );
		$message = trim( (string) ( $scanner_check['message'] ?? '' ) );
		?>
		<div class="f152-audit-item f152-audit-item--<?php echo esc_attr( $severity ); ?>">
			<div class="f152-audit-item__icon"><span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span></div>
			<div class="f152-audit-item__body">
				<div class="f152-audit-item__title"><strong><?php echo esc_html( (string) ( $scanner_check['title'] ?? '' ) ); ?></strong><span class="f152-status-chip <?php echo esc_attr( $chip_class ); ?>"><?php echo esc_html( (string) ( $scanner_check['chip'] ?? 'Информация' ) ); ?></span></div>
				<?php if ( '' !== $message ) : ?><p><?php echo esc_html( $message ); ?></p><?php endif; ?>
				<?php if ( '' !== $recommendation && $recommendation !== $message ) : ?><p class="f152-audit-item__recommendation"><strong>Что делать:</strong> <?php echo esc_html( $recommendation ); ?></p><?php endif; ?>
				<?php if ( ! empty( $location_labels ) ) : ?><p class="f152-audit-item__meta">Страница: <?php echo esc_html( implode( ', ', $location_labels ) ); ?></p><?php endif; ?>
				<?php if ( ! empty( $evidence['resources'] ) ) : ?><p class="f152-audit-item__meta">Пример подключения: <?php echo esc_html( implode( ', ', array_slice( (array) $evidence['resources'], 0, 2 ) ) ); ?></p><?php endif; ?>
				<?php if ( ! empty( $evidence['ids'] ) ) : ?><p class="f152-audit-item__meta">ID сервиса: <?php echo esc_html( implode( ', ', array_slice( (array) $evidence['ids'], 0, 3 ) ) ); ?></p><?php endif; ?>
			</div>
		</div>
		<?php
	}

	private static function render_tab_main() : void {
		$pro_active = self::is_pro_license_active();
		$multilingual_providers = class_exists( '\F152\MultilingualSafety' ) ? MultilingualSafety::providers() : [];
		$multilingual_detected = count( $multilingual_providers ) > 0;
		$multilingual_without_pro = $multilingual_detected && ! $pro_active;
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
		$scan_pending = ! empty( $scan_meta['browser_pending'] );
		$scan_complete = ! empty( $scan_meta['browser_complete'] );
		$scan_incomplete = $scan_complete && ( ! $scan_home_ok || $scan_failed_pages > 0 );
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
		$scanner_checks = class_exists( '\F152\ScannerChecks' )
			? \F152\ScannerChecks::build( $scan_snapshot, $tracker_issues, $detected_plugins )
			: [];
		 
		$display_groups = class_exists( '\F152\ScannerChecks' ) ? \F152\ScannerChecks::presentation_groups( $scanner_checks ) : [];
		$scanner_attention_checks = (array) ( $display_groups['attention'] ?? [] );
		$scanner_reference_checks = (array) ( $display_groups['reference'] ?? [] );
		$verified_form_count = (int) ( $display_groups['consent_verified'] ?? 0 );
		$unverified_form_count = (int) ( $display_groups['unverified'] ?? 0 );
		$scan_forms = isset( $scan_snapshot['forms'] ) && is_array( $scan_snapshot['forms'] ) ? $scan_snapshot['forms'] : [];
		$scan_form_count = 0;
		foreach ( $scan_forms as $scan_form ) if ( is_array( $scan_form ) && 'woocommerce_myaccount_page' !== (string) ( $scan_form['evidence'] ?? '' ) && ( ! empty( $scan_form['types'] ) || ! empty( $scan_form['partial_name'] ) ) ) $scan_form_count++;
		$external_domains = isset( $scan_snapshot['external_domains'] ) && is_array( $scan_snapshot['external_domains'] ) ? $scan_snapshot['external_domains'] : [];
		$unknown_domain_count = 0;
		foreach ( $external_domains as $external_domain ) if ( is_array( $external_domain ) && empty( $external_domain['known'] ) ) $unknown_domain_count++;
		$scanner_issue_count = class_exists( '\F152\ScannerChecks' ) ? \F152\ScannerChecks::issue_count( $scanner_checks ) : count( $tracker_issues );
		$audit_count = $scanner_issue_count + count( $policy_issues ) + ( $scan_incomplete ? 1 : 0 ) + ( $multilingual_without_pro ? 1 : 0 );
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
						<p>Записи блога и товары не сканируются.</p>
					</div>
					<?php if ( $scan_pending ) : ?><span class="f152-audit-card__count">Проверка выполняется</span><?php elseif ( $audit_count > 0 ) : ?><span class="f152-audit-card__count f152-audit-card__count--attention"><?php echo esc_html( sprintf( 'Нужно проверить: %d', $audit_count ) ); ?></span><?php elseif ( ! $scan_complete || $unverified_form_count > 0 ) : ?><span class="f152-audit-card__count">Проверка неполная</span><?php else : ?><span class="f152-audit-card__count">Проблем не найдено</span><?php endif; ?>
				</div>

				<div class="f152-audit-list">
					<?php if ( $multilingual_without_pro ) : ?>
					<div class="f152-audit-item f152-audit-item--warning">
						<div class="f152-audit-item__icon"><span class="dashicons dashicons-translation" aria-hidden="true"></span></div>
						<div class="f152-audit-item__body">
							<div class="f152-audit-item__title"><strong>Обнаружен мультиязычный сайт</strong><span class="f152-status-chip f152-status-chip--attention">Нужна настройка переводов</span></div>
							<p><?php echo esc_html( sprintf( 'Языковой плагин: %s. Бесплатная версия FZ-152 RF не создаёт связанные юридические документы и не переводит тексты согласий автоматически. Проверьте все языковые версии сайта вручную.', implode( ', ', $multilingual_providers ) ) ); ?></p>
							<p>В FZ-152 RF Pro доступна генерация и синхронизация русских и английских документов с учётом CRM и трекеров, а также языковые тексты чекбоксов и cookie-баннера.</p>
							<p><a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'multilingual_audit', 'utm_campaign' => 'pro_upgrade' ], F152_PURCHASE_URL ) ); ?>" target="_blank" rel="noopener noreferrer">Мультиязычность в Pro</a></p>
						</div>
					</div>
					<?php elseif ( $multilingual_detected && count( $multilingual_providers ) > 1 ) : ?>
					<div class="f152-audit-item f152-audit-item--warning">
						<div class="f152-audit-item__icon"><span class="dashicons dashicons-warning" aria-hidden="true"></span></div>
						<div class="f152-audit-item__body"><strong>Найдено несколько языковых систем</strong><p>Одновременно обнаружены <?php echo esc_html( implode( ', ', $multilingual_providers ) ); ?>. Оставьте один языковой плагин: автоматическая синхронизация документов при конфликте отключена.</p></div>
					</div>
					<?php endif; ?>
					<div class="f152-audit-item" data-f152-browser-scan>
						<div class="f152-audit-item__icon"><span class="dashicons dashicons-search" aria-hidden="true"></span></div>
						<div class="f152-audit-item__body">
							<div class="f152-audit-item__title"><strong>Результат проверки сайта</strong><span class="f152-status-chip <?php echo esc_attr( $scan_complete ? 'f152-status-chip--success' : 'f152-status-chip--scanning' ); ?>" data-f152-browser-scan-chip aria-live="polite"><span class="f152-status-chip__spinner" aria-hidden="true"></span><span data-f152-browser-scan-chip-text><?php echo esc_html( $scan_complete ? 'Готово' : 'Сканирование…' ); ?></span></span></div>
							<?php if ( $scan_complete ) : ?>
								<p>Найдено форм: <?php echo esc_html( (string) $scan_form_count ); ?>, с обнаруженным согласием: <?php echo esc_html( (string) $verified_form_count ); ?><?php if ( $unverified_form_count ) : ?>, не удалось проверить: <?php echo esc_html( (string) $unverified_form_count ); ?><?php endif; ?>. Внешних доменов: <?php echo esc_html( (string) count( $external_domains ) ); ?><?php if ( $unknown_domain_count > 0 ) : ?>, требуют проверки: <?php echo esc_html( (string) $unknown_domain_count ); ?><?php endif; ?>.</p>
								<?php if ( ! empty( $detected_frontend_labels ) ) : ?><p class="f152-audit-item__meta">Распознаны сервисы: <?php echo esc_html( implode( ', ', $detected_frontend_labels ) ); ?>.</p><?php endif; ?>
								<?php if ( array_key_exists( 'form_signals_available', $scan_snapshot ) && empty( $scan_snapshot['form_signals_available'] ) ) : ?><p class="f152-audit-item__meta">На сервере недоступно расширение PHP DOM, поэтому детальный анализ полей форм отключён.</p><?php endif; ?>
							<?php endif; ?>
							<p class="f152-audit-item__meta" data-f152-browser-scan-status aria-live="polite"><?php echo esc_html( $scan_complete ? sprintf( 'Проверено страниц: %d. Ошибок загрузки: %d.', (int) ( $scan_meta['rendered_pages'] ?? 0 ), $scan_failed_pages ) : 'Сканирование ещё выполняется. Результат обновится автоматически.' ); ?></p>
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
									<p><?php echo esc_html( sprintf( 'Браузер администратора не смог открыть главную страницу и ещё %d страниц во время проверки.', $scan_failed_pages ) ); ?></p>
								<?php elseif ( ! $scan_home_ok ) : ?>
									<p>Браузер администратора не смог открыть главную страницу во время проверки.</p>
								<?php else : ?>
									<p><?php echo esc_html( sprintf( 'Браузер администратора не смог открыть %d страниц. Остальные найденные проблемы ниже можно исправлять — они не зависят от этого сообщения.', $scan_failed_pages ) ); ?></p>
								<?php endif; ?>
								<p><strong>Что делать:</strong> нажмите «Проверить ещё раз». Если сообщение останется, откройте «Данные для поддержки» и пришлите их в поддержку FZ-152.</p>
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


					<?php foreach ( $scanner_attention_checks as $scanner_check ) self::render_scanner_check_item( $scanner_check ); ?>
					<?php if ( ! empty( $scanner_reference_checks ) ) : ?>
						<details class="f152-audit-extra">
							<summary>Успешные и справочные результаты (<?php echo esc_html( (string) count( $scanner_reference_checks ) ); ?>)</summary>
							<div class="f152-audit-extra__items">
								<?php foreach ( $scanner_reference_checks as $scanner_check ) self::render_scanner_check_item( $scanner_check ); ?>
							</div>
						</details>
					<?php endif; ?>

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

				<?php if ( $pro_active && $show_pro_policy_action && ! $multilingual_detected ) : ?>
				<div class="f152-audit-pro-cta">
					<div>
						<strong>Тексты Pro требуют настройки</strong>
						<p>Все отмеченные выше сервисы настраиваются в одном месте. Проверьте, какие из них реально используются, отметьте их в блоке Pro и сохраните — политика обновится одним действием.</p>
					</div>
					<a class="button button-primary" href="<?php echo esc_url( self::subtab_url( 'policy_pd' ) ); ?>">Открыть сервисы и тексты Pro</a>
				</div>
				<?php endif; ?>

				<?php if ( ! $pro_active && $audit_count > 0 && ! $multilingual_without_pro ) : ?>
				<div class="f152-audit-pro-cta">
					<div>
						<strong>FZ-152 RF Pro может исправить это автоматически</strong>
						<p>Интеграции с другими плагинами, управление трекерами и внешними сервисами, автоматическое добавление недостающих текстов в политики — всё это Pro-версия плагина может настроить и дальше поддерживать автоматически за вас.</p>
					</div>
					<a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'audit_summary', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'diagnostics_bottom_cta' ], F152_PURCHASE_URL ) ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о FZ-152 Pro</a>
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
						<?php
						 
						 
						$english_urls = apply_filters( 'f152/admin/english_document_urls', [] );
						if ( is_array( $english_urls ) && ! empty( $english_urls ) ) :
							$english_labels = [
								'f152_link_policy_pd' => 'Политика обработки ПД (EN)',
								'f152_link_consent_pd' => 'Согласие на обработку ПД (EN)',
								'f152_link_policy_cookie' => 'Политика cookie (EN)',
								'f152_link_consent_marketing' => 'Согласие на рассылку (EN)',
						];
							foreach ( $english_labels as $english_key => $english_label ) :
								if ( empty( $english_urls[ $english_key ] ) || ! is_string( $english_urls[ $english_key ] ) ) { continue; }
								$english_url = esc_url( $english_urls[ $english_key ] );
								if ( '' === $english_url ) { continue; }
								?><div class="f152-field"><label><?php echo esc_html( $english_label ); ?></label>
								<input type="url" class="regular-text" value="<?php echo esc_attr( $english_url ); ?>" readonly aria-readonly="true">
								<small>Заполнено генератором Pro. <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'f152', 'tab' => 'languages' ], admin_url( 'options-general.php' ) ) ); ?>">Изменить на вкладке «Языки»</a>.</small></div><?php
							endforeach;
						endif;
						?>
						<div class="f152-field"><label for="f152_policy_version">Версия политики персональных данных</label><input type="text" class="regular-text" id="f152_policy_version" name="f152_policy_version" value="<?php echo esc_attr( API::get_policy_version() ); ?>" placeholder="1.0"><small>Версия сохраняется в журнале. После её изменения ранее сохранённый выбор cookie считается устаревшим и баннер показывается снова. При наличии кэша/CDN очистите его.</small></div>
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

			<section class="f152-settings-card f152-designer-card">
				<div class="f152-settings-card__header"><div><span class="f152-card-eyebrow">Дизайн</span><h2>Оформление cookie-баннера</h2><p>Настройте внешний вид визуально. Предпросмотр справа обновляется сразу, без сохранения страницы.</p></div></div>
				<div class="f152-settings-card__body">
					<div class="f152-designer-layout">
						<div class="f152-designer-controls">
							<div class="f152-appearance-group f152-appearance-group--position">
								<div class="f152-appearance-group__heading"><h3>Расположение</h3><span>На сайте</span></div>
								<?php self::render_position_selector(); ?>
							</div>

							<div class="f152-appearance-group">
								<div class="f152-appearance-group__heading"><h3>Тема и основные цвета</h3><span>Баннер и окно настроек</span></div>
								<div class="f152-theme-options" role="radiogroup" aria-label="Тема cookie-баннера">
									<label class="f152-theme-option">
										<input type="radio" name="f152_theme" value="light" <?php checked( get_option('f152_theme','light') === 'light' ); ?>>
										<span><i class="f152-theme-option__sample f152-theme-option__sample--light" aria-hidden="true"></i>Светлая</span>
									</label>
									<label class="f152-theme-option">
										<input type="radio" name="f152_theme" value="dark" <?php checked( get_option('f152_theme','light') === 'dark' ); ?>>
										<span><i class="f152-theme-option__sample f152-theme-option__sample--dark" aria-hidden="true"></i>Тёмная</span>
									</label>
								</div>
								<div class="f152-color-grid f152-color-grid--base">
									<?php self::render_color_field('f152_color_bg', 'Фон', '#ffffff'); ?>
									<?php self::render_color_field('f152_color_text', 'Текст', '#111111'); ?>
									<?php self::render_color_field('f152_color_link', 'Ссылки', '#1e73be'); ?>
								</div>
							</div>

							<div class="f152-appearance-group">
								<div class="f152-appearance-group__heading"><h3>Размеры</h3><span>Компактные значения в px</span></div>
								<div class="f152-metrics-grid">
									<?php self::render_compact_number_field('f152_font_size_text', 'Текст баннера', 13, 8, 32); ?>
									<?php self::render_compact_number_field('f152_font_size_btn', 'Текст кнопок', 14, 8, 32); ?>
									<?php self::render_compact_number_field('f152_btn_radius', 'Скругление', 8, 0, 32); ?>
								</div>
							</div>

							<div class="f152-appearance-group">
								<div class="f152-appearance-group__heading"><h3>Кнопки</h3><span>Фон и текст каждой кнопки</span></div>
								<div class="f152-button-palette-grid">
									<div class="f152-button-palette f152-button-palette--accept">
										<h4>Принять</h4>
										<?php self::render_color_field('f152_color_btn_accept_bg', 'Фон', '#27ae60'); ?>
										<?php self::render_color_field('f152_color_btn_accept_text', 'Текст', '#ffffff'); ?>
									</div>
									<div class="f152-button-palette f152-button-palette--settings">
										<h4>Настроить</h4>
										<?php self::render_color_field('f152_color_btn_settings_bg', 'Фон', '#f3f4f6', '0% прозрачности сделает кнопку вторичной с обводкой.'); ?>
										<?php self::render_color_field('f152_color_btn_settings_text', 'Текст', '#1e73be'); ?>
									</div>
									<div class="f152-button-palette f152-button-palette--reject">
										<h4>Отклонить</h4>
										<?php self::render_color_field('f152_color_btn_reject_bg', 'Фон', '#b32424'); ?>
										<?php self::render_color_field('f152_color_btn_reject_text', 'Текст', '#ffffff'); ?>
									</div>
								</div>
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
						<div class="f152-designer-preview-column">
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
								<div class="f152-preview__header"><div><strong>Предпросмотр</strong><p class="f152-preview__hint">Нижняя полоса показана внутри миниатюры страницы. На узкой ширине кнопки могут переноситься, как на небольшом экране.</p></div></div>
								<div class="f152-preview__frames"><div class="f152-preview__frame" data-f152-preview-frame="desktop"><div class="f152-preview__viewport" data-f152-theme="<?php echo esc_attr( $theme_mode ); ?>"><div class="f152-preview__banner" data-f152-preview-banner></div></div></div></div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

        private static function render_position_selector() : void {
                $has_pro = defined( 'F152_PRO_VERSION' );
                $pro_url = $has_pro
                        ? self::tab_url( 'integrations' ) . '#f152-pro-designer'
                        : ( defined( 'F152_PURCHASE_URL' ) ? F152_PURCHASE_URL : 'https://fz152rf.ru/' );
                $current_mode = 'bar';
                if ( $has_pro && class_exists( '\F152Pro\BannerDesigner' ) && is_callable( [ '\F152Pro\BannerDesigner', 'get_settings' ] ) ) {
                        $pro_settings = \F152Pro\BannerDesigner::get_settings();
                        $candidate = is_array( $pro_settings ) ? (string) ( $pro_settings['mode'] ?? 'bar' ) : 'bar';
                        if ( in_array( $candidate, [ 'bar', 'card_left', 'card_right' ], true ) ) {
                                $current_mode = $candidate;
                        }
                }
                ?>
                <div class="f152-position-picker" aria-label="Расположение cookie-баннера">
                        <?php if ( $has_pro ) : ?>
                                <a href="<?php echo esc_url( $pro_url ); ?>" class="f152-position-card f152-position-card--link <?php echo 'bar' === $current_mode ? 'is-selected' : ''; ?>">
                                        <span class="f152-position-card__visual f152-position-card__visual--bar" aria-hidden="true"><i></i></span>
                                        <span class="f152-position-card__label">Полоса снизу</span>
                                        <?php if ( 'bar' === $current_mode ) : ?><span class="f152-position-card__state">Текущее</span><?php endif; ?>
                                </a>
                                <a href="<?php echo esc_url( $pro_url ); ?>" class="f152-position-card f152-position-card--link <?php echo 'card_left' === $current_mode ? 'is-selected' : ''; ?>">
                                        <span class="f152-position-card__visual f152-position-card__visual--left" aria-hidden="true"><i></i></span>
                                        <span class="f152-position-card__label">Слева снизу</span>
                                        <span class="f152-pro-badge">PRO</span>
                                        <?php if ( 'card_left' === $current_mode ) : ?><span class="f152-position-card__state">Текущее</span><?php endif; ?>
                                </a>
                                <a href="<?php echo esc_url( $pro_url ); ?>" class="f152-position-card f152-position-card--link <?php echo 'card_right' === $current_mode ? 'is-selected' : ''; ?>">
                                        <span class="f152-position-card__visual f152-position-card__visual--right" aria-hidden="true"><i></i></span>
                                        <span class="f152-position-card__label">Справа снизу</span>
                                        <span class="f152-pro-badge">PRO</span>
                                        <?php if ( 'card_right' === $current_mode ) : ?><span class="f152-position-card__state">Текущее</span><?php endif; ?>
                                </a>
                        <?php else : ?>
                                <button type="button" class="f152-position-card is-selected" aria-pressed="true">
                                        <span class="f152-position-card__visual f152-position-card__visual--bar" aria-hidden="true"><i></i></span>
                                        <span class="f152-position-card__label">Полоса снизу</span>
                                        <span class="f152-position-card__state">Текущее</span>
                                </button>
                                <button type="button" class="f152-position-card is-locked" disabled aria-disabled="true">
                                        <span class="f152-position-card__visual f152-position-card__visual--left" aria-hidden="true"><i></i></span>
                                        <span class="f152-position-card__label">Слева снизу</span>
                                        <span class="f152-pro-badge">PRO</span>
                                </button>
                                <button type="button" class="f152-position-card is-locked" disabled aria-disabled="true">
                                        <span class="f152-position-card__visual f152-position-card__visual--right" aria-hidden="true"><i></i></span>
                                        <span class="f152-position-card__label">Справа снизу</span>
                                        <span class="f152-pro-badge">PRO</span>
                                </button>
                        <?php endif; ?>
                </div>
                <p class="f152-position-picker__note">
                        <?php if ( $has_pro ) : ?>
                                <?php echo esc_html__( 'Pro установлен — нажмите на вариант, чтобы открыть полноценные настройки расположения.', 'fz-152-rf' ); ?>
                        <?php else : ?>
                                <?php echo esc_html__( 'Карточки слева и справа доступны в Pro.', 'fz-152-rf' ); ?>
                                <a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Подробнее', 'fz-152-rf' ); ?></a>
                        <?php endif; ?>
                </p>
                <?php
        }

        private static function render_compact_number_field( string $option, string $label, int $placeholder, int $min, int $max ) : void {
                $value = get_option( $option, '' );
                ?>
                <div class="f152-metric-field">
                        <label for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label>
                        <div class="f152-metric-field__control">
                                <input type="number" class="small-text" id="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $option ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( (string) $placeholder ); ?>" min="<?php echo esc_attr( (string) $min ); ?>" max="<?php echo esc_attr( (string) $max ); ?>" step="1">
                                <span>px</span>
                        </div>
                </div>
                <?php
        }

        private static function render_color_field(string $option, string $label, string $placeholder = '', string $description = '') : void {
                $value = get_option($option, '');
                $placeholder_value = $placeholder !== '' ? $placeholder : '#000000';
                $default_color = self::color_picker_fallback($placeholder_value);
                $picker_value = 'transparent' === $default_color ? '#000000' : $default_color;
                ?>
                <div class="f152-color-field">
                        <label for="<?php echo esc_attr($option); ?>"><?php echo esc_html($label); ?></label>
                        <div class="f152-color-control" data-f152-color-default="<?php echo esc_attr($default_color); ?>">
                                <div class="f152-color-control__row">
                                        <input type="color" class="f152-color-picker" value="<?php echo esc_attr( $picker_value ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Выбрать цвет: %s', $label ) ); ?>">
                                        <input type="text" class="f152-color-input code" id="<?php echo esc_attr($option); ?>" name="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr($placeholder_value); ?>" spellcheck="false" autocomplete="off">
                                        <div class="f152-color-opacity" title="Прозрачность">
                                                <input type="range" min="0" max="100" step="1" value="100" class="f152-opacity__range" aria-label="Прозрачность">
                                                <output class="f152-opacity__value" aria-live="polite">100%</output>
                                        </div>
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
                        $hex = $m[0];
                        if ( 4 === strlen( $hex ) ) {
                                return '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
                        }
                        return $hex;
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
		if ( ! isset( $map[ $subtab ] ) && 'history' !== $subtab ) {
			$subtab = 'policy_pd';
		}

		?>
		<h2 class="nav-tab-wrapper f152-subtabs">
			<?php foreach ( $map as $key => $info ) : ?>
				<a href="<?php echo esc_url( self::subtab_url($key) ); ?>" class="nav-tab <?php echo esc_attr( $subtab === $key ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $info['label'] ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( self::subtab_url('history') ); ?>" class="nav-tab <?php echo esc_attr( 'history' === $subtab ? 'nav-tab-active' : '' ); ?>">История версий</a>
		</h2>
		<?php if ( 'history' === $subtab ) { self::render_policy_history(); return; } ?>
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


	private static function render_policy_history(): void {
		if ( class_exists( '\F152\PolicySnapshots' ) ) {
			PolicySnapshots::maybe_upgrade();
		}
		$snapshots = class_exists( '\F152\PolicySnapshots' ) ? PolicySnapshots::get_snapshots( 120 ) : [];
		$labels = [
			'policy_pd' => 'Политика обработки ПД',
			'consent_pd' => 'Согласие на обработку ПД',
			'policy_cookie' => 'Политика cookie',
			'consent_marketing' => 'Согласие на рассылку',
		];
		$sources = [
			'baseline' => 'Исходный снимок',
			'before_change' => 'Перед изменением страницы',
			'version_mark' => 'Версия политики',
			'manual' => 'Ручной снимок',
		];
		$saved_raw = filter_input( INPUT_GET, 'f152_snapshot_saved', FILTER_UNSAFE_RAW );
		$saved = is_string( $saved_raw ) ? max( 0, (int) sanitize_text_field( wp_unslash( $saved_raw ) ) ) : -1;
		?>
		<div class="f152-settings-card">
			<div class="f152-settings-card__header">
				<div>
					<span class="f152-card-eyebrow">Архив документов</span>
					<h2>История версий юридических страниц</h2>
					<p>FZ-152 хранит собственные снимки страниц и не зависит от WordPress Revisions. Снимок создаётся перед изменением отслеживаемой страницы и при смене версии политики.</p>
				</div>
			</div>
			<div class="f152-settings-card__body">
				<?php if ( $saved >= 0 ) : ?>
					<div class="notice notice-success inline"><p><?php echo esc_html( $saved > 0 ? sprintf( 'Сохранено снимков: %d.', $saved ) : 'Новых снимков не потребовалось: текущие тексты уже сохранены.' ); ?></p></div>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="f152_capture_policy_snapshot">
					<?php wp_nonce_field( 'f152_capture_policy_snapshot', 'f152_snapshot_nonce' ); ?>
					<?php submit_button( 'Сохранить снимок текущих страниц', 'secondary', 'submit', false ); ?>
				</form>
				<p class="description">Снимки создаются только для страниц WordPress, ссылки на которые указаны в FZ-152 или которые были созданы самим плагином.</p>
			</div>
		</div>
		<?php if ( empty( $snapshots ) ) : ?>
			<p>Снимков пока нет.</p>
		<?php else : ?>
			<div class="f152-policy-history">
				<?php foreach ( $snapshots as $index => $snapshot ) : ?>
					<?php
					$document = sanitize_key( (string) ( $snapshot['document'] ?? '' ) );
					$source = sanitize_key( (string) ( $snapshot['source'] ?? '' ) );
					$textarea_id = 'f152-policy-snapshot-' . (int) ( $snapshot['id'] ?? $index );
					?>
					<details class="f152-settings-card" style="margin-top:12px;">
						<summary style="cursor:pointer;padding:16px 20px;">
							<strong><?php echo esc_html( $labels[ $document ] ?? $document ); ?></strong>
							 — <?php echo esc_html( (string) ( $snapshot['created_at'] ?? '' ) ); ?>
							<?php if ( ! empty( $snapshot['policy_version'] ) ) : ?> — версия <?php echo esc_html( (string) $snapshot['policy_version'] ); ?><?php endif; ?>
							 — <?php echo esc_html( $sources[ $source ] ?? $source ); ?>
						</summary>
						<div style="padding:0 20px 20px;">
							<p class="description"><?php echo esc_html( (string) ( $snapshot['page_title'] ?? '' ) ); ?><?php if ( ! empty( $snapshot['page_id'] ) ) : ?> · ID <?php echo (int) $snapshot['page_id']; ?><?php endif; ?></p>
							<div class="f152-row-actions">
								<button type="button" class="button f152-copy-btn" data-f152-copy-target="<?php echo esc_attr( $textarea_id ); ?>" data-f152-copy-status="<?php echo esc_attr( $textarea_id . '-status' ); ?>" data-f152-copy-success="Скопировано" data-f152-copy-error="Не удалось скопировать">Скопировать</button>
								<span id="<?php echo esc_attr( $textarea_id . '-status' ); ?>" class="f152-copy-status" aria-live="polite"></span>
							</div>
							<textarea id="<?php echo esc_attr( $textarea_id ); ?>" class="large-text code" rows="14" readonly><?php echo esc_textarea( (string) ( $snapshot['content'] ?? '' ) ); ?></textarea>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php
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
				<p><a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'texts_policy_preview', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'policy_preview_cta' ], F152_PURCHASE_URL ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Подробнее о FZ-152 Pro', 'fz-152-rf' ); ?></a></p>
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
	private static function render_locked_languages_tab() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [
			'utm_source' => 'free_plugin', 'utm_medium' => 'languages_tab',
			'utm_campaign' => 'pro_upgrade', 'utm_content' => 'languages_locked',
		], F152_PURCHASE_URL ) );
		?>
		<div class="f152-integrations-locked">
			<div class="f152-addon-intro">
				<span class="f152-card-eyebrow">FZ-152 RF Pro</span>
				<h2>Мультиязычность сайта</h2>
				<p>Если ваш WordPress-сайт работает на нескольких языках, Pro поможет показывать посетителям согласия, ссылки на документы и cookie-баннер на языке страницы.</p>
			</div>
			<div class="f152-integrations-card f152-integrations-card--locked" style="max-width:820px;">
				<div class="f152-integrations-card__body">
					<h3 class="f152-integrations-card__title">Переводы согласий и юридические документы</h3>
					<p class="f152-integrations-card__desc">Поддерживаются Polylang, WPML и qTranslate-X/XT. Для сайта с русским и английским языками Pro автоматически создаёт, публикует и обновляет четыре юридических документа на обоих языках. Переводы текстов согласий и cookie-баннера также поддерживаются. Для остальных языков тексты согласий и ссылки на документы настраиваются вручную; автоматическая генерация юридических документов на этих языках не предусмотрена.</p>
				</div>
			</div>
			<div class="f152-integrations-locked__cta"><a class="button button-primary" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о FZ-152 RF Pro</a></div>
		</div>
		<?php
	}

	private static function render_locked_rkn_tab() : void {
		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'rkn_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'rkn_locked_cta' ], F152_PURCHASE_URL ) );
		?>
		<div class="f152-integrations-locked">
			<div class="f152-addon-intro">
				<span class="f152-card-eyebrow">FZ-152 RF Pro</span>
				<h2>Подготовка уведомления в Роскомнадзор</h2>
				<p>Pro помогает собрать сведения об операторе и целях обработки, подготовить черновик уведомления по форме Роскомнадзора, проверить трансграничную передачу и запомнить состояние после подачи, чтобы позже видеть изменения.</p>
			</div>
			<section class="f152-addon-section" aria-labelledby="f152-rkn-pro-title">
				<div class="f152-addon-section__header">
					<h2 id="f152-rkn-pro-title">Что доступно в Pro</h2>
					<p>Это отдельный помощник внутри FZ-152, а не просто пустой шаблон документа.</p>
				</div>
				<div class="f152-integrations-grid">
					<div class="f152-integrations-card f152-integrations-card--locked"><div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div><div class="f152-integrations-card__body"><h3 class="f152-integrations-card__title">Мастер оператора</h3><p class="f152-integrations-card__desc">Реквизиты, ответственный, базы данных, сроки, меры защиты и анкета о процессах вне WordPress.</p></div></div>
					<div class="f152-integrations-card f152-integrations-card--locked"><div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div><div class="f152-integrations-card__body"><h3 class="f152-integrations-card__title">Цели обработки</h3><p class="f152-integrations-card__desc">Готовые редактируемые цели для заявок, заказов, личных кабинетов, рассылок, аналитики, CRM, сотрудников и других сценариев.</p></div></div>
					<div class="f152-integrations-card f152-integrations-card--locked"><div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div><div class="f152-integrations-card__body"><h3 class="f152-integrations-card__title">Черновик и инструкция</h3><p class="f152-integrations-card__desc">Поля в порядке формы Роскомнадзора, кнопки копирования, TXT-выгрузка и печать/сохранение в PDF.</p></div></div>
					<div class="f152-integrations-card f152-integrations-card--locked"><div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div><div class="f152-integrations-card__body"><h3 class="f152-integrations-card__title">Контроль изменений</h3><p class="f152-integrations-card__desc">После подачи можно зафиксировать заявленное состояние и видеть, что изменилось в целях, настройках и обнаруженных сервисах.</p></div></div>
					<div class="f152-integrations-card f152-integrations-card--locked"><div class="f152-integrations-card__lock"><span class="dashicons dashicons-lock" aria-hidden="true"></span></div><div class="f152-integrations-card__body"><h3 class="f152-integrations-card__title">Трансграничная передача</h3><p class="f152-integrations-card__desc">Отдельная проверка сервисов-кандидатов и ссылка на специальную форму уведомления РКН.</p></div></div>
				</div>
			</section>
			<div class="f152-integrations-locked__cta"><a class="button button-primary" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer">Подробнее о FZ-152 RF Pro</a></div>
		</div>
		<?php
	}

	private static function render_locked_integrations_tab() : void {
		$form_cards = [
			[ 'title' => 'Contact Form 7',        'desc' => 'Автоматический чекбокс согласия и логирование отправленных форм.' ],
			[ 'title' => 'WPForms',               'desc' => 'Добавление согласия и запись отправки в журнал согласий.' ],
			[ 'title' => 'Ninja Forms',           'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'Fluent Forms',          'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'Forminator',            'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'MetForm',               'desc' => 'Автоматический чекбокс согласия и логирование отправок.' ],
			[ 'title' => 'FormLayer',            'desc' => 'Автоматический чекбокс, положение над/под кнопкой, серверная проверка и журнал имени, телефона и email.' ],
			[ 'title' => 'Formidable Forms',      'desc' => 'Автоматический чекбокс, положение над/под кнопкой, серверная проверка и журнал имени, телефона и email.' ],
			[ 'title' => 'SureForms',             'desc' => 'Автоматический чекбокс, положение над/под кнопкой, серверная проверка и журнал имени, телефона и email.' ],
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

		$buy_url = apply_filters( 'f152_pro_buy_url', add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'addon_tab', 'utm_campaign' => 'pro_upgrade', 'utm_content' => 'integrations_locked' ], F152_PURCHASE_URL ) );
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
			add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'addon_tab', 'utm_campaign' => 'owner_multisite_discount', 'utm_content' => 'multisite_discount_card' ], F152_PURCHASE_URL )
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
			add_query_arg( [ 'utm_source' => 'free_plugin', 'utm_medium' => 'addon_tab', 'utm_campaign' => 'partner_program', 'utm_content' => 'partner_program_card' ], F152_PARTNER_URL )
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

		private static function render_feature_request_cta() : void {
			?>
			<aside class="f152-feature-cta" aria-label="<?php echo esc_attr__( 'Предложить функцию для FZ-152 RF', 'fz-152-rf' ); ?>">
				<span class="dashicons dashicons-lightbulb f152-feature-cta__icon" aria-hidden="true"></span>
				<div class="f152-feature-cta__copy">
					<strong><?php echo esc_html__( 'Не хватает функции?', 'fz-152-rf' ); ?></strong>
					<span><?php echo esc_html__( 'Расскажите разработчику.', 'fz-152-rf' ); ?></span>
				</div>
				<button type="button" class="button f152-feature-cta__button" data-f152-feature-open>
					<?php echo esc_html__( 'Предложить функцию', 'fz-152-rf' ); ?>
				</button>
			</aside>
			<?php
		}

		private static function render_feature_request_modal() : void {
			?>
			<div class="f152-feature-modal" data-f152-feature-modal hidden>
				<div class="f152-feature-modal__backdrop" data-f152-feature-close></div>
				<div class="f152-feature-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="f152-feature-title">
					<button type="button" class="f152-feature-modal__close" data-f152-feature-close aria-label="<?php echo esc_attr__( 'Закрыть', 'fz-152-rf' ); ?>">×</button>
					<div class="f152-feature-modal__heading">
						<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
						<div>
							<h2 id="f152-feature-title"><?php echo esc_html__( 'Предложить новую функцию', 'fz-152-rf' ); ?></h2>
							<p><?php echo esc_html__( 'Опишите, чего вам не хватает в FZ-152 RF или какую задачу плагин мог бы решать удобнее.', 'fz-152-rf' ); ?></p>
						</div>
					</div>
					<label class="screen-reader-text" for="f152-feature-message"><?php echo esc_html__( 'Описание функции', 'fz-152-rf' ); ?></label>
					<textarea id="f152-feature-message" class="large-text f152-feature-modal__textarea" rows="7" maxlength="4000" data-f152-feature-message placeholder="<?php echo esc_attr__( 'Например: хотелось бы, чтобы плагин...', 'fz-152-rf' ); ?>"></textarea>
					<div class="f152-feature-modal__meta">
						<span><?php echo esc_html__( 'Не отправляйте пароли, ключи и другие секретные данные.', 'fz-152-rf' ); ?></span>
						<span data-f152-feature-counter>0 / 4000</span>
					</div>
					<div class="f152-feature-modal__footer">
						<span class="f152-feature-modal__status" data-f152-feature-status aria-live="polite"></span>
						<div class="f152-feature-modal__actions">
							<button type="button" class="button" data-f152-feature-close><?php echo esc_html__( 'Отмена', 'fz-152-rf' ); ?></button>
							<button type="button" class="button button-primary" data-f152-feature-submit><?php echo esc_html__( 'Отправить', 'fz-152-rf' ); ?></button>
						</div>
					</div>
				</div>
			</div>
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
	add_action('admin_post_f152_clear_logs', [__CLASS__, 'handle_clear_logs']);
	add_action('admin_post_f152_log_cleanup_password', [__CLASS__, 'handle_log_cleanup_password']);
	add_action('admin_post_f152_export_cookie_csv', [__CLASS__, 'handle_cookie_csv_export']);
	add_action('admin_post_f152_capture_policy_snapshot', [__CLASS__, 'handle_capture_policy_snapshot']);
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
		'language',
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
			$row['language'] ?? '',
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

 public static function handle_cookie_csv_export(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав для выполнения операции.' );
	}
	check_admin_referer( 'f152_export_cookie_csv', 'f152_cookie_export_nonce' );
	if ( ! CookieConsentLog::table_exists() ) {
		wp_die( 'Таблица cookie-журнала недоступна.' );
	}
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="fz-152-cookie-consent-log-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$output = fopen( 'php://output', 'w' );
	if ( false === $output ) {
		wp_die( 'Не удалось открыть поток экспорта.' );
	}
	echo "\xEF\xBB\xBF";  
	self::write_cookie_csv( $output );
	exit;
 }

  
 public static function write_cookie_csv( $output ): void {
	fputcsv( $output, [ 'id', 'event_id', 'created_at', 'created_at_utc', 'timezone', 'action', 'source', 'necessary', 'analytics', 'marketing', 'categories_json', 'page_url', 'ip_address', 'browser', 'user_agent', 'policy_version', 'policy_url', 'language' ], ';', '"', '' );
	$max_id = CookieConsentLog::get_export_max_id();
	$after_id = 0;
	while ( $after_id < $max_id ) {
		$rows = CookieConsentLog::get_export_batch( $after_id, $max_id );
		if ( empty( $rows ) ) {
			break;
		}
		foreach ( $rows as $row ) {
			$ua = (string) ( $row['user_agent'] ?? '' );
			$csv_row = [
				$row['id'], $row['event_id'], CookieConsentLog::local_datetime( $row['created_at_utc'] ),
				$row['created_at_utc'], wp_timezone()->getName(), $row['action'], $row['source'],
				$row['necessary'], $row['analytics'], $row['marketing'], $row['categories_json'],
				$row['page_url'], $row['ip_address'], CookieConsentLog::browser_name( $ua ), $ua,
				$row['policy_version'], $row['policy_url'], $row['language'] ?? '',
			];
			fputcsv( $output, array_map( [ __CLASS__, 'csv_safe_cell' ], $csv_row ), ';', '"', '' );
			$after_id = (int) $row['id'];
		}
	}
 }

 public static function handle_clear_logs(): void {
 	if ( ! current_user_can('manage_options') ) {
 		wp_die('Недостаточно прав для выполнения операции.');
 	}

 	check_admin_referer( 'f152_clear_logs', 'f152_clear_nonce' );
 	$date_raw = filter_input( INPUT_POST, 'f152_clear_until', FILTER_UNSAFE_RAW );
 	$password_raw = filter_input( INPUT_POST, 'f152_clear_password', FILTER_UNSAFE_RAW );
	$target_raw = filter_input( INPUT_POST, 'f152_clear_target', FILTER_UNSAFE_RAW );
	$return_raw = filter_input( INPUT_POST, 'log_type', FILTER_UNSAFE_RAW );
	$target = null === $target_raw ? 'forms' : ( is_string( $target_raw ) ? sanitize_key( wp_unslash( $target_raw ) ) : '' );
	$return_type = 'cookie' === $return_raw ? 'cookie' : 'forms';
	$redirect = [ 'page' => 'f152', 'tab' => 'logs', 'log_type' => $return_type ];
 	$date = is_string( $date_raw ) ? sanitize_text_field( wp_unslash( $date_raw ) ) : '';
 	$password = is_string( $password_raw ) ? wp_unslash( $password_raw ) : '';
 	$hash = (string) get_option( 'f152_log_cleanup_password_hash', '' );

 	if ( '' === $hash || '' === $password || ! wp_check_password( $password, $hash ) ) {
		wp_safe_redirect( add_query_arg( $redirect + [ 'f152_log_error' => 'password' ], admin_url( 'options-general.php' ) ) );
 		exit;
 	}

	if ( ! in_array( $target, [ 'forms', 'cookie', 'both' ], true ) || null === CookieConsentLog::cleanup_cutoff_utc( $date ) ) {
		wp_safe_redirect( add_query_arg( $redirect + [ 'f152_log_error' => 'date' ], admin_url( 'options-general.php' ) ) );
		exit;
	}
	$clear_forms = in_array( $target, [ 'forms', 'both' ], true );
	$clear_cookie = in_array( $target, [ 'cookie', 'both' ], true );
	 
	if ( ( $clear_forms && ! ConsentLog::table_exists() ) || ( $clear_cookie && ! CookieConsentLog::table_exists() ) ) {
		wp_safe_redirect( add_query_arg( $redirect + [ 'f152_log_error' => 'delete' ], admin_url( 'options-general.php' ) ) );
 		exit;
 	}
	$redirect['f152_cleared_date'] = $date;
	foreach ( [ 'forms' => $clear_forms, 'cookie' => $clear_cookie ] as $type => $selected ) {
		if ( ! $selected ) {
			continue;
		}
		$deleted = 'forms' === $type ? ConsentLog::delete_through_date( $date ) : CookieConsentLog::delete_through_date( $date );
		if ( false === $deleted ) {
			$redirect['f152_log_error'] = 'delete';
			break;
		}
		$redirect[ 'f152_cleared_' . $type ] = $deleted;
	}
	wp_safe_redirect( add_query_arg( $redirect, admin_url( 'options-general.php' ) ) );
 	exit;
 }

 public static function handle_log_cleanup_password(): void {
 	if ( ! current_user_can( 'manage_options' ) ) {
 		wp_die( 'Недостаточно прав для выполнения операции.' );
 	}

 	check_admin_referer( 'f152_log_cleanup_password', 'f152_log_password_nonce' );
 	$current_raw = filter_input( INPUT_POST, 'f152_current_cleanup_password', FILTER_UNSAFE_RAW );
 	$new_raw = filter_input( INPUT_POST, 'f152_new_cleanup_password', FILTER_UNSAFE_RAW );
 	$confirm_raw = filter_input( INPUT_POST, 'f152_confirm_cleanup_password', FILTER_UNSAFE_RAW );
 	$current = is_string( $current_raw ) ? wp_unslash( $current_raw ) : '';
 	$new = is_string( $new_raw ) ? wp_unslash( $new_raw ) : '';
 	$confirm = is_string( $confirm_raw ) ? wp_unslash( $confirm_raw ) : '';
 	$existing = (string) get_option( 'f152_log_cleanup_password_hash', '' );
 	$status = 'updated';

 	if ( '' !== $existing && ( '' === $current || ! wp_check_password( $current, $existing ) ) ) {
 		$status = 'current';
 	} elseif ( strlen( $new ) < 8 ) {
 		$status = 'short';
 	} elseif ( ! hash_equals( $new, $confirm ) ) {
 		$status = 'confirm';
 	} else {
 		update_option( 'f152_log_cleanup_password_hash', wp_hash_password( $new ), false );
 	}

	$return_raw = filter_input( INPUT_POST, 'log_type', FILTER_UNSAFE_RAW );
	wp_safe_redirect( add_query_arg( [ 'page' => 'f152', 'tab' => 'logs', 'log_type' => 'cookie' === $return_raw ? 'cookie' : 'forms', 'f152_log_password' => $status ], admin_url( 'options-general.php' ) ) );
 	exit;
 }

 public static function handle_capture_policy_snapshot(): void {
 	if ( ! current_user_can( 'manage_options' ) ) {
 		wp_die( 'Недостаточно прав для выполнения операции.' );
 	}

 	check_admin_referer( 'f152_capture_policy_snapshot', 'f152_snapshot_nonce' );
 	$count = class_exists( '\F152\PolicySnapshots' ) ? PolicySnapshots::capture_current_all( 'manual' ) : 0;
 	wp_safe_redirect( add_query_arg( [
 		'page' => 'f152',
 		'tab' => 'texts',
 		'subtab' => 'history',
 		'f152_snapshot_saved' => (int) $count,
 	], admin_url( 'options-general.php' ) ) );
 	exit;
 }


 	public static function handle_replace_configured_pages(): void {
 		if ( ! current_user_can( 'edit_pages' ) ) {
 			wp_die( 'Недостаточно прав для выполнения операции.' );
 		}

 		check_admin_referer( 'f152_replace_configured_pages', 'f152_replace_pages_nonce' );
		if ( ! MultilingualSafety::may_automatically_write() ) {
			self::flash_create_result( [ 'type' => 'error', 'message' => 'Замена юридических страниц заблокирована: на сайте активен мультиязычный плагин. Во избежание потери переводов редактируйте страницы вручную.', 'items' => [] ] );
			self::redirect_back_to_tab( 'texts' );
		}


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
			if ( ! MultilingualSafety::may_automatically_write( (int) $target['page_id'] ) ) {
				$failed_message = 'Обнаружен переводной юридический документ. Автоматическая перезапись запрещена.';
				break;
			}
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
		if ( ! MultilingualSafety::may_automatically_write() ) {
			self::flash_create_result( [ 'type' => 'error', 'message' => 'Автогенерация юридических страниц заблокирована на мультиязычном сайте. Подготовьте переводы и страницы вручную.', 'items' => [] ] );
			self::redirect_back();
		}


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
		 
		 
		if ( class_exists( '\F152\MultilingualSafety' ) && \F152\MultilingualSafety::is_multilingual() ) {
			$providers = \F152\MultilingualSafety::providers();
			$pro_ready = self::is_pro_license_active();
			$pro_mode = $pro_ready && class_exists( '\F152Pro\BilingualPolicyGenerator' )
				? \F152Pro\BilingualPolicyGenerator::supported() : [];
			?><div class="notice notice-info inline" style="margin:0;padding:10px 14px;">
			<p><strong>Здесь отключена только кнопка бесплатного генератора.</strong></p>
			<p>Обнаружен языковой плагин: <?php echo esc_html( implode( ', ', $providers ) ); ?>. Чтобы сохранить переводы, обычный генератор Free не записывает юридические страницы.</p>
			<?php if ( ! empty( $pro_mode ) && ! empty( $pro_mode['generate_en'] ) ) : ?>
			<p><strong>Pro готов:</strong> на вкладке «Языки» можно создать и обновить опубликованные документы RU и EN. Для Polylang/WPML будут отдельные связанные страницы, для qTranslate — обе языковые секции одной записи.</p>
			<?php elseif ( ! empty( $pro_mode ) ) : ?>
			<p><strong>Pro готов:</strong> генератор на вкладке «Языки» обновляет только русский вариант. Другие языки остаются без изменений.</p>
			<?php elseif ( $pro_ready ) : ?>
			<p>Откройте вкладку «Языки» для диагностики. Автоматическая RU/EN-генерация требует ровно один поддерживаемый языковой плагин и настроенные RU + EN; для qTranslate с тремя языками генерация заблокирована.</p>
			<?php else : ?>
			<p>В Pro доступна отдельная генерация RU + EN для qTranslate, Polylang и WPML. При дополнительных языках Polylang/WPML могут обновляться только русские документы.</p>
			<?php endif; ?>
			<p><a class="button button-secondary" href="<?php echo esc_url( self::tab_url( 'texts' ) ); ?>">Русские шаблоны</a> <a class="button" href="<?php echo esc_url( self::tab_url( 'languages' ) ); ?>"><?php echo $pro_ready ? 'Открыть генератор Pro' : 'Мультиязычность в Pro'; ?></a></p>
			</div><?php
			return;
		}

		$config = self::legal_pages_config();
		$empty_count = 0;
		foreach ( $config as $cfg ) {
			if ( trim((string) get_option($cfg['url_opt'], '')) === '' ) { $empty_count++; }
		}
		$total = count($config);
		$label = ( $empty_count === $total ) ? 'Создать страницы' : 'Создать недостающие страницы';
		?>
		<div class="f152-create-pages-actions"><div class="f152-create-pages-action">
			<button type="submit" name="f152_save_and_create_pages" value="1" class="button button-primary"><?php echo esc_html($label); ?></button>
			<span class="description">Создаёт только недостающие юридические страницы и заполняет ссылки в настройках.</span>
			<?php if ( $empty_count === 0 ) : ?><span class="description">Все ссылки уже заполнены — новые страницы созданы не будут.</span><?php endif; ?>
		</div>
		<?php self::render_replace_configured_pages_button( true, 'main' ); ?>
		</div><?php
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
		 
		 
		if ( class_exists( '\F152\MultilingualSafety' ) && \F152\MultilingualSafety::is_multilingual() ) { return; }
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
    $type_raw = filter_input( INPUT_GET, 'log_type', FILTER_UNSAFE_RAW );
    $log_type = 'cookie' === $type_raw ? 'cookie' : 'forms';
    ?>
    <h2>Журнал согласий</h2>
    <nav class="f152-log-subtabs" aria-label="Журналы согласий">
        <a href="<?php echo esc_url( add_query_arg( 'log_type', 'forms', self::tab_url( 'logs' ) ) ); ?>" class="<?php echo esc_attr( 'forms' === $log_type ? 'is-active' : '' ); ?>" <?php if ( 'forms' === $log_type ) echo 'aria-current="page"'; ?>>Формы</a>
        <a href="<?php echo esc_url( add_query_arg( 'log_type', 'cookie', self::tab_url( 'logs' ) ) ); ?>" class="<?php echo esc_attr( 'cookie' === $log_type ? 'is-active' : '' ); ?>" <?php if ( 'cookie' === $log_type ) echo 'aria-current="page"'; ?>>Cookie-баннер</a>
    </nav>
    <?php
    self::render_log_notices();
    if ( 'cookie' === $log_type ) {
        self::render_cookie_logs();
    } else {
        self::render_form_logs();
    }
 }

 private static function render_log_notices(): void {
    foreach ( [ 'forms' => 'журнала форм', 'cookie' => 'cookie-журнала' ] as $type => $label ) {
        $raw = filter_input( INPUT_GET, 'f152_cleared_' . $type, FILTER_UNSAFE_RAW );
        if ( is_string( $raw ) && ctype_digit( $raw ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( 'Удалено из %s: %d.', $label, (int) $raw ) ) . '</p></div>';
        }
    }
     
    $legacy = filter_input( INPUT_GET, 'f152_cleared_count', FILTER_UNSAFE_RAW );
    if ( is_string( $legacy ) && ctype_digit( $legacy ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( 'Удалено из журнала форм: %d.', (int) $legacy ) ) . '</p></div>';
    }
    $error_raw = filter_input( INPUT_GET, 'f152_log_error', FILTER_UNSAFE_RAW );
    $error = is_string( $error_raw ) ? sanitize_key( $error_raw ) : '';
    $errors = [
        'password' => 'Неверный пароль защиты очистки журнала.',
        'date' => 'Проверьте выбранный журнал и дату очистки.',
        'delete' => 'Не удалось завершить очистку. Проверьте доступность таблиц. Выполненные удаления показаны отдельно.',
    ];
    if ( isset( $errors[ $error ] ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $errors[ $error ] ) . '</p></div>';
    }
    $status_raw = filter_input( INPUT_GET, 'f152_log_password', FILTER_UNSAFE_RAW );
    $status = is_string( $status_raw ) ? sanitize_key( $status_raw ) : '';
    $messages = [ 'updated' => 'Пароль защиты очистки журнала сохранён.', 'current' => 'Текущий пароль указан неверно.', 'short' => 'Новый пароль должен содержать не менее 8 символов.', 'confirm' => 'Новый пароль и подтверждение не совпадают.' ];
    if ( isset( $messages[ $status ] ) ) {
        echo '<div class="notice notice-' . esc_attr( 'updated' === $status ? 'success' : 'error' ) . ' is-dismissible"><p>' . esc_html( $messages[ $status ] ) . '</p></div>';
    }
 }

 private static function render_cookie_logs(): void {
    $ready = CookieConsentLog::maybe_upgrade();
    $enabled = (bool) get_option( 'f152_cookie_log_enabled', 0 );
    $retention = CookieConsentLog::sanitize_retention_days( get_option( 'f152_cookie_log_retention_days', 365 ) );
    $paged_raw = filter_input( INPUT_GET, 'paged', FILTER_UNSAFE_RAW );
    $paged = is_string( $paged_raw ) ? max( 1, (int) $paged_raw ) : 1;
    $total = $ready ? CookieConsentLog::get_total_count() : 0;
    $total_pages = (int) ceil( $total / 50 );
    $paged = min( $paged, max( 1, $total_pages ) );
    $logs = $ready ? CookieConsentLog::get_logs( $paged, 50 ) : [];
    $size = $ready ? CookieConsentLog::get_table_size() : null;
    $export_url = add_query_arg( [ 'action' => 'f152_export_cookie_csv', 'f152_cookie_export_nonce' => wp_create_nonce( 'f152_export_cookie_csv' ) ], admin_url( 'admin-post.php' ) );
    $actions = [ 'accept_all' => 'Принял всё', 'reject_optional' => 'Отклонил необязательные', 'save_preferences' => 'Сохранил настройки', 'grant_category' => 'Разрешил категорию' ];
    $sources = [ 'banner' => 'Баннер', 'preferences' => 'Настройки cookie', 'embed' => 'Заблокированный контент', 'api' => 'API' ];
    settings_errors();
    ?>
    <div class="f152-logs-wrap">
        <form method="post" action="options.php" class="f152-settings-card f152-cookie-log-settings">
            <?php settings_fields( 'f152_cookie_log' ); ?>
            <div class="f152-settings-card__header"><h2>Журнал cookie-баннера</h2></div>
            <div class="f152-settings-card__body">
                <p><input type="hidden" name="f152_cookie_log_enabled" value="0"><label><input type="checkbox" name="f152_cookie_log_enabled" value="1" <?php checked( $enabled ); ?>> <strong>Вести журнал действий с cookie-баннером</strong></label></p>
                <p>При включении журнал будет сохранять явные решения посетителей в cookie-баннере: принятие, отказ и выбранные категории. Посетители, которые не взаимодействовали с баннером, в журнал не записываются.</p>
                <div class="notice notice-warning inline"><p>На сайтах с высокой посещаемостью журнал может заметно увеличивать размер базы данных. IP-адрес и User-Agent могут относиться к данным посетителя. Используйте необходимый срок хранения и убедитесь, что такая обработка отражена в документах сайта.</p></div>
                <p><input type="hidden" name="f152_cookie_log_capture_ip" value="0"><label><input type="checkbox" name="f152_cookie_log_capture_ip" value="1" <?php checked( get_option( 'f152_cookie_log_capture_ip', 1 ), 1 ); ?>> Сохранять IP-адрес</label></p>
                <p><input type="hidden" name="f152_cookie_log_capture_user_agent" value="0"><label><input type="checkbox" name="f152_cookie_log_capture_user_agent" value="1" <?php checked( get_option( 'f152_cookie_log_capture_user_agent', 1 ), 1 ); ?>> Сохранять User-Agent</label></p>
                <p><label for="f152_cookie_log_retention_days"><strong>Срок хранения</strong></label><br>
                    <select name="f152_cookie_log_retention_days" id="f152_cookie_log_retention_days">
                        <?php foreach ( [ 30 => '30 дней', 90 => '90 дней', 180 => '180 дней', 365 => '365 дней', 730 => '730 дней', 0 => 'Бессрочно' ] as $days => $label ) : ?>
                            <option value="<?php echo esc_attr( $days ); ?>" <?php selected( $retention, $days ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="description">Автоматическая очистка выполняется раз в сутки через WP-Cron, в том числе при выключенном журнале. Выключение IP и User-Agent влияет на новые записи.</p>
                <?php submit_button( 'Сохранить настройки журнала', 'primary', 'submit', false ); ?>
                <p class="description">При включении или выключении журнала плагин очищает кэш WP Rocket. Если используете другой кэш страниц или CDN, очистите его после сохранения.</p>
            </div>
        </form>
        <?php if ( ! $enabled ) : ?>
            <div class="notice notice-info inline"><p>Журнал cookie-баннера выключен. Плагин продолжает хранить выбор посетителя в cookie и вести существующую агрегированную статистику баннера, но индивидуальные действия посетителей не записываются.</p></div>
        <?php endif; ?>
        <?php if ( ! $ready ) : ?>
            <div class="notice notice-error"><p>Не удалось создать таблицу cookie-журнала. Проверьте права доступа к базе данных.</p></div>
        <?php else : ?>
            <p><strong>Записей: <?php echo esc_html( number_format_i18n( $total ) ); ?></strong><?php if ( null !== $size ) : ?> · размер таблицы: ~<?php echo esc_html( size_format( $size, 1 ) ); ?><?php endif; ?></p>
            <p class="description">Дата и время показаны в часовом поясе WordPress: <?php echo esc_html( wp_timezone()->getName() ); ?>. В CSV также сохранено время UTC.</p>
            <div class="f152-log-actions"><a href="<?php echo esc_url( $export_url ); ?>" class="button f152-log-actions__export">Экспорт cookie-журнала в CSV</a></div>
            <?php self::render_log_cleanup( 'cookie' ); ?>
            <?php if ( empty( $logs ) ) : ?>
                <p>Записей пока нет. После включения журнала здесь появятся новые решения посетителей.</p>
            <?php else : ?>
                <div class="f152-log-table-card">
                    <table class="widefat f152-log-table f152-cookie-log-table">
                        <thead><tr><th>Дата и время</th><th>Действие</th><th>Выбор</th><th>Источник</th><th>Страница</th><th>IP</th><th>Браузер / User-Agent</th><th>Версия политики</th></tr></thead>
                        <tbody>
                        <?php foreach ( $logs as $row ) : ?>
                            <?php $ua = (string) ( $row['user_agent'] ?? '' ); $url = (string) ( $row['page_url'] ?? '' ); ?>
                            <tr>
                                <td><?php echo esc_html( CookieConsentLog::local_datetime( $row['created_at_utc'] ) ); ?><br><small class="description">ID: <?php echo esc_html( $row['id'] ); ?></small></td>
                                <td><?php echo esc_html( $actions[ $row['action'] ] ?? $row['action'] ); ?></td>
                                <td class="f152-cookie-log-table__choice">Обязательные: да<br>Аналитические: <?php echo $row['analytics'] ? 'да' : 'нет'; ?><br>Маркетинговые: <?php echo $row['marketing'] ? 'да' : 'нет'; ?></td>
                                <td><?php echo esc_html( $sources[ $row['source'] ] ?? $row['source'] ); ?></td>
                                <td class="f152-cookie-log-table__page"><?php if ( '' !== $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url ); ?></a><?php else : ?>&mdash;<?php endif; ?></td>
                                <td><?php echo esc_html( $row['ip_address'] ?? '—' ); ?></td>
                                <td class="f152-cookie-log-table__ua"><?php if ( '' !== $ua ) : ?><?php echo esc_html( CookieConsentLog::browser_name( $ua ) ); ?><details><summary>User-Agent</summary><small><?php echo esc_html( $ua ); ?></small></details><?php else : ?>&mdash;<?php endif; ?></td>
                                <td><?php echo esc_html( $row['policy_version'] ?? '—' ); ?><?php if ( ! empty( $row['language'] ) ) : ?><br><small>Язык: <?php echo esc_html( $row['language'] ); ?></small><?php endif; ?><?php if ( ! empty( $row['policy_url'] ) ) : ?><br><a href="<?php echo esc_url( $row['policy_url'] ); ?>" target="_blank" rel="noopener noreferrer">Политика cookie</a><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom"><div class="tablenav-pages"><?php echo wp_kses_post( paginate_links( [ 'base' => add_query_arg( 'paged', '%#%', add_query_arg( 'log_type', 'cookie', self::tab_url( 'logs' ) ) ), 'format' => '', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'total' => $total_pages, 'current' => $paged ] ) ); ?></div></div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
 }

 private static function render_log_cleanup( string $log_type ): void {
    $cleanup_hash = (string) get_option( 'f152_log_cleanup_password_hash', '' );
    ?>
			<details class="f152-settings-card" style="margin:16px 0;">
				<summary style="cursor:pointer;padding:16px 20px;"><strong>Защищённая очистка журналов</strong></summary>
				<div style="padding:0 20px 20px;">
					<p>Выберите журнал и дату. Будут удалены только записи выбранного журнала по эту дату включительно, в часовом поясе WordPress.</p>
					<?php if ( '' === $cleanup_hash ) : ?>
						<p><strong>Сначала задайте отдельный пароль очистки.</strong> Он не совпадает с паролем WordPress и хранится только в виде хэша.</p>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Удалить записи журнала до выбранной даты включительно? Это действие необратимо.');" style="margin-bottom:20px;">
							<input type="hidden" name="action" value="f152_clear_logs">
                            <input type="hidden" name="log_type" value="<?php echo esc_attr( $log_type ); ?>">
                            <p><label><strong>Что очищать</strong><br><select name="f152_clear_target" required>
                                <option value="forms" <?php selected( $log_type, 'forms' ); ?>>Журнал форм</option>
                                <option value="cookie" <?php selected( $log_type, 'cookie' ); ?>>Cookie-журнал</option>
                                <option value="both">Оба журнала</option>
                            </select></label></p>
							<?php wp_nonce_field( 'f152_clear_logs', 'f152_clear_nonce' ); ?>
							<p><label><strong>Удалить записи по дату включительно</strong><br><input type="date" name="f152_clear_until" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label></p>
							<p><label><strong>Пароль очистки</strong><br><input type="password" name="f152_clear_password" autocomplete="current-password" required></label></p>
							<?php submit_button( 'Удалить выбранный период', 'delete', 'submit', false ); ?>
						</form>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="f152_log_cleanup_password">
                        <input type="hidden" name="log_type" value="<?php echo esc_attr( $log_type ); ?>">
						<?php wp_nonce_field( 'f152_log_cleanup_password', 'f152_log_password_nonce' ); ?>
						<?php if ( '' !== $cleanup_hash ) : ?>
							<p><label><strong>Текущий пароль</strong><br><input type="password" name="f152_current_cleanup_password" autocomplete="current-password" required></label></p>
						<?php endif; ?>
						<p><label><strong><?php echo esc_html( '' === $cleanup_hash ? 'Новый пароль очистки' : 'Новый пароль' ); ?></strong><br><input type="password" name="f152_new_cleanup_password" minlength="8" autocomplete="new-password" required></label></p>
						<p><label><strong>Повторите пароль</strong><br><input type="password" name="f152_confirm_cleanup_password" minlength="8" autocomplete="new-password" required></label></p>
						<?php submit_button( '' === $cleanup_hash ? 'Задать пароль защиты' : 'Изменить пароль защиты', 'secondary', 'submit', false ); ?>
					</form>
					<p class="description">Защита предотвращает случайную или намеренную очистку через интерфейс FZ-152 без отдельного пароля. Пользователь с полным доступом к серверу или базе данных технически может обойти защиту.</p>
				</div>
			</details>
    <?php
 }

 private static function render_form_logs(): void {
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
 	'sureforms'         => 'SureForms',
	'formidable'        => 'Formidable Forms',
	'formlayer'         => 'FormLayer',
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

 	?>
 	<div class="f152-logs-wrap">
		<h3>Журнал форм</h3>
		<div class="f152-onboarding__notice">
			<h3>Что попадает в журнал</h3>
			<p>Решения по cookie находятся в соседней вкладке «Cookie-баннер». Здесь сохраняются согласия на обработку персональных данных, согласия на рассылку и произвольные подтверждения из поддерживаемых форм. Для необязательного произвольного подтверждения фиксируется и ответ «Да», и ответ «Нет».</p>
		</div>

 		<?php if ( ! $table_ready ) : ?>
 			<div class="notice notice-error">
 				<p>Не удалось создать таблицу логов. Обратитесь к администратору сайта.</p>
 			</div>
 		<?php else : ?>
 			<div class="f152-log-actions">
 				<a href="<?php echo esc_url($export_url); ?>" class="button f152-log-actions__export">Экспорт в CSV</a>
 			</div>

			<?php self::render_log_cleanup( 'forms' ); ?>

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
