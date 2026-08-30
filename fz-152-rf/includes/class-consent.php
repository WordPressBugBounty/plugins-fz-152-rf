<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;

final class Consent {
	private static $runtime_raw = null;

	private const LEGACY_VERSION_OPTION = 'f152_cookie_consent_legacy_version';
	private const OBSOLETE_VERSION_OPTION = 'f152_cookie_consent_version';

	public static function name() : string {
		return Helpers::COOKIE_NAME;
	}

	private static function normalize_version( $value ) : string {
		return trim( sanitize_text_field( (string) $value ) );
	}

	public static function maybe_initialize_versioning() : void {
		$needs_cache_purge = false;

		if ( null === get_option( self::LEGACY_VERSION_OPTION, null ) ) {
			add_option( self::LEGACY_VERSION_OPTION, self::current_version() );
			$needs_cache_purge = true;
		}

		if ( null !== get_option( self::OBSOLETE_VERSION_OPTION, null ) ) {
			delete_option( self::OBSOLETE_VERSION_OPTION );
			$needs_cache_purge = true;
		}

		if ( $needs_cache_purge ) {
			self::purge_page_cache();
		}
	}

	public static function current_version() : string {
		return self::normalize_version( get_option( 'f152_policy_version', '' ) );
	}

	public static function legacy_version() : string {
		$value = get_option( self::LEGACY_VERSION_OPTION, null );
		if ( null === $value ) {
			self::maybe_initialize_versioning();
			$value = get_option( self::LEGACY_VERSION_OPTION, '' );
		}
		return self::normalize_version( $value );
	}

	public static function is_current( array $consent ) : bool {
		if ( empty( $consent ) ) {
			return false;
		}

		$current = self::current_version();

		if ( array_key_exists( 'v', $consent ) ) {
			return is_scalar( $consent['v'] ) && (string) $consent['v'] === $current;
		}

		return $current === self::legacy_version();
	}

	public static function handle_policy_version_updated( $old_value, $new_value ) : void {
		$old = self::normalize_version( $old_value );
		$new = self::normalize_version( $new_value );

		if ( $old === $new ) {
			return;
		}

		self::purge_page_cache();
	}

	private static function purge_page_cache() : void {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	public static function domain() : string {
		$domain = (string) get_option('f152_cookie_domain', '');
		$domain = trim($domain);

		if ($domain === '') {
			return '';
		}

		$domain = strtolower($domain);

		return $domain;
	}

	public static function days() : int {
		return 365;
	}

	public static function params() : array {
		$domain = self::domain();
		return [
			'expires'  => time() + self::days() * DAY_IN_SECONDS,
			'path'     => '/',
			'domain'   => $domain !== '' ? $domain : '',
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		];
	}

	public static function raw() : string {
		if ( null !== self::$runtime_raw ) {
			return self::$runtime_raw;
		}

		$cookie_raw = filter_input( INPUT_COOKIE, self::name(), FILTER_UNSAFE_RAW );
		if ( ! is_string( $cookie_raw ) || '' === $cookie_raw ) {
			return '';
		}

		return sanitize_textarea_field( wp_unslash( $cookie_raw ) );
	}

	public static function read() : array {
		$raw = self::raw();
		if ($raw === '') return [];
		$data = json_decode($raw, true);
		return is_array($data) ? $data : [];
	}

	public static function analytics_allowed() : bool {
		$arr = self::read();
		if (empty($arr)) return true;
		if (array_key_exists('analytics', $arr) && $arr['analytics'] === false) {
			return false;
		}
		return true;
	}

	public static function marketing_allowed() : bool {
		$arr = self::read();
		if (empty($arr)) return true;
		if (array_key_exists('marketing', $arr) && $arr['marketing'] === false) {
			return false;
		}
		return true;
	}

	public static function state() : array {
		$consent = self::read();
		$exists  = ! empty( $consent );
		$legacy  = $exists && ! array_key_exists( 'v', $consent );

		$consent_version = null;
		if ( $exists && array_key_exists( 'v', $consent ) && is_scalar( $consent['v'] ) ) {
			$consent_version = self::normalize_version( $consent['v'] );
		}

		return [
			'exists'          => $exists,
			'current'         => $exists && self::is_current( $consent ),
			'legacy'          => $legacy,
			'current_version' => self::current_version(),
			'consent_version' => $consent_version,
			'analytics'       => self::category_decision( $consent, 'analytics' ),
			'marketing'       => self::category_decision( $consent, 'marketing' ),
		];
	}

	public static function is_category_allowed( string $category ) : bool {
		$category = sanitize_key( $category );
		if ( ! in_array( $category, [ 'analytics', 'marketing' ], true ) ) {
			return false;
		}

		$state = self::state();

		return true === $state['current'] && true === $state[ $category ];
	}

	private static function category_decision( array $consent, string $category ) {
		if ( ! array_key_exists( $category, $consent ) ) {
			return null;
		}

		if ( true === $consent[ $category ] ) {
			return true;
		}

		if ( false === $consent[ $category ] ) {
			return false;
		}

		return null;
	}

	public static function set(array $value) : void {
		$value['v'] = self::current_version();
		$value = [ 'v' => $value['v'] ] + $value;

		$json = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
		if ( ! is_string($json) ) {
			return;
		}

		@setcookie(self::name(), $json, self::params());

		self::$runtime_raw = sanitize_textarea_field( $json );
	}

	public static function delete() : void {
		$params = self::params();
		$params['expires'] = time() - YEAR_IN_SECONDS;
		@setcookie(self::name(), '', $params);
		self::$runtime_raw = '';
	}
}
