<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;

final class Helpers {
	public const COOKIE_NAME = 'f152_consent';

	public static function default_banner_text() : string {
		return 'Мы используем файлы <a href="[f152_link_policy_cookie]">cookie</a>, чтобы улучшить ваш опыт на сайте. Нажимая "Принять", вы соглашаетесь с нашей <a href="[f152_link_policy_pd]">политикой конфиденциальности</a>. [f152_btn_accept] [f152_btn_settings] [f152_btn_reject]';
	}

	public static function default_personal_consent_text() : string {
		return 'Подтверждаю ознакомление и соглашаюсь на обработку моих персональных данных по условиям, описанным на странице <a href="[f152_link_consent_pd]">Согласие на обработку персональных данных</a>.';
	}

	public static function maybe_migrate_legacy_default_texts() : void {
		if ( get_option( 'f152_default_text_links_migrated_v2' ) ) {
			return;
		}

		self::migrate_exact_default(
			'f152_banner_text',
			self::default_banner_text(),
			[
				'Мы используем файлы cookie, чтобы улучшить ваш опыт на сайте. Нажимая "Принять", вы соглашаетесь с нашей политикой конфиденциальности. [f152_btn_accept] [f152_btn_settings] [f152_btn_reject]',
			]
		);

		$personal_default = self::default_personal_consent_text();
		$legacy_personal  = [
			'Подтверждаю ознакомление и соглашаюсь на обработку моих персональных данных.',
			'Я подтверждаю ознакомление и даю согласие на обработку персональных данных.',
			'Я подтверждаю ознакомление и даю Согласие на обработку моих персональных данных в порядке и на условиях, указанных в Политике обработки персональных данных.',
			'Я подтверждаю ознакомление и даю Согласие на обработку моих персональных данных в порядке и на условиях, указанных в <a href="[f152_link_policy_pd]">Политике обработки персональных данных</a>.',
			'Подтверждаю ознакомление и соглашаюсь на обработку моих персональных данных по условиям описанным на странице Согласие на обработку персональных данных.',
			'Подтверждаю ознакомление и соглашаюсь на обработку моих персональных данных по условиям описанным на странице <a href="[f152_link_consent_pd]">Согласие на обработку персональных данных</a>.',
			'Я даю согласие на обработку персональных данных в соответствии с Политикой обработки персональных данных.',
			'Я даю согласие на обработку персональных данных в соответствии с <a href="[f152_link_policy_pd]">Политикой обработки персональных данных</a>.',
		];

		foreach ( [ 'f152_comment_text', 'f152_reviews_text', 'f152_checkout_text', 'f152_register_text', 'f152_external_form_text' ] as $option ) {
			self::migrate_exact_default( $option, $personal_default, $legacy_personal );
		}

		if ( class_exists( '\\F152\\Templates' ) ) {
			$popup_default = (string) Templates::default_for_option( 'f152_popup_upper' );
			if ( '' !== $popup_default ) {
				$popup_site_plain = str_replace(
					'<a href="[f152_site_url]">[f152_site_url]</a>',
					'[f152_site_url]',
					$popup_default
				);
				$popup_policy_plain = str_replace(
					'<a href="[f152_link_policy_cookie]">Политикой использования файлов cookie</a>',
					'Политикой использования файлов cookie',
					$popup_site_plain
				);
				$popup_activation_legacy = str_replace(
					'«Персональные настройки Cookie»',
					'«Настройки Cookie»',
					$popup_policy_plain
				);

				self::migrate_exact_default(
					'f152_popup_upper',
					$popup_default,
					[ $popup_site_plain, $popup_policy_plain, $popup_activation_legacy ]
				);
			}

			$policy_cookie_default = (string) Templates::default_for_option( 'f152_text_policy_cookie' );
			if ( '' !== $policy_cookie_default ) {
				$policy_cookie_legacy = str_replace(
					'<a href="[f152_link_policy_pd]">Полной политике конфиденциальности</a>',
					'Полной политике конфиденциальности',
					$policy_cookie_default
				);
				self::migrate_exact_default( 'f152_text_policy_cookie', $policy_cookie_default, [ $policy_cookie_legacy ] );
			}

			$policy_pd_default = (string) Templates::default_for_option( 'f152_text_policy_pd' );
			if ( '' !== $policy_pd_default ) {
				$policy_pd_without_self_link = str_replace(
					'<a href="[f152_link_policy_pd]">[f152_link_policy_pd]</a>',
					'[f152_link_policy_pd]',
					$policy_pd_default
				);
				$policy_pd_without_yandex_link = str_replace(
					'<a href="https://yandex.ru/support/metrika/general/opt-out.html">https://yandex.ru/support/metrika/general/opt-out.html</a>',
					'https://yandex.ru/support/metrika/general/opt-out.html',
					$policy_pd_default
				);
				$policy_pd_legacy = str_replace(
					'<a href="https://yandex.ru/support/metrika/general/opt-out.html">https://yandex.ru/support/metrika/general/opt-out.html</a>',
					'https://yandex.ru/support/metrika/general/opt-out.html',
					$policy_pd_without_self_link
				);
				self::migrate_exact_default(
					'f152_text_policy_pd',
					$policy_pd_default,
					[ $policy_pd_without_self_link, $policy_pd_without_yandex_link, $policy_pd_legacy ]
				);
			}
		}

		update_option( 'f152_default_text_links_migrated_v2', 1, false );
	}

	private static function migrate_exact_default( string $option, string $new_default, array $legacy_defaults ) : void {
		$current = get_option( $option, null );
		if ( null === $current || '' === trim( (string) $current ) || in_array( (string) $current, $legacy_defaults, true ) ) {
			update_option( $option, $new_default );
		}
	}


	public static function option(string $key, $default = '') {
		return get_option($key, $default);
	}

	public static function site_url() : string {
		$val = (string) self::option('f152_site_url', '');
		$val = trim($val);
		if ( $val === '' ) {
			$val = home_url('/');
		}
		return esc_url($val);
	}

	public static function esc_url_allow_empty(string $url) : string {
		$url = trim($url);
		return $url === '' ? '' : esc_url($url);
	}

	public static function replace_common_macros(string $text) : string {
		$map = [
			'[f152_site_url]'             => self::site_url(),
			'[f152_company_name]'         => esc_html( (string) self::option('f152_company_name', '') ),
			'[f152_company_inn]'          => esc_html( (string) self::option('f152_company_inn', '') ),
			'[f152_company_email]'        => esc_html( (string) self::option('f152_company_email', '') ),
			'[f152_policy_version]'       => esc_html( (string) self::option('f152_policy_version', '1.0') ),
			'[f152_link_policy_pd]'       => self::esc_url_allow_empty( (string) self::option('f152_link_policy_pd', '') ),
			'[f152_link_consent_pd]'      => self::esc_url_allow_empty( (string) self::option('f152_link_consent_pd', '') ),
			'[f152_link_policy_cookie]'   => self::esc_url_allow_empty( (string) self::option('f152_link_policy_cookie', '') ),
			'[f152_link_consent_marketing]' => self::esc_url_allow_empty( (string) self::option('f152_link_consent_marketing', '') ),
		];

		return strtr($text, $map);
	}

	public static function button_html(string $type) : string {
		switch ($type) {
			case 'accept':
				$label = esc_html__( 'Принять', 'fz-152-rf' );
				$class = 'f152-btn f152-btn--accept';
				$data  = 'data-f152-accept';
				break;
			case 'settings':
				$label = esc_html__( 'Настроить', 'fz-152-rf' );
				$class = 'f152-btn f152-btn--settings';
				$data  = 'data-f152-open';
				break;
			case 'reject':
				$label = esc_html__( 'Отклонить', 'fz-152-rf' );
				$class = 'f152-btn f152-btn--reject';
				$data  = 'data-f152-reject';
				break;
			default:
				$label = esc_html( $type );
				$class = 'f152-btn';
				$data  = '';
		}

		return sprintf(
			'<button type="button" class="%s" %s>%s</button>',
			esc_attr($class),
			$data !== '' ? $data : '',
			$label
		);
	}

	public static function settings_shortcode_html() : string {
		$view = (string) self::option('f152_btn_settings_view', 'button');
		if ( $view === 'link' ) {
			return self::settings_link_html();
		}

		return self::button_html('settings');
	}

	private static function settings_link_html() : string {
		$label = esc_html__( 'Настроить', 'fz-152-rf' );

		return sprintf(
			'<a href="#" class="%s" data-f152-open>%s</a>',
			esc_attr('f152-link f152-link--settings'),
			$label
		);
	}
	
	public static function replace_button_macros(string $text) : string {
		$map = [
			'[f152_btn_accept]'   => self::button_html('accept'),
			'[f152_btn_settings]' => self::button_html('settings'),
			'[f152_btn_reject]'   => self::button_html('reject'),
		];
		return strtr($text, $map);
	}

	public static function replace_all_macros(string $text) : string {
		$text = self::replace_common_macros($text);
		$text = self::replace_button_macros($text);
		return $text;
	}

	public static function split_banner_content(string $raw) : array {
		$text = self::replace_common_macros($raw);

		$action_macros = [
			'[f152_btn_accept]'   => [__CLASS__, 'button_html'],
			'[f152_btn_settings]' => [__CLASS__, 'settings_shortcode_html'],
			'[f152_btn_reject]'   => [__CLASS__, 'button_html'],
		];

		$actions_html = '';
		$message_text = $text;

		if ( preg_match_all('/\[f152_btn_(accept|settings|reject)\]/', $text, $matches, PREG_SET_ORDER) ) {
			foreach ( $matches as $match ) {
				$macro = $match[0];
				$type  = $match[1];

				if ( $type === 'settings' ) {
					$actions_html .= self::settings_shortcode_html();
				} else {
					$actions_html .= self::button_html($type);
				}

				$message_text = str_replace($macro, '', $message_text);
			}
		}

		$message_text = preg_replace('/\s+/', ' ', $message_text);
		$message_text = trim($message_text);

		return [
			'message' => $message_text,
			'actions' => $actions_html,
		];
	}

	public static function has_consent_cookie() : bool {
		$cookie = self::get_sanitized_consent_cookie_value();
		return is_string($cookie) && $cookie !== '';
	}

        public static function get_consent_cookie() : array {
                $cookie = self::get_sanitized_consent_cookie_value();

                if ( ! is_string( $cookie ) || $cookie === '' ) {
                        return [];
                }
                $val = json_decode($cookie, true);

                return is_array($val) ? $val : [];
        }

        private static function get_sanitized_consent_cookie_value() : ?string {

                $cookie = filter_input( INPUT_COOKIE, self::COOKIE_NAME, FILTER_UNSAFE_RAW );

                if ( $cookie === null ) {
                        return null;
                }

                return sanitize_textarea_field( wp_unslash( (string) $cookie ) );
        }

        public static function sanitize_hex_color_soft(?string $color) : string {
                $color = trim( (string) $color );

                if ( $color === '' ) return '';

                if ( strcasecmp($color, 'transparent') === 0 ) {
                        return 'transparent';
                }

                if ( preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $color) ) {
                        return $color;
                }

                if ( preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|0?\.\d+|1(?:\.0+)?))?\s*\)$/i', $color, $m) ) {
                        $r = min(255, (int) $m[1]);
                        $g = min(255, (int) $m[2]);
                        $b = min(255, (int) $m[3]);
                        $has_alpha = isset($m[4]) && $m[4] !== '';
                        $a = $has_alpha ? min(1, (float) $m[4]) : 1;

                        if ( $has_alpha && $a < 1 ) {
                                return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, rtrim(rtrim(number_format($a, 2, '.', ''), '0'), '.'));
                        }

                        return sprintf('rgb(%d, %d, %d)', $r, $g, $b);
                }

                return '';
        }

        public static function sanitize_metrika_id($val) : string {
                $digits = preg_replace('~\D~', '', (string) $val);
                if ( $digits === '' ) {
                        return '';
                }

                return $digits;
        }

	public static function kses_paragraph($html) : string {
		$html = (string) $html;
		$allowed = [
			'a'      => [
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
			],
			'br'     => [],
			'em'     => [],
			'strong' => [],
			'p'      => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
			'span'   => ['class' => true],
		];
		return wp_kses($html, $allowed);
	}

	public static function safe_json_encode($data) : string {
		$encoded = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
		return is_string($encoded) ? $encoded : '""';
	}
}
