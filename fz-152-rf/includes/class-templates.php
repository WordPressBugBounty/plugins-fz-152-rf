<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;

final class Templates {

	private static function map() : array {
		return [
			'f152_popup_upper'            => 'popup_upper.html',
			'f152_popup_func'             => 'popup_func.html',
			'f152_popup_anal'             => 'popup_anal.html',
			'f152_popup_mark'             => 'popup_mark.html',
			'f152_text_policy_pd'         => 'policy_pd.html',
			'f152_text_consent_pd'        => 'consent_pd.html',
			'f152_text_policy_cookie'     => 'policy_cookie.html',
			'f152_text_consent_marketing' => 'consent_marketing.html',
		];
	}

	private static function base_dir() : string {
		return rtrim(F152_DIR, '/\\') . '/assets/texts/';
	}
	public static function path_for_option(string $option) : string {
		$map = self::map();
		if ( ! isset($map[$option]) ) return '';
		return self::base_dir() . $map[$option];
	}

	public static function default_for_option(string $option) : string {
		$path = self::path_for_option($option);
		if ( $path === '' ) return '';
		if ( ! file_exists($path) || ! is_readable($path) ) return '';
		$raw = file_get_contents($path);
		return is_string($raw) ? $raw : '';
	}

	public static function seed_options_if_empty() : void {
		foreach ( array_keys(self::map()) as $opt ) {
			$val = get_option($opt, null);
			if ( $val === null || $val === '' ) {
				$def = self::default_for_option($opt);
				if ( $def !== '' ) {

					if ( get_option($opt, null) === null ) {
						add_option($opt, $def);
					} else {
						update_option($opt, $def);
					}
				}
			}
		}
	}
}
