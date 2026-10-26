<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

 
final class MultilingualSafety {
	 
	public static function providers(): array {
		$providers = [];
		$active = (array) get_option( 'active_plugins', [] );
		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_site_option' ) ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) );
		}
		$active = array_map( 'strval', $active );
		$has_active_prefix = static function ( string $prefix ) use ( $active ): bool {
			foreach ( $active as $basename ) {
				if ( 0 === strpos( $basename, $prefix ) ) { return true; }
			}
			return false;
		};
		$qx_active = $has_active_prefix( 'qtranslate-x/' );
		$qxt_active = $has_active_prefix( 'qtranslate-xt/' );
		if ( $qx_active && $qxt_active ) {
			 
			$providers[] = 'qtranslate-x';
			$providers[] = 'qtranslate-xt';
		} elseif ( function_exists( 'qtranxf_getLanguage' ) || $qx_active || $qxt_active ) {
			$providers[] = 'qtranslate';
		}
		if ( ( function_exists( 'pll_current_language' ) && function_exists( 'pll_languages_list' ) )
			|| $has_active_prefix( 'polylang/' ) || $has_active_prefix( 'polylang-pro/' ) ) {
			$providers[] = 'polylang';
		}
		if ( defined( 'ICL_SITEPRESS_VERSION' ) || defined( 'WPML_PLUGIN_BASENAME' )
			|| isset( $GLOBALS['sitepress'] ) || $has_active_prefix( 'sitepress-multilingual-cms/' ) ) {
			$providers[] = 'wpml';
		}
		return array_values( array_unique( $providers ) );
	}

	 
	public static function provider(): string {
		$providers = self::providers();
		return 1 === count( $providers ) ? $providers[0] : '';
	}

	public static function has_conflict(): bool {
		return count( self::providers() ) > 1;
	}

	public static function is_multilingual(): bool {
		 
		return count( self::providers() ) > 0;
	}

	 
	public static function may_automatically_write( int $page_id = 0 ): bool {
		if ( self::is_multilingual() ) {
			return false;
		}
		 
		if ( $page_id > 0 ) {
			$post = get_post( $page_id, OBJECT, 'raw' );
			$raw  = $post instanceof \WP_Post ? (string) $post->post_content : '';
			if ( preg_match( '/(?:\[:[a-z]{2}(?:[-_][a-z0-9]+)?\]|\[:\]|<!--\:[a-z]{2}|\{:[a-z]{2})/i', $raw ) ) {
				return false;
			}
		}
		return true;
	}

	public static function admin_notice(): void {
		if ( ! self::is_multilingual() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'f152' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>FZ-152 RF:</strong> обнаружен мультиязычный сайт. Автоматическая перезапись и синхронизация юридических страниц отключены, чтобы не уничтожить переводы. Редактируйте страницы вручную. Тексты шаблонов в настройках можно просматривать и изменять.</p></div>';
	}
}
