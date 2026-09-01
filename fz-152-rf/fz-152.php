<?php
/**
 * Plugin Name: FZ-152 RF
 * Description: Банер куки внизу сайта с кнопками принять/отклонить/настройки, не активные чекбоксы у отзывов, комментов и странице заказа WooCommerce, согласно ФЗ 152. Шаблоны с текстом страниц политик и соглашений.
 * Version: 0.2.5
 * Author: Котик
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI: https://kotikblog.ru
 * Text Domain: fz-152-rf
 */

if ( ! defined('ABSPATH') ) exit;

define('F152_VERSION', '0.2.5');
define('F152_FILE', __FILE__);
define('F152_DIR', plugin_dir_path(__FILE__));
define('F152_URL', plugin_dir_url(__FILE__));
define('F152_SLUG', 'fz-152-rf');

if ( ! defined( 'F152_HAS_CONSULTANTS_SERVICE_TAB' ) ) {
	define( 'F152_HAS_CONSULTANTS_SERVICE_TAB', true );
}

require_once F152_DIR . 'includes/helpers.php';
require_once F152_DIR . 'includes/class-assets.php';
require_once F152_DIR . 'includes/class-consent.php';
require_once F152_DIR . 'includes/class-consent-log.php';
require_once F152_DIR . 'includes/class-banner-stats.php';
require_once F152_DIR . 'includes/class-banner.php';
require_once F152_DIR . 'includes/class-metrika.php';
require_once F152_DIR . 'includes/class-embed-blocker.php';
require_once F152_DIR . 'includes/class-integrations.php';
require_once F152_DIR . 'includes/class-plugin-detector.php';
require_once F152_DIR . 'includes/class-service-catalog.php';
require_once F152_DIR . 'includes/class-policy-services.php';
require_once F152_DIR . 'includes/class-service-scanner.php';
require_once F152_DIR . 'includes/class-service-inventory.php';
require_once F152_DIR . 'includes/class-survey.php';
require_once F152_DIR . 'includes/class-settings.php';
require_once F152_DIR . 'includes/class-templates.php';


require_once F152_DIR . 'includes/class-api.php';

if ( class_exists( '\F152\PolicyServices' ) ) {
	\F152\PolicyServices::init();
}

add_action( 'init', [ '\F152\Helpers', 'maybe_migrate_legacy_default_texts' ], 1 );

if ( ! function_exists( 'f152_get_capabilities' ) ) {
	function f152_get_capabilities() : array {
		if ( class_exists( '\\F152\\API' ) && is_callable( [ '\\F152\\API', 'get_capabilities' ] ) ) {
			return \F152\API::get_capabilities();
		}
		return [];
	}
}

if ( ! function_exists( 'f152_has_capability' ) ) {
	function f152_has_capability( string $capability, int $min_version = 1 ) : bool {
		if ( class_exists( '\\F152\\API' ) && is_callable( [ '\\F152\\API', 'has_capability' ] ) ) {
			return \F152\API::has_capability( $capability, $min_version );
		}
		return false;
	}
}

if ( ! function_exists( 'f152_get_consent_checkbox_html' ) ) {
	function f152_get_consent_checkbox_html( array $args = [] ) : string {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::get_checkbox_html( $args );
		}
		return '';
	}
}

if ( ! function_exists( 'f152_render_consent_checkbox' ) ) {
	function f152_render_consent_checkbox( array $args = [] ) : void {
		if ( class_exists( '\F152\API' ) ) {
			\F152\API::render_checkbox( $args );
		}
	}
}

if ( ! function_exists( 'f152_verify_consent' ) ) {
	function f152_verify_consent( array $request = [], array $args = [] ) : array {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::verify_consent( $request, $args );
		}
		return [
			'valid'         => true,
			'consent_given' => true,
			'nonce_valid'   => true,
			'error'         => '',
		];
	}
}

if ( ! function_exists( 'f152_log_external_consent' ) ) {
	function f152_log_external_consent( array $data = [] ) {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::log_consent( $data );
		}
		return false;
	}
}

if ( ! function_exists( 'f152_get_policy_version' ) ) {
	function f152_get_policy_version() : string {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::get_policy_version();
		}
		return '';
	}
}

if ( ! function_exists( 'f152_get_policy_url' ) ) {
	function f152_get_policy_url() : string {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::get_policy_url();
		}
		return '';
	}
}

if ( ! function_exists( 'f152_get_cookie_consent_state' ) ) {
	function f152_get_cookie_consent_state() : array {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::get_cookie_consent_state();
		}
		return [
			'exists'          => false,
			'current'         => false,
			'legacy'          => false,
			'current_version' => '',
			'consent_version' => null,
			'analytics'       => null,
			'marketing'       => null,
		];
	}
}

if ( ! function_exists( 'f152_is_cookie_category_allowed' ) ) {
	function f152_is_cookie_category_allowed( string $category ) : bool {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::is_cookie_category_allowed( $category );
		}
		return false;
	}
}

if ( ! function_exists( 'f152_has_current_cookie_consent' ) ) {
	function f152_has_current_cookie_consent() : bool {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::has_current_cookie_consent();
		}
		return false;
	}
}

if ( ! function_exists( 'f152_get_cookie_consent_version' ) ) {
	function f152_get_cookie_consent_version() : string {
		if ( class_exists( '\F152\API' ) ) {
			return \F152\API::get_cookie_consent_version();
		}
		return '';
	}
}

add_action( 'woocommerce_init', [ '\\F152\\Integrations', 'register_block_checkout_field' ] );

register_activation_hook(__FILE__, function () {

	add_option('f152_enabled', 1);

	add_option(
		'f152_banner_text',
		\F152\Helpers::default_banner_text()
	);

	add_option('f152_site_url', home_url('/'));
	add_option('f152_company_name', '');
	add_option('f152_company_inn', '');
	add_option('f152_company_email', '');

	add_option('f152_ym_counter', '');
	add_option('f152_yandex_maps_behavior', 'always');

	foreach ( [ 'f152_popup_upper', 'f152_popup_func', 'f152_popup_anal', 'f152_popup_mark' ] as $popup_option ) {
		$popup_default = \F152\Templates::default_for_option( $popup_option );
		if ( '' !== $popup_default ) {
			add_option( $popup_option, $popup_default );
		}
	}

	add_option('f152_link_policy_pd', '');
	add_option('f152_link_consent_pd', '');
	add_option('f152_link_policy_cookie', '');
	add_option('f152_link_consent_marketing', '');

	add_option('f152_comment_enable', 1);
	add_option( 'f152_comment_text', \F152\Helpers::default_personal_consent_text() );

	add_option('f152_reviews_enable', 1);
	add_option( 'f152_reviews_text', \F152\Helpers::default_personal_consent_text() );

	add_option('f152_checkout_enable', 1);
	add_option( 'f152_checkout_text', \F152\Helpers::default_personal_consent_text() );

	add_option('f152_register_enable', 1);
	add_option( 'f152_register_text', \F152\Helpers::default_personal_consent_text() );

	add_option( 'f152_external_form_text', \F152\Helpers::default_personal_consent_text() );

	add_option('f152_cookie_domain', '');

	add_option('f152_theme', 'light');
	add_option('f152_color_bg', '');
	add_option('f152_color_text', '');
	add_option('f152_color_link', '');
	add_option('f152_color_btn_bg', '');
	add_option('f152_color_btn_text', '');
	add_option('f152_btn_settings_view', 'button');
	
	if ( class_exists('\\F152\\Templates') ) {
		\F152\Templates::seed_options_if_empty();
	}

	if ( class_exists('\\F152\\ConsentLog') ) {
		\F152\ConsentLog::create_table();
	}

	if ( class_exists('\\F152\\BannerStats') ) {
		\F152\BannerStats::create_table();
		\F152\BannerStats::ensure_salt();
		\F152\BannerStats::ensure_cron();
	}
});

add_action('plugins_loaded', function () {
	if ( class_exists('\F152\Consent') ) {
		\F152\Consent::maybe_initialize_versioning();
	}
}, 5);

add_action( 'update_option_f152_policy_version', [ '\F152\Consent', 'handle_policy_version_updated' ], 10, 2 );
add_action( 'added_option', function ( $option, $value ) {
	if ( 'f152_policy_version' === $option && class_exists('\F152\Consent') ) {
		\F152\Consent::handle_policy_version_updated( '', $value );
	}
}, 10, 2 );

$f152_service_options = [ 'f152_ym_counter', 'f152_ym_behavior', 'f152_yandex_maps_behavior' ];
foreach ( $f152_service_options as $f152_service_option ) {
	add_action( 'update_option_' . $f152_service_option, [ '\F152\Settings', 'handle_services_setting_updated' ], 10, 2 );
}
add_action( 'added_option', function ( $option, $value ) use ( $f152_service_options ) {
	if ( in_array( $option, $f152_service_options, true ) && class_exists( '\F152\Settings' ) ) {
		\F152\Settings::handle_services_setting_updated( null, $value );
	}
}, 10, 2 );
unset( $f152_service_option, $f152_service_options );

add_action( 'plugins_loaded', [ '\F152\Assets', 'maybe_refresh_asset_cache' ], 6 );
add_action( 'upgrader_process_complete', [ '\F152\Assets', 'handle_upgrader_process_complete' ], 10, 2 );

add_action( 'plugins_loaded', function () {
	if ( class_exists( '\F152\EmbedBlocker' ) ) {
		\F152\EmbedBlocker::maybe_upgrade();
	}
}, 7 );

add_action('plugins_loaded', function () {
	if ( class_exists('\\F152\\BannerStats') ) {
		\F152\BannerStats::maybe_upgrade();
		\F152\BannerStats::init();
	}
});

register_deactivation_hook(__FILE__, function () {
	if ( class_exists('\\F152\\BannerStats') ) {
		\F152\BannerStats::clear_cron();
	}
});

add_action('init', function(){

	if ( ! get_option('f152_enabled', 1) ) {
		return;
	}

	add_shortcode('f152_btn_accept', function() {
		return \F152\Helpers::button_html('accept');
	});
	add_shortcode('f152_btn_settings', function() {
		return \F152\Helpers::settings_shortcode_html();
	});
	add_shortcode('f152_btn_reject', function() {
		return \F152\Helpers::button_html('reject');
	});
	
	add_shortcode('f152_link_policy_pd', function() {
		return esc_url( (string) get_option('f152_link_policy_pd', '') );
	});
	add_shortcode('f152_link_consent_pd', function() {
		return esc_url( (string) get_option('f152_link_consent_pd', '') );
	});
	add_shortcode('f152_link_policy_cookie', function() {
		return esc_url( (string) get_option('f152_link_policy_cookie', '') );
	});
	add_shortcode('f152_link_consent_marketing', function() {
		return esc_url( (string) get_option('f152_link_consent_marketing', '') );
	});
	
	if ( class_exists('\\F152\\Assets') ) {
		\F152\Assets::init();
	}

    if ( class_exists('\\F152\\Metrika') ) {
            \F152\Metrika::init();
    }

    if ( class_exists('\\F152\\EmbedBlocker') ) {
            \F152\EmbedBlocker::init();
    }

    add_action('wp_footer', function(){
            if ( class_exists('\\F152\\Banner') ) {
                    \F152\Banner::render_banner();
                    \F152\Banner::render_popup();
            }
    }, 5);

	if ( class_exists('\\F152\\Integrations') ) {
		\F152\Integrations::init();
	}
});

add_action('admin_init', function(){
	if ( class_exists('\\F152\\Settings') ) {
		\F152\Settings::init();
	}
});
add_action('admin_menu', function(){
        if ( class_exists('\\F152\\Settings') ) {
                \F152\Settings::admin_menu();
        }
});
add_action('admin_enqueue_scripts', function($hook){
        if ( class_exists('\\F152\\Settings') ) {
                \F152\Settings::enqueue_assets($hook);
        }
});
if ( class_exists('\\F152\\Survey') ) {
	\F152\Survey::init();
}
add_action('admin_init', function(){
	if ( class_exists('\\F152\\Templates') ) {
		\F152\Templates::seed_options_if_empty();
	}
});

add_action('admin_init', function () {
	add_option( 'f152_external_form_text', \F152\Helpers::default_personal_consent_text() );
});

add_action('admin_init', function () {
	if ( get_option('f152_seed_main_defaults_done') ) {
		return;
	}

	$defaults = [
		'f152_banner_text' => \F152\Helpers::default_banner_text(),

		'f152_comment_enable' => 1,
		'f152_comment_text'   => \F152\Helpers::default_personal_consent_text(),

		'f152_reviews_enable' => 1,
		'f152_reviews_text'   => \F152\Helpers::default_personal_consent_text(),

		'f152_checkout_enable' => 1,
		'f152_checkout_text'   => \F152\Helpers::default_personal_consent_text(),

		'f152_register_enable' => 1,
		'f152_register_text'   => \F152\Helpers::default_personal_consent_text(),
	];

	foreach ($defaults as $key => $val) {
		$cur = get_option($key, null);
		if ($cur === null || $cur === '') {
			update_option($key, $val);
		}
	}

	update_option('f152_seed_main_defaults_done', 1);
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url(admin_url('options-general.php?page=f152')),
		__('Настройки', 'fz-152-rf')
	);
	array_unshift($links, $settings_link);
	return $links;
});