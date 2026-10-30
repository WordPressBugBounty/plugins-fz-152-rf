<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;

final class PluginDetector {

	private static $detected = null;

	public static function supported_plugins() : array {
		return [
			[
				'id'    => 'contact-form-7',
				'name'  => 'Contact Form 7',
				'check' => static function () { return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7_ContactForm' ); },
			],
			[
				'id'    => 'wpforms',
				'name'  => 'WPForms',
				'check' => static function () { return function_exists( 'wpforms' ) || defined( 'WPFORMS_VERSION' ); },
			],
			[
				'id'    => 'wpdiscuz',
				'name'  => 'wpDiscuz',
				'check' => static function () { return class_exists( 'WpdiscuzCore' ) || defined( 'WPDISCUZ_VERSION' ); },
			],
			[
				'id'    => 'ninja-forms',
				'name'  => 'Ninja Forms',
				'check' => static function () { return class_exists( 'Ninja_Forms' ) || defined( 'NF_PLUGIN_URL' ); },
			],
			[
				'id'    => 'fluent-forms',
				'name'  => 'Fluent Forms',
				'check' => static function () { return defined( 'FLUENTFORM' ) || defined( 'FLUENTFORM_VERSION' ); },
			],
			[
				'id'    => 'forminator',
				'name'  => 'Forminator',
				'check' => static function () { return defined( 'FORMINATOR_VERSION' ) || class_exists( 'Forminator' ); },
			],
			[
				'id'    => 'metform',
				'name'  => 'MetForm',
				'check' => static function () { return defined( 'METFORM_VERSION' ) || class_exists( '\MetForm\Plugin' ) || did_action( 'metform/after_load' ); },
			],
			[
				'id'    => 'formlayer',
				'name'  => 'FormLayer',
				'check' => static function () { return defined( 'FORMLAYER_VERSION' ) || class_exists( '\FormLayer\Frontend' ); },
			],
			[
				'id'    => 'formidable',
				'name'  => 'Formidable Forms',
				'check' => static function () { return class_exists( 'FrmForm' ); },
			],
			[
				'id'    => 'sureforms',
				'name'  => 'SureForms',
				'check' => static function () { return defined( 'SRFM_VER' ) || class_exists( '\SRFM\Inc\Form_Submit' ); },
			],
			[
				'id'    => 'gravity-forms',
				'name'  => 'Gravity Forms',
				'check' => static function () { return defined( 'GF_VERSION' ) || class_exists( 'GFForms' ) || class_exists( 'GFAPI' ); },
			],
			[
				'id'    => 'elementor-pro',
				'name'  => 'Elementor Pro',
				'check' => static function () {
					if ( ! did_action( 'elementor/loaded' ) ) {
						return false;
					}
					return class_exists( '\ElementorPro\Modules\Forms\Module' )
						|| defined( 'ELEMENTOR_PRO_VERSION' );
				},
			],
		];
	}

	public static function detected_plugins() : array {
		if ( self::$detected !== null ) {
			return self::$detected;
		}

		self::$detected = [];
		foreach ( self::supported_plugins() as $plugin ) {
			$active = false;
			if ( is_callable( $plugin['check'] ) ) {
				$active = (bool) call_user_func( $plugin['check'] );
			}

			if ( $active ) {
				self::$detected[] = [
					'id'   => $plugin['id'],
					'name' => $plugin['name'],
				];
			}
		}

		return self::$detected;
	}

	public static function is_active( string $id ) : bool {
		foreach ( self::detected_plugins() as $plugin ) {
			if ( $plugin['id'] === $id ) {
				return true;
			}
		}
		return false;
	}

	public static function is_pro_active() : bool {
		return defined( 'F152_PRO_VERSION' );
	}

	public static function should_show_block() : bool {
		if ( self::is_pro_active() ) {
			return false;
		}
		return ! empty( self::detected_plugins() );
	}
}
