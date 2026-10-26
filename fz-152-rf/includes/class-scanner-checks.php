<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ScannerChecks {
	public static function registry(): array {
		return [
			'form_not_verified' => [ 'category' => 'forms', 'severity' => 'info', 'chip' => 'Не проверена', 'icon' => 'forms' ],
			'form_consent_missing' => [ 'category' => 'forms', 'severity' => 'warning', 'chip' => 'Проверить согласие', 'icon' => 'forms' ],
			'form_consent_found' => [ 'category' => 'forms', 'severity' => 'success', 'chip' => 'Согласие найдено', 'icon' => 'forms' ],
			'form_plugin_active' => [ 'category' => 'forms', 'severity' => 'info', 'chip' => 'Плагин активен', 'icon' => 'forms' ],
			'external_domain_unknown' => [ 'category' => 'external', 'severity' => 'warning', 'chip' => 'Проверить', 'icon' => 'admin-site-alt3' ],
			'external_domain_known' => [ 'category' => 'external', 'severity' => 'info', 'chip' => 'Распознан', 'icon' => 'admin-site-alt3' ],
			'service_control_issue' => [ 'category' => 'services', 'severity' => 'warning', 'chip' => 'Нужно исправить', 'icon' => 'search' ],
		];
	}

	public static function build( array $snapshot, array $tracker_issues = [], array $detected_plugins = [] ): array {
		$checks = [];
		$forms = isset( $snapshot['forms'] ) && is_array( $snapshot['forms'] ) ? $snapshot['forms'] : [];
		$provider_keys = [];

		foreach ( $forms as $form ) {
			if ( ! is_array( $form ) ) continue;
			$types = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( $form['types'] ?? [] ) ) ) ) );
			if ( empty( $types ) && empty( $form['partial_name'] ) ) continue;
			$provider_key = sanitize_key( (string) ( $form['provider_key'] ?? '' ) );
			if ( '' !== $provider_key ) $provider_keys[] = $provider_key;
			 
			 
			if ( 'woocommerce_myaccount_page' === (string) ( $form['evidence'] ?? '' ) ) {
				$title = self::form_title( $form );
				$registration_enabled = ! empty( $types );
				$message = $registration_enabled
					? 'В WooCommerce разрешена регистрация, но сканер не смог проверить её форму в гостевой версии страницы. Наличие или отсутствие чекбокса не установлено.'
					: 'Страница личного кабинета WooCommerce найдена, но регистрация на ней не подтверждена.';
				$checks[] = self::item( 'form_not_verified', [
					'id' => 'form:woocommerce-registration-unverified',
					'title' => $title,
					'message' => $message,
					'recommendation' => 'Проверьте страницу в режиме инкогнито и повторите диагностику. Убедитесь, что защита и кэш не мешают получать гостевую страницу.',
					'evidence' => [ 'source' => 'woocommerce_settings', 'verified' => false, 'urls' => array_values( array_filter( array_map( 'esc_url_raw', (array) ( $form['urls'] ?? [] ) ) ) ) ],
				] );
				continue;
			}
			$has_consent = ! empty( $form['consent'] );
			$check_id = $has_consent ? 'form_consent_found' : 'form_consent_missing';
			$labels = self::semantic_labels( $types, ! empty( $form['partial_name'] ) );
			$title = self::form_title( $form );
			$message = $has_consent
				? sprintf( 'Форма собирает: %s. Явный чекбокс согласия на обработку персональных данных найден.', implode( ', ', $labels ) )
				: sprintf( 'Форма собирает: %s, но явный чекбокс согласия на обработку персональных данных не найден. Проверьте форму вручную и добавьте согласие, если оно требуется для этого сценария.', implode( ', ', $labels ) );
			if ( ! empty( $form['marketing'] ) ) $message .= ' Отдельное согласие на рассылку/рекламу также обнаружено.';
			$checks[] = self::item( $check_id, [
				'id' => 'form:' . sanitize_key( (string) ( $form['id'] ?? md5( $title ) ) ),
				'title' => $title,
				'message' => $message,
				'recommendation' => $has_consent ? 'Проверьте текст согласия и ссылку на политику.' : 'Добавьте отдельный явный чекбокс согласия и проверьте фиксацию согласия в журнале.',
				'evidence' => [
					'types' => $types,
					'provider' => sanitize_text_field( (string) ( $form['provider'] ?? '' ) ),
					'provider_id' => sanitize_text_field( (string) ( $form['provider_id'] ?? '' ) ),
					'urls' => array_values( array_filter( array_map( 'esc_url_raw', (array) ( $form['urls'] ?? [] ) ) ) ),
					'submit_text' => sanitize_text_field( (string) ( $form['submit_text'] ?? '' ) ),
					'consent' => $has_consent,
					'marketing' => ! empty( $form['marketing'] ),
				],
			] );
		}
		$provider_keys = array_values( array_unique( $provider_keys ) );

		$plugin_provider_map = [ 'contact-form-7' => 'cf7', 'wpforms' => 'wpforms', 'wpdiscuz' => 'wpdiscuz', 'ninja-forms' => 'ninja', 'fluent-forms' => 'fluent', 'forminator' => 'forminator', 'metform' => 'metform', 'formlayer' => 'formlayer', 'formidable' => 'formidable', 'sureforms' => 'sureforms', 'gravity-forms' => 'gravity', 'elementor-pro' => 'elementor' ];
		$scan_complete = ! empty( $snapshot['scan_meta']['browser_complete'] );
		foreach ( $scan_complete ? $detected_plugins : [] as $plugin ) {
			if ( ! is_array( $plugin ) ) continue;
			$plugin_id = sanitize_key( (string) ( $plugin['id'] ?? '' ) );
			$provider_key = $plugin_provider_map[ $plugin_id ] ?? '';
			if ( '' !== $provider_key && in_array( $provider_key, $provider_keys, true ) ) continue;
			$name = sanitize_text_field( (string) ( $plugin['name'] ?? $plugin_id ) );
			$checks[] = self::item( 'form_plugin_active', [ 'id' => 'plugin:' . $plugin_id, 'title' => $name, 'message' => 'Плагин форм активен, но на проверенных опубликованных страницах форма этого типа не была найдена. Если такая форма используется на сайте, проверьте её вручную.', 'recommendation' => 'Откройте страницу с формой и повторите сканирование. Для сторонних форм бесплатная версия не добавляет согласие автоматически.', 'evidence' => [ 'plugin_id' => $plugin_id ] ] );
		}

		$domains = isset( $snapshot['external_domains'] ) && is_array( $snapshot['external_domains'] ) ? $snapshot['external_domains'] : [];
		foreach ( $domains as $host => $domain ) {
			if ( ! is_array( $domain ) ) continue;
			$known = ! empty( $domain['known'] );
			$check_id = $known ? 'external_domain_known' : 'external_domain_unknown';
			$types = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( $domain['resource_types'] ?? [] ) ) ) ) );
			$labels = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $domain['service_labels'] ?? [] ) ) ) );
			if ( $known ) {
				$message = sprintf( 'Внешний домен используется на проверенных страницах и распознан как: %s. Тип подключения: %s.', implode( ', ', $labels ), self::resource_types_label( $types ) );
				$recommendation = 'Проверьте найденный сервис в разделе «Сервисы и трекеры» и его режим загрузки.';
			} else {
				$message = sprintf( 'Страницы сайта обращаются к внешнему домену %s. Тип подключения: %s. Сканер не смог связать домен с известным сервисом FZ-152.', sanitize_text_field( (string) $host ), self::resource_types_label( $types ) );
				$recommendation = 'Проверьте, что это за сервис, какие данные он получает и должен ли он загружаться до согласия. Само наличие внешнего домена ещё не означает нарушение.';
			}
			$checks[] = self::item( $check_id, [
				'id' => 'domain:' . sanitize_key( str_replace( '.', '-', (string) $host ) ),
				'title' => sanitize_text_field( (string) $host ),
				'message' => $message,
				'recommendation' => $recommendation,
				'evidence' => [ 'host' => sanitize_text_field( (string) $host ), 'known' => $known, 'service_ids' => array_values( array_filter( array_map( 'sanitize_key', (array) ( $domain['service_ids'] ?? [] ) ) ) ), 'service_labels' => $labels, 'resource_types' => $types, 'resources' => array_values( array_filter( array_map( 'esc_url_raw', (array) ( $domain['resources'] ?? [] ) ) ) ), 'locations' => (array) ( $domain['locations'] ?? [] ) ],
			] );
		}

		foreach ( $tracker_issues as $issue ) {
			if ( ! is_array( $issue ) ) continue;
			$issue_id = sanitize_key( (string) ( $issue['id'] ?? 'service' ) );
			$checks[] = self::item( 'service_control_issue', [
				'id' => 'service:' . $issue_id,
				'title' => sanitize_text_field( (string) ( $issue['label'] ?? $issue_id ) ),
				'message' => sanitize_text_field( (string) ( $issue['message'] ?? '' ) ),
				'recommendation' => sanitize_text_field( (string) ( $issue['message'] ?? '' ) ),
				'evidence' => [ 'service_id' => $issue_id, 'ids' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $issue['ids'] ?? [] ) ) ) ), 'locations' => (array) ( $issue['locations'] ?? [] ) ],
			] );
		}
		return $checks;
	}

	public static function issue_count( array $checks ): int {
		$count = 0;
		foreach ( $checks as $check ) if ( is_array( $check ) && in_array( (string) ( $check['severity'] ?? '' ), [ 'warning', 'error' ], true ) ) $count++;
		return $count;
	}

	 
	public static function presentation_groups( array $checks ): array {
		$result = [ 'attention' => [], 'reference' => [], 'consent_verified' => 0, 'unverified' => 0 ];
		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) ) continue;
			$severity = (string) ( $check['severity'] ?? 'info' );
			$result[ in_array( $severity, [ 'warning', 'error' ], true ) ? 'attention' : 'reference' ][] = $check;
			if ( 'form_consent_found' === (string) ( $check['check_id'] ?? '' ) ) $result['consent_verified']++;
			if ( 'form_not_verified' === (string) ( $check['check_id'] ?? '' ) ) $result['unverified']++;
		}
		return $result;
	}

	private static function item( string $check_id, array $data ): array {
		$registry = self::registry();
		$definition = $registry[ $check_id ] ?? [ 'category' => 'other', 'severity' => 'info', 'chip' => 'Информация', 'icon' => 'info-outline' ];
		return array_merge( [ 'check_id' => sanitize_key( $check_id ), 'category' => sanitize_key( (string) $definition['category'] ), 'severity' => sanitize_key( (string) $definition['severity'] ), 'chip' => sanitize_text_field( (string) $definition['chip'] ), 'icon' => sanitize_key( (string) $definition['icon'] ), 'id' => sanitize_key( $check_id ), 'title' => '', 'message' => '', 'recommendation' => '', 'evidence' => [] ], $data );
	}

	private static function form_title( array $form ): string {
		$provider = sanitize_text_field( (string) ( $form['provider'] ?? 'Форма сайта' ) );
		$title = sanitize_text_field( (string) ( $form['title'] ?? '' ) );
		return '' === $title || $title === $provider ? $provider : $provider . ': ' . $title;
	}

	private static function semantic_labels( array $types, bool $partial_name ): array {
		$map = [
			'email' => 'email', 'phone' => 'телефон', 'full_name' => 'ФИО', 'birth_year' => 'год рождения', 'birth_month' => 'месяц рождения', 'birth_date' => 'дату рождения', 'birth_place' => 'место рождения', 'marital_status' => 'семейное положение', 'social_status' => 'социальное положение', 'property_status' => 'имущественное положение', 'income' => 'доход', 'gender' => 'пол', 'residence_address' => 'адрес проживания', 'registration_address' => 'адрес регистрации', 'delivery_address' => 'адрес доставки', 'billing_address' => 'платёжный адрес', 'postal_address' => 'почтовый адрес', 'other_address' => 'адрес', 'identity_document' => 'паспортные данные', 'foreign_identity_document' => 'данные загранпаспорта', 'driver_license' => 'водительское удостоверение', 'birth_certificate' => 'свидетельство о рождении', 'citizenship' => 'гражданство', 'inn' => 'ИНН', 'snils' => 'СНИЛС', 'bank_card' => 'данные банковской карты', 'settlement_account' => 'расчётный счёт', 'personal_account' => 'лицевой счёт', 'profession' => 'профессию', 'position' => 'должность', 'employment' => 'данные о работе', 'military' => 'данные воинского учёта', 'education' => 'образование', 'username' => 'логин', 'message_text' => 'текст сообщения', 'resume_file' => 'резюме', 'health_signal' => 'сведения о здоровье', 'nationality_signal' => 'сведения о национальности', 'political_views_signal' => 'политические взгляды', 'beliefs_signal' => 'религиозные/философские убеждения', 'criminal_record_signal' => 'сведения о судимости', 'face_photo_signal' => 'фотографию лица', 'voice_signal' => 'запись голоса', 'fingerprint_signal' => 'отпечатки пальцев',
		];
		$labels = [];
		if ( $partial_name && ! in_array( 'full_name', $types, true ) ) $labels[] = 'имя/фамилию/отчество';
		foreach ( $types as $type ) $labels[] = $map[ $type ] ?? str_replace( '_', ' ', $type );
		$labels = array_values( array_unique( array_filter( $labels ) ) );
		return $labels ?: [ 'персональные данные' ];
	}

	private static function resource_types_label( array $types ): string {
		$map = [ 'script' => 'скрипт', 'iframe' => 'iframe', 'img' => 'изображение', 'link' => 'ресурс страницы', 'embed' => 'embed', 'object' => 'object', 'form' => 'отправка формы', 'source' => 'медиа', 'video' => 'видео' ];
		$labels = [];
		foreach ( $types as $type ) $labels[] = $map[ $type ] ?? $type;
		return implode( ', ', array_values( array_unique( array_filter( $labels ) ) ) );
	}
}
