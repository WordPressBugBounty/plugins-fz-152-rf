<?php
/**
 * Очистка данных при удалении плагина ФЗ-152.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$f152_opts = [
	'f152_enabled',
	'f152_banner_text',
	'f152_banner_default_links_migrated_v1',
	'f152_default_text_links_migrated_v2',
	'f152_document_defaults_migrated_0_2_5',
	'f152_seed_main_defaults_done',
	'f152_site_url',
	'f152_company_name',
	'f152_company_inn',
	'f152_company_email',

	'f152_ym_counter',
	'f152_ym_behavior',
	'f152_ym_disable_on_reject',
	'f152_yandex_maps_behavior',
	'f152_yandex_maps_policy_confirmed',
	'f152_embed_blocker_version',
	'f152_asset_signature',
	'f152_asset_signature_checked_at',
	'f152_scanner_ack_gtm',

	'f152_popup_upper',
	'f152_popup_func',
	'f152_popup_anal',
	'f152_popup_mark',

	'f152_link_policy_pd',
	'f152_link_consent_pd',
	'f152_link_policy_cookie',
	'f152_link_consent_marketing',

	'f152_comment_enable',
	'f152_comment_text',
	'f152_reviews_enable',
	'f152_reviews_text',
	'f152_checkout_enable',
	'f152_checkout_text',
	'f152_register_enable',
	'f152_register_text',
	'f152_external_form_text',

	'f152_cookie_domain',
	'f152_cookie_consent_legacy_version',
	'f152_cookie_consent_version',

	'f152_theme',
	'f152_btn_settings_view',
	'f152_btn_radius',
	'f152_font_size_text',
	'f152_font_size_btn',
	'f152_color_bg',
	'f152_color_text',
	'f152_color_link',
	'f152_color_btn_bg',
	'f152_color_btn_text',
	'f152_color_btn_accept_bg',
	'f152_color_btn_accept_text',
	'f152_color_btn_settings_bg',
	'f152_color_btn_settings_text',
	'f152_color_btn_reject_bg',
	'f152_color_btn_reject_text',
	'f152_custom_css',

	'f152_text_policy_pd',
	'f152_text_consent_pd',
	'f152_text_policy_cookie',
	'f152_text_consent_marketing',
	'f152_policy_version',
	'f152_consent_log_db_version',

	'f152_banner_stats_db_version',
	'f152_banner_stats_salt',

	'f152_generated_page_ids',
	'f152_survey_status_v1',
];

foreach ( $f152_opts as $f152_opt ) {
	delete_option( $f152_opt );
}

delete_transient( 'f152_service_scan_home_v2' );
delete_transient( 'f152_service_scan_home_v3' );
for ( $f152_scan_version = 4; $f152_scan_version <= 17; $f152_scan_version++ ) {
	delete_transient( 'f152_service_scan_site_v' . $f152_scan_version );
}

global $wpdb;
$f152_table_name = esc_sql( $wpdb->prefix . 'f152_consent_log' );

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Удаление таблицы при деинсталляции плагина.
$wpdb->query(
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is internal, plugin-prefixed, and escaped with esc_sql(); identifier placeholders are unreliable in current tooling.
	"DROP TABLE IF EXISTS `{$f152_table_name}`"
);

$f152_stats_table_name = esc_sql( $wpdb->prefix . 'f152_banner_stats' );

$wpdb->query(
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is internal, plugin-prefixed, and escaped with esc_sql().
	"DROP TABLE IF EXISTS `{$f152_stats_table_name}`"
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

wp_clear_scheduled_hook( 'f152_banner_stats_cleanup' );
