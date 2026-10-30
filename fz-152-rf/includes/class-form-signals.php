<?php
namespace F152;
if ( ! defined( 'ABSPATH' ) ) exit;

final class FormSignals {
	public static function extract( string $html, string $url, string $title ): array {
		if ( ! class_exists( '\DOMDocument' ) || '' === trim( $html ) ) return [];
		$dom = new \DOMDocument();
		$old = libxml_use_internal_errors( true );
		$loaded = $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors(); libxml_use_internal_errors( $old );
		if ( ! $loaded ) return [];
		$xpath = new \DOMXPath( $dom ); $result = []; $index = 0;
		foreach ( $xpath->query( '//form' ) as $form ) {
			if ( ++$index > 60 ) break;
			$context = $form->getAttribute( 'class' ) . ' ' . $form->getAttribute( 'id' );
			$parent = $form->parentNode;
			for ( $i = 0; $i < 4 && $parent instanceof \DOMElement; $i++, $parent = $parent->parentNode ) {
				$context .= ' ' . $parent->getAttribute( 'class' ) . ' ' . $parent->getAttribute( 'id' );
			}
			$providers = [ 'wpcf7' => 'Contact Form 7', 'wpforms' => 'WPForms', 'frm-show-form' => 'Formidable Forms', 'formlayer' => 'FormLayer', 'srfm' => 'SureForms', 'sureforms' => 'SureForms', 'forminator' => 'Forminator', 'fluentform' => 'Fluent Forms', 'fluent' => 'Fluent Forms', 'nf-form' => 'Ninja Forms', 'gform' => 'Gravity Forms', 'metform' => 'MetForm', 'elementor' => 'Elementor Forms', 'wpdiscuz' => 'wpDiscuz', 'commentform' => 'Комментарии WordPress', 'registerform' => 'Регистрация WordPress', 'woocommerce' => 'WooCommerce', 'checkout' => 'WooCommerce', 'f152-quiz' => 'Quiz Forms' ];
			$provider = 'Форма сайта';
			foreach ( $providers as $needle => $label ) if ( false !== stripos( $context, $needle ) ) { $provider = $label; break; }
			$own_context = $form->getAttribute( 'class' ) . ' ' . $form->getAttribute( 'id' );
			 
			 
			$woo_login = 'WooCommerce' === $provider && (bool) preg_match( '/woocommerce[-_ ]form[-_ ]login|(?:^|\s)login(?:\s|$)/i', $own_context );
			$woo_register = 'WooCommerce' === $provider && (bool) preg_match( '/woocommerce[-_ ]form[-_ ]register|(?:^|\s)register(?:\s|$)/i', $own_context );
			$kind = $woo_login ? 'login' : ( $woo_register ? 'registration' : ( preg_match( '/checkout/i', $own_context ) ? 'checkout' : ( preg_match( '/comment/i', $own_context ) ? 'comments' : ( preg_match( '/register|registration/i', $own_context ) ? 'registration' : 'form' ) ) ) );
			if ( $woo_login ) continue;  
			$fields = []; $types = []; $partial_name = false; $marketing = false; $consent = false; $count = 0;
			foreach ( $xpath->query( './/input|.//select|.//textarea', $form ) as $field ) {
				if ( ++$count > 250 ) break;
				$type = strtolower( $field->getAttribute( 'type' ) ?: $field->tagName );
				$name = $field->getAttribute( 'name' );
				$id = $field->getAttribute( 'id' );
				$placeholder = trim( $field->getAttribute( 'placeholder' ) );
				$aria_label = trim( $field->getAttribute( 'aria-label' ) );
				$label = trim( $aria_label . ' ' . $placeholder );
				if ( '' !== $id ) foreach ( $xpath->query( './/label', $form ) as $l ) if ( $l->getAttribute( 'for' ) === $id ) $label .= ' ' . $l->textContent;
				for ( $ancestor = $field->parentNode, $depth = 0; $ancestor instanceof \DOMElement && $depth < 3; $ancestor = $ancestor->parentNode, $depth++ ) {
					if ( 'label' === strtolower( $ancestor->tagName ) ) { $label .= ' ' . $ancestor->textContent; break; }
					if ( $ancestor === $form ) break;
				}
				$label = trim( preg_replace( '/\s+/u', ' ', $label ) );
				if ( 'checkbox' === $type && ! self::hidden( $field, $form ) && ! $field->hasAttribute( 'disabled' ) ) {
					$checkbox_text = trim( $name . ' ' . $id . ' ' . $field->getAttribute( 'value' ) . ' ' . $label );
					$is_marketing = (bool) preg_match( '/marketing|newsletter|subscribe|promo|advert|реклам|рассыл|новост/iu', $checkbox_text );
					$is_pd = 'f152_consent_register' === $name || (bool) preg_match( '/f152.*consent|personal[ _-]?data|privacy|152[ _-]?fz|152[ _-]?фз|персональн[^\s]{0,12}[ \-_]*(?:данн|информац)|обработк[^\s]{0,12}[ \-_]*(?:данн|персональн)|политик[^\s]{0,12}[ \-_]*(?:конфиденц|обработк)|соглас[^\n]{0,40}(?:обработ|персональн)/iu', $checkbox_text );
					if ( $is_marketing ) $marketing = true;
					if ( $is_pd ) $consent = true;
				}
				if ( self::hidden( $field, $form ) || preg_match( '/honeypot|honey.?pot/i', $name ) ) continue;
				if ( in_array( $type, [ 'hidden','submit','button','reset','password','checkbox','radio' ], true ) || $field->hasAttribute( 'disabled' ) ) continue;
				$auto_raw = strtolower( trim( $field->getAttribute( 'autocomplete' ) ) );
				$auto_parts = preg_split( '/\s+/', $auto_raw );
				$auto = is_array( $auto_parts ) && $auto_parts ? (string) end( $auto_parts ) : $auto_raw;
				$semantic_haystack = trim( $name . ' ' . $label . ' ' . $placeholder . ' ' . $auto_raw );
				$detected = self::detect_semantic( $type, $semantic_haystack, $auto, $auto_raw, $kind );
				$semantic = (string) ( $detected['semantic'] ?? '' );
				if ( ! empty( $detected['partial_name'] ) ) $partial_name = true;
				if ( '' !== $semantic ) $types[] = $semantic;
				$fields[] = [
					'name'         => substr( sanitize_text_field( $name ), 0, 120 ),
					'type'         => sanitize_key( $type ),
					'semantic'     => $semantic,
					'placeholder'  => substr( sanitize_text_field( $placeholder ), 0, 160 ),
					'label'        => substr( sanitize_text_field( trim( $label ) ), 0, 200 ),
					'autocomplete' => substr( sanitize_text_field( $auto_raw ), 0, 120 ),
				];
			}
			$submit_text = self::submit_text( $form, $xpath );

			if ( ! $types && ! $partial_name && ! $marketing && ! $consent && ! in_array( $kind, [ 'registration', 'checkout', 'comments' ], true ) ) continue;

			$identity = self::identity( $form, $xpath, $provider, $kind, $context );
			$safe_url = strtok( $url, '?' );
			if ( '' !== $identity['stable_id'] ) {
				$key_seed = $identity['provider_key'] . '|' . $kind . '|' . $identity['stable_id'];
			} else {
				$key_seed = $safe_url . '|' . ( $form->getAttribute( 'id' ) ?: 'form-' . $index );
			}
			$key = substr( hash( 'sha256', $key_seed ), 0, 20 );
			$form_title = self::form_title( $identity['provider_key'], $identity['stable_id'], $identity['embedded_title'] );
			if ( '' === $form_title ) {
				if ( 'checkout' === $kind ) $form_title = 'Форма оформления заказа';
				elseif ( 'registration' === $kind && 'woocommerce' === $identity['provider_key'] ) $form_title = 'Мой аккаунт';
				elseif ( 'registration' === $kind ) $form_title = 'Регистрация пользователей';
				elseif ( 'comments' === $kind ) $form_title = 'Комментарии';
				else $form_title = '' !== $identity['stable_id'] ? 'Форма #' . $identity['stable_id'] : sanitize_text_field( $title );
			}
			$result[ $key ] = [
				'id'          => $key,
				'kind'        => $kind,
				'provider'    => $provider,
				'provider_key'=> $identity['provider_key'],
				'provider_id' => $identity['stable_id'],
				'title'       => sanitize_text_field( $form_title ),
				'page_title'  => sanitize_text_field( $title ),
				'url'         => esc_url_raw( $safe_url ),
				'urls'        => [ esc_url_raw( $safe_url ) ],
				'occurrences' => 1,
				'types'       => array_values( array_unique( $types ) ),
				'partial_name'=> $partial_name,
				'marketing'   => $marketing,
				'consent'     => $consent,
				'fields'      => $fields,
				'submit_text' => $submit_text,
				'evidence'    => 'rendered_form',
			];
		}
		return $result;
	}

	private static function detect_semantic( string $type, string $haystack, string $auto, string $auto_raw, string $kind ): array {
		$h = function_exists( 'mb_strtolower' ) ? mb_strtolower( $haystack, 'UTF-8' ) : strtolower( $haystack );
		$out = [ 'semantic' => '', 'partial_name' => false ];

		if ( 'email' === $type || 'email' === $auto ) return [ 'semantic' => 'email', 'partial_name' => false ];
		if ( 'tel' === $type || preg_match( '/^tel(?:-|$)/', $auto ) ) return [ 'semantic' => 'phone', 'partial_name' => false ];
		if ( preg_match( '/(^|[^[:alnum:]_])(инн|inn)([^[:alnum:]_]|$)/iu', $h ) ) return [ 'semantic' => 'inn', 'partial_name' => false ];
		if ( preg_match( '/(^|[^[:alnum:]_])(снилс|snils)([^[:alnum:]_]|$)/iu', $h ) ) return [ 'semantic' => 'snils', 'partial_name' => false ];

		if ( 'name' === $auto || preg_match( '/\bfull[ _-]?name\b|\bфио\b|фамилия\s*[, ]+имя\s*[, ]+отчество/iu', $h ) ) {
			return [ 'semantic' => 'full_name', 'partial_name' => false ];
		}
		if ( in_array( $auto, [ 'given-name','family-name','additional-name' ], true ) || preg_match( '/(^|[\W_])(имя|фамилия|отчество|first[ _-]?name|last[ _-]?name|family[ _-]?name|given[ _-]?name)([\W_]|$)/iu', $h ) ) {
			$out['partial_name'] = true;
		}

		if ( 'bday-year' === $auto || preg_match( '/год\s+рожд|birth[ _-]?year/iu', $h ) ) $out['semantic'] = 'birth_year';
		elseif ( 'bday-month' === $auto || preg_match( '/месяц\s+рожд|birth[ _-]?month/iu', $h ) ) $out['semantic'] = 'birth_month';
		elseif ( in_array( $auto, [ 'bday','bday-day' ], true ) || preg_match( '/дата\s+рожд|date[ _-]?of[ _-]?birth|birth[ _-]?date|birthday/iu', $h ) ) $out['semantic'] = 'birth_date';
		elseif ( preg_match( '/место\s+рожд|place[ _-]?of[ _-]?birth|birth[ _-]?place/iu', $h ) ) $out['semantic'] = 'birth_place';
		elseif ( preg_match( '/семейн(?:ое|ый)\s+полож|marital[ _-]?status/iu', $h ) ) $out['semantic'] = 'marital_status';
		elseif ( preg_match( '/социальн(?:ое|ый)\s+полож|social[ _-]?status/iu', $h ) ) $out['semantic'] = 'social_status';
		elseif ( preg_match( '/имущественн(?:ое|ый)\s+полож|property[ _-]?status/iu', $h ) ) $out['semantic'] = 'property_status';
		elseif ( preg_match( '/(^|[\W_])(доход|доходы|income)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'income';
		elseif ( preg_match( '/(^|[\W_])(пол|gender|sex)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'gender';

		elseif ( preg_match( '/адрес\s+(?:места\s+)?жительств|residential[ _-]?address|place[ _-]?of[ _-]?residence/iu', $h ) ) $out['semantic'] = 'residence_address';
		elseif ( preg_match( '/адрес\s+регистрац|пропис(?:ка|ки)|registration[ _-]?address/iu', $h ) ) $out['semantic'] = 'registration_address';
		elseif ( preg_match( '/(^|[\W_])shipping(?:[_\-\s]|$)|адрес\s+достав|delivery[ _-]?address/iu', $h ) ) $out['semantic'] = 'delivery_address';
		elseif ( preg_match( '/(^|[\W_])billing(?:[_\-\s]|$)|плат[её]жн(?:ый|ого)\s+адрес|billing[ _-]?address/iu', $h ) ) $out['semantic'] = 'billing_address';
		elseif ( preg_match( '/почтов(?:ый|ого)\s+адрес|postal[ _-]?address|street[ _-]?address|address[ _-]?line/iu', $h ) ) $out['semantic'] = 'postal_address';
		elseif ( 'checkout' === $kind && preg_match( '/(^|[\W_])(address|city|state|postcode|zip|country)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'delivery_address';
		elseif ( preg_match( '/(^|[\W_])address([\W_]|$)|(^|[\W_])адрес([\W_]|$)/iu', $h ) ) $out['semantic'] = 'other_address';

		elseif ( preg_match( '/загран(?:ичный\s+)?паспорт|foreign[ _-]?passport|international[ _-]?passport/iu', $h ) ) $out['semantic'] = 'foreign_identity_document';
		elseif ( preg_match( '/водительск(?:ое|ого)\s+удостовер|driver[ _-]?licen[cs]e|driving[ _-]?licen[cs]e/iu', $h ) ) $out['semantic'] = 'driver_license';
		elseif ( preg_match( '/свидетельств[оа]\s+о\s+рожд|birth[ _-]?certificate/iu', $h ) ) $out['semantic'] = 'birth_certificate';
		elseif ( preg_match( '/паспортн(?:ые|ых)\s+данн|(?:серия|номер)\s+паспорта|паспорт[^\n]{0,25}(?:серия|номер)|passport[ _-]?(?:series|number|data)|документ[^\n]{0,40}удостоверяющ[^\n]{0,30}личност/iu', $h ) ) $out['semantic'] = 'identity_document';
		elseif ( preg_match( '/(^|[\W_])(гражданств|citizenship)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'citizenship';

		elseif ( 'cc-number' === $auto || preg_match( '/номер\s+банковск(?:ой|ая)\s+карт|реквизит(?:ы)?\s+банковск(?:ой|ая)\s+карт|bank[ _-]?card|credit[ _-]?card|debit[ _-]?card/iu', $h ) ) $out['semantic'] = 'bank_card';
		elseif ( preg_match( '/расч[её]тн(?:ый|ого)\s+сч[её]т|settlement[ _-]?account/iu', $h ) ) $out['semantic'] = 'settlement_account';
		elseif ( preg_match( '/лицев(?:ой|ого)\s+сч[её]т|personal[ _-]?account[ _-]?number/iu', $h ) ) $out['semantic'] = 'personal_account';
		elseif ( preg_match( '/(^|[\W_])(профессия|profession|occupation)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'profession';
		elseif ( 'organization-title' === $auto || preg_match( '/(^|[\W_])(должност|job[ _-]?title|position)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'position';
		elseif ( preg_match( '/трудов(?:ая|ой)\s+деятельност|стаж\s+работ|опыт\s+работ|work[ _-]?experience|employment[ _-]?history/iu', $h ) ) $out['semantic'] = 'employment';
		elseif ( preg_match( '/воинск(?:ая|ой|ому|ий)|военн(?:ый|ая)\s+уч[её]т|military[ _-]?(?:duty|record|service)/iu', $h ) ) $out['semantic'] = 'military';
		elseif ( preg_match( '/образован|учебн(?:ое|ого)\s+заведен|диплом|education|university|college/iu', $h ) ) $out['semantic'] = 'education';

		elseif ( 'username' === $auto || preg_match( '/(^|[\W_])(username|user[ _-]?login|логин)([\W_]|$)/iu', $h ) ) $out['semantic'] = 'username';
		elseif ( preg_match( '/(^|[\W_])(сообщение|комментарий|вопрос|обращение|message|comment|question|inquiry)([\W_]|$)/iu', $h ) && in_array( $type, [ 'textarea','text' ], true ) ) $out['semantic'] = 'message_text';
		elseif ( 'file' === $type && preg_match( '/резюме|curriculum\s+vitae|(^|[\W_])cv([\W_]|$)/iu', $h ) ) $out['semantic'] = 'resume_file';

		elseif ( preg_match( '/состояни(?:е|я|и)\s+здоров|медицинск(?:ие|ая)\s+данн|health[ _-]?data|medical[ _-]?data/iu', $h ) ) $out['semantic'] = 'health_signal';
		elseif ( preg_match( '/национальн(?:ая|ой)\s+принадлеж|этническ(?:ая|ое)\s+принадлеж/iu', $h ) ) $out['semantic'] = 'nationality_signal';
		elseif ( preg_match( '/политическ(?:ие|их)\s+взгляд|political[ _-]?views/iu', $h ) ) $out['semantic'] = 'political_views_signal';
		elseif ( preg_match( '/религиозн(?:ые|ых)\s+убежден|философск(?:ие|их)\s+убежден|religious[ _-]?belief|philosophical[ _-]?belief/iu', $h ) ) $out['semantic'] = 'beliefs_signal';
		elseif ( preg_match( '/судимост|criminal[ _-]?record/iu', $h ) ) $out['semantic'] = 'criminal_record_signal';
		elseif ( preg_match( '/фото(?:графия)?\s+лица|селфи|selfie|face[ _-]?photo/iu', $h ) ) $out['semantic'] = 'face_photo_signal';
		elseif ( preg_match( '/запись\s+голоса|голосов(?:ое|ая)\s+сообщен|voice[ _-]?(?:record|message)|audio[ _-]?record/iu', $h ) ) $out['semantic'] = 'voice_signal';
		elseif ( preg_match( '/папиллярн(?:ые|ых)\s+узор|отпечат(?:ок|ки)\s+пальц|fingerprint/iu', $h ) ) $out['semantic'] = 'fingerprint_signal';

		return $out;
	}

	private static function identity( \DOMElement $form, \DOMXPath $xpath, string $provider, string $kind, string $context ): array {
		$map = [
			'Contact Form 7'        => 'cf7',
			'WPForms'               => 'wpforms',
			'Formidable Forms'      => 'formidable',
			'FormLayer'             => 'formlayer',
			'SureForms'             => 'sureforms',
			'Forminator'            => 'forminator',
			'Fluent Forms'          => 'fluent',
			'Ninja Forms'           => 'ninja',
			'Gravity Forms'         => 'gravity',
			'MetForm'               => 'metform',
			'Elementor Forms'       => 'elementor',
			'wpDiscuz'              => 'wpdiscuz',
			'Комментарии WordPress' => 'comments',
			'Регистрация WordPress' => 'registration',
			'WooCommerce'           => 'woocommerce',
			'Quiz Forms'            => 'quiz',
		];
		$provider_key = $map[ $provider ] ?? 'generic';
		if ( 'comments' === $kind || in_array( $provider_key, [ 'comments','wpdiscuz' ], true ) ) return [ 'provider_key' => $provider_key, 'stable_id' => 'comments', 'embedded_title' => '' ];
		if ( 'registration' === $kind || 'registration' === $provider_key ) return [ 'provider_key' => $provider_key, 'stable_id' => 'registration', 'embedded_title' => '' ];
		if ( 'checkout' === $kind || 'woocommerce' === $provider_key ) return [ 'provider_key' => $provider_key, 'stable_id' => 'checkout', 'embedded_title' => '' ];

		$stable = '';
		$title = '';
		$fid = $form->getAttribute( 'id' );
		$attrs = self::ancestor_attributes( $form, [ 'data-form-id','data-formid','form-id','data-id','data-form-key','data-form-key-id','data-srfm-form-id','data-wpforms-form-id','data-form-name' ], 4 );

		switch ( $provider_key ) {
			case 'cf7':
				$stable = self::hidden_value( $xpath, $form, [ '_wpcf7' ] );
				if ( '' === $stable && preg_match( '/wpcf7-f(\d+)/i', $context, $m ) ) $stable = $m[1];
				break;
			case 'wpforms':
				$stable = self::hidden_value( $xpath, $form, [ 'wpforms[id]', 'wpforms[id]' ] );
				if ( '' === $stable && preg_match( '/wpforms-form-(\d+)/i', $fid . ' ' . $context, $m ) ) $stable = $m[1];
				break;
			case 'formidable':
				$stable = self::hidden_value( $xpath, $form, [ 'form_id','form_key' ] );
				if ( '' === $stable && preg_match( '/\bform_([a-z0-9_-]+)/i', $fid, $m ) ) $stable = $m[1];
				break;
			case 'formlayer':
				$stable = $attrs['data-form-id'] ?? '';
				break;
			case 'sureforms':
				$stable = $attrs['data-form-id'] ?? ( $attrs['data-formid'] ?? ( $attrs['form-id'] ?? ( $attrs['data-srfm-form-id'] ?? '' ) ) );
				$stable = $stable ?: self::hidden_value( $xpath, $form, [ 'form-id','form_id','srfm_form_id','post_id' ] );
				if ( '' === $stable && preg_match( '/srfm[^\s"\']*[-_](\d+)/i', $fid . ' ' . $context, $m ) ) $stable = $m[1];
				break;
			case 'forminator':
				$stable = self::hidden_value( $xpath, $form, [ 'form_id','forminator_form_id' ] );
				if ( '' === $stable && preg_match( '/forminator-module-(\d+)/i', $fid . ' ' . $context, $m ) ) $stable = $m[1];
				break;
			case 'fluent':
				$stable = self::hidden_value( $xpath, $form, [ 'form_id','fluentform_id' ] );
				if ( '' === $stable && preg_match( '/fluentform[_-](\d+)/i', $fid . ' ' . $context, $m ) ) $stable = $m[1];
				break;
			case 'ninja':
				$stable = self::hidden_value( $xpath, $form, [ 'form_id','nf_form_id' ] );
				if ( '' === $stable && preg_match( '/nf-form-(\d+)/i', $fid . ' ' . $context, $m ) ) $stable = $m[1];
				break;
			case 'gravity':
				$stable = self::hidden_value( $xpath, $form, [ 'gform_submit','form_id' ] );
				if ( '' === $stable && preg_match( '/gform_(\d+)/i', $fid . ' ' . $context, $m ) ) $stable = $m[1];
				break;
			case 'metform':
				$stable = self::hidden_value( $xpath, $form, [ 'form_id','mf_form_id','metform_form_id' ] );
				break;
			case 'elementor':
				$stable = self::hidden_value( $xpath, $form, [ 'form_id' ] );
				$title  = self::hidden_value( $xpath, $form, [ 'form_name' ] );
				break;
			case 'quiz':
				$stable = self::hidden_value( $xpath, $form, [ 'quiz_id','form_id' ] );
				break;
		}

		if ( '' === $stable ) {
			foreach ( [ 'data-form-id','data-formid','form-id','data-form-key','data-srfm-form-id' ] as $attr ) {
				if ( ! empty( $attrs[ $attr ] ) ) { $stable = $attrs[ $attr ]; break; }
			}
		}
		$stable = substr( sanitize_text_field( (string) $stable ), 0, 120 );
		$title = substr( sanitize_text_field( (string) ( $title ?: ( $attrs['data-form-name'] ?? '' ) ) ), 0, 200 );
		return [ 'provider_key' => $provider_key, 'stable_id' => $stable, 'embedded_title' => $title ];
	}

	private static function ancestor_attributes( \DOMElement $form, array $names, int $depth ): array {
		$out = [];
		$el = $form;
		for ( $i = 0; $i <= $depth && $el instanceof \DOMElement; $i++, $el = $el->parentNode ) {
			foreach ( $names as $name ) if ( ! isset( $out[ $name ] ) && $el->hasAttribute( $name ) ) {
				$value = trim( $el->getAttribute( $name ) );
				if ( '' !== $value ) $out[ $name ] = $value;
			}
		}
		return $out;
	}

	private static function hidden_value( \DOMXPath $xpath, \DOMElement $form, array $names ): string {
		foreach ( $xpath->query( './/input', $form ) as $input ) {
			$name = $input->getAttribute( 'name' );
			if ( ! in_array( $name, $names, true ) ) continue;
			$value = trim( $input->getAttribute( 'value' ) );
			if ( '' !== $value ) return $value;
		}
		return '';
	}

	private static function form_title( string $provider_key, string $stable_id, string $embedded ): string {
		if ( '' !== $embedded ) return $embedded;
		if ( '' === $stable_id || ! ctype_digit( $stable_id ) || ! function_exists( 'get_the_title' ) ) return '';
		if ( ! in_array( $provider_key, [ 'cf7','wpforms','formlayer','sureforms' ], true ) ) return '';
		$title = get_the_title( (int) $stable_id );
		return is_string( $title ) ? trim( wp_strip_all_tags( $title ) ) : '';
	}

	private static function submit_text( \DOMElement $form, \DOMXPath $xpath ): string {
		foreach ( $xpath->query( './/button|.//input', $form ) as $el ) {
			if ( ! $el instanceof \DOMElement ) continue;
			$type = strtolower( trim( $el->getAttribute( 'type' ) ) );
			if ( 'input' === strtolower( $el->tagName ) && 'submit' !== $type ) continue;
			if ( 'button' === strtolower( $el->tagName ) && ! in_array( $type, [ '', 'submit' ], true ) ) continue;
			$text = 'input' === strtolower( $el->tagName ) ? $el->getAttribute( 'value' ) : $el->textContent;
			$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
			if ( '' !== $text ) return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 160 ) : substr( $text, 0, 160 );
		}
		return '';
	}

	private static function hidden( \DOMElement $field, \DOMElement $form ): bool {
		for ( $el = $field; $el instanceof \DOMElement; $el = $el->parentNode ) {
			if ( $el->hasAttribute( 'hidden' ) || 'true' === $el->getAttribute( 'aria-hidden' ) || preg_match( '/(?:display\s*:\s*none|visibility\s*:\s*hidden)/i', $el->getAttribute( 'style' ) ) ) return true;
			if ( $el === $form ) break;
		}
		return false;
	}
}
