<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;
final class Integrations {
	private static $checkout_consent_verified = false;
	public static function init() : void {
		if ( ! get_option('f152_enabled', 1) ) return;
		add_action('comment_form_after_fields',      [__CLASS__, 'render_wp_comment_checkbox']);
		add_action('comment_form_logged_in_after',   [__CLASS__, 'render_wp_comment_checkbox']);
		add_filter('preprocess_comment',             [__CLASS__, 'validate_wp_comment']);
		add_action('comment_post',                   [__CLASS__, 'save_comment_meta'], 10, 3);

		if ( class_exists( 'WooCommerce' ) ) {
			add_action('woocommerce_review_order_before_submit', [__CLASS__, 'render_checkout_checkbox'], 9);
			add_action('woocommerce_after_checkout_validation',  [__CLASS__, 'validate_checkout'], 10, 2);
			add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_checkout_meta'], 10, 1);
			add_action('woocommerce_set_additional_field_value', [__CLASS__, 'sync_block_checkout_consent'], 10, 4);
	
			add_action('woocommerce_register_form',       [__CLASS__, 'render_register_checkbox'], 9);
			add_filter('woocommerce_registration_errors', [__CLASS__, 'validate_register'], 10, 3);
			add_action('woocommerce_created_customer',    [__CLASS__, 'save_register_consent'], 10, 3);
		}
	}

	public static function render_wp_comment_checkbox() : void {
		if ( ! ( $GLOBALS['post'] instanceof \WP_Post ) ) {
			return;
		}

		$post_id = (int) $GLOBALS['post']->ID;
		if ( $post_id <= 0 ) {
			return;
		}

		$type = get_post_type( $post_id );

		if ( $type === 'elementor_library' ) {
			return;
		}

		if ( self::is_elementor_context_active() ) {
			return;
		}

		if ( ! comments_open( $post_id ) ) {
			return;
		}

		$is_product = ( $type === 'product' );

		$enable_comments = (bool) get_option('f152_comment_enable', 1);
		$enable_reviews  = (bool) get_option('f152_reviews_enable', 1);

		if ( $is_product && ! $enable_reviews )  return;
		if ( ! $is_product && ! $enable_comments ) return;

		$text_raw = $is_product
			? (string) get_option('f152_reviews_text', Helpers::default_personal_consent_text())
			: (string) get_option('f152_comment_text', Helpers::default_personal_consent_text());

		$text = Helpers::kses_paragraph( Helpers::replace_common_macros( $text_raw ) );

                ?>
                <p class="comment-form-f152">
                        <label>
                                <input type="checkbox" name="f152_consent_comment" value="1" required>
                                <span class="f152-text"><?php echo wp_kses_post( $text ); ?></span>
                        </label>
                        <?php wp_nonce_field( 'f152_comment_consent', 'f152_comment_consent_nonce' ); ?>
                </p>
                <?php
        }

	private static function is_elementor_context_active() : bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only editor/preview detection, no data persisted.
		$preview = isset( $_GET['elementor-preview'] )
			? sanitize_text_field( wp_unslash( (string) $_GET['elementor-preview'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $preview !== '' ) {
			return true;
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- read-only Elementor AJAX action detection, no data persisted.
			$action = isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] )
				? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) )
				: '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			if ( $action !== '' && strpos( $action, 'elementor' ) !== false ) {
				return true;
			}
		}

		if ( class_exists( '\Elementor\Plugin' ) ) {
			try {
				$plugin = \Elementor\Plugin::instance();
				if ( $plugin && method_exists( $plugin, 'editor' ) ) {
					$editor = $plugin->editor;
					if ( is_object( $editor ) && method_exists( $editor, 'is_edit_mode' ) && $editor->is_edit_mode() ) {
						return true;
					}
				}
				if ( $plugin && method_exists( $plugin, 'preview' ) ) {
					$preview_obj = $plugin->preview;
					if ( is_object( $preview_obj ) && method_exists( $preview_obj, 'is_preview_mode' ) && $preview_obj->is_preview_mode() ) {
						return true;
					}
				}
			} catch ( \Throwable $e ) {
			}
		}

		return false;
	}

	public static function validate_wp_comment(array $commentdata) : array {
		self::maybe_debug_wpdiscuz_request( 'enter', $commentdata );

		$post_id = (int) ( $commentdata['comment_post_ID'] ?? 0 );
		if ( $post_id <= 0 ) {
			return $commentdata;
		}

		if ( self::is_wpdiscuz_request( $commentdata ) ) {
			return $commentdata;
		}

		$type       = get_post_type( $post_id );
		$is_product = ( $type === 'product' );

		$enable_comments = (bool) get_option( 'f152_comment_enable', 1 );
		$enable_reviews  = (bool) get_option( 'f152_reviews_enable', 1 );

		$need_check = ( $is_product && $enable_reviews ) || ( ! $is_product && $enable_comments );

		if ( $need_check ) {
			$nonce_raw = filter_input( INPUT_POST, 'f152_comment_consent_nonce', FILTER_UNSAFE_RAW );
			$nonce_raw = is_string( $nonce_raw ) ? $nonce_raw : '';
			$nonce     = sanitize_text_field( wp_unslash( $nonce_raw ) );

			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'f152_comment_consent' ) ) {
				self::maybe_debug_wpdiscuz_request( 'wp_die_nonce', $commentdata );
				wp_die(
					esc_html__( 'Ошибка проверки безопасности. Попробуйте ещё раз.', 'fz-152-rf' ),
					esc_html__( 'Ошибка', 'fz-152-rf' ),
					[ 'response' => 400 ]
				);
			}

			$consent_raw = filter_input( INPUT_POST, 'f152_consent_comment', FILTER_UNSAFE_RAW );
			$consent_raw = is_string( $consent_raw ) ? $consent_raw : '';
			$consent_val = sanitize_text_field( wp_unslash( $consent_raw ) );
			$consent     = ( $consent_val === '1' );

			$commentdata['f152_consent_comment'] = $consent ? 1 : 0;

			if ( ! $consent ) {
				self::maybe_debug_wpdiscuz_request( 'wp_die_consent', $commentdata );
				wp_die(
					esc_html__( 'Для отправки требуется согласие на обработку персональных данных.', 'fz-152-rf' ),
					esc_html__( 'Отказ в отправке', 'fz-152-rf' ),
					[ 'response' => 400 ]
				);
			}
		} else {
			$commentdata['f152_consent_comment'] = 0;
		}

		return $commentdata;
	}

	private static function is_wpdiscuz_request( array $commentdata = [] ) : bool {
		$is_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
		if ( ! $is_ajax ) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- read-only key presence detection only.
		$has_free_fields = isset( $_POST['f152_comment_consent_nonce'] )
			|| isset( $_POST['f152_consent_comment'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $has_free_fields ) {
			return false;
		}

		$wpdiscuz_loaded = defined( 'WPDISCUZ_VERSION' ) || class_exists( 'WpdiscuzCore' );
		if ( ! $wpdiscuz_loaded ) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- read-only detection, sanitized immediately.
		$action      = isset( $_POST['action'] ) && is_scalar( $_POST['action'] )
			? sanitize_key( wp_unslash( (string) $_POST['action'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) && is_scalar( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '';

		$action_looks_wpdiscuz = (
			$action === 'wpdiscuz_ajax'
			|| strpos( $action, 'wpdiscuz' ) !== false
			|| strpos( $action, 'wpd' ) !== false
		);

		$request_looks_ajax = ( strpos( $request_uri, 'admin-ajax.php' ) !== false );
		if ( ! $request_looks_ajax ) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- read-only key presence only.
		$has_comment_signals = (
			isset( $commentdata['comment_post_ID'] )
			|| isset( $_POST['comment_post_ID'] )
			|| isset( $_POST['comment'] )
			|| isset( $_POST['wpdiscuz_unique_id'] )
			|| isset( $_POST['wpd_comment_text'] )
			|| isset( $_POST['wpdiscuz_comment'] )
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return ( $action_looks_wpdiscuz || $has_comment_signals );
	}

	private static function maybe_debug_wpdiscuz_request( string $stage, array $commentdata = [] ) : void {
		if ( ! defined( 'F152_DEBUG_WPDISCUZ' ) || ! F152_DEBUG_WPDISCUZ ) {
			return;
		}

		$is_ajax            = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
		$wpdiscuz_version   = defined( 'WPDISCUZ_VERSION' );
		$wpdiscuz_core      = class_exists( 'WpdiscuzCore' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) && is_scalar( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '';

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- read-only diagnostic, gated by F152_DEBUG_WPDISCUZ; nothing persisted, values only logged/sanitized.
		$action = isset( $_POST['action'] ) && is_scalar( $_POST['action'] )
			? sanitize_key( wp_unslash( (string) $_POST['action'] ) )
			: '(none)';

		$post_keys = is_array( $_POST ) ? array_keys( $_POST ) : [];
		$post_keys = array_values( array_filter( $post_keys, 'is_string' ) );

		$has_nonce_free    = isset( $_POST['f152_comment_consent_nonce'] ) ? 1 : 0;
		$has_consent_free  = isset( $_POST['f152_consent_comment'] ) ? 1 : 0;
		$has_nonce_pro     = isset( $_POST['f152_external_consent_nonce'] ) ? 1 : 0;
		$has_consent_pro   = isset( $_POST['f152_consent'] ) ? 1 : 0;

		$cdata_post_id = isset( $commentdata['comment_post_ID'] )
			? (int) $commentdata['comment_post_ID'] : 0;
		$post_post_id = isset( $_POST['comment_post_ID'] )
			? (int) $_POST['comment_post_ID'] : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$is_wpdiscuz = self::is_wpdiscuz_request( $commentdata );

		$line = sprintf(
			'[F152 wpDiscuz debug] stage=%s ajax=%d wpdiscuz_version=%d wpdiscuz_core=%d action=%s request_uri=%s free_nonce=%d free_consent=%d pro_nonce=%d pro_consent=%d cdata_post_id=%d post_post_id=%d is_wpdiscuz=%d post_keys=[%s]',
			$stage,
			$is_ajax ? 1 : 0,
			$wpdiscuz_version ? 1 : 0,
			$wpdiscuz_core ? 1 : 0,
			$action,
			$request_uri,
			$has_nonce_free,
			$has_consent_free,
			$has_nonce_pro,
			$has_consent_pro,
			$cdata_post_id,
			$post_post_id,
			$is_wpdiscuz ? 1 : 0,
			implode( ',', $post_keys )
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional, gated by constant.
		error_log( $line );
	}

		      public static function save_comment_meta(int $comment_ID, $comment_approved, array $commentdata) : void {
                if ( ! empty( $commentdata['f152_consent_comment'] ) ) {
                        add_comment_meta($comment_ID, 'f152_consent', 1, true);

                        if ( class_exists('\\F152\\ConsentLog') ) {
                                $post_id = (int) ( $commentdata['comment_post_ID'] ?? 0 );
                                $post_type = $post_id > 0 ? get_post_type($post_id) : '';
                                $is_product = ( $post_type === 'product' );

                                $source_type = $is_product ? 'review' : 'comment';

                                $email = $commentdata['comment_author_email'] ?? '';
                                $full_name = $commentdata['comment_author'] ?? '';
                                $user_id = (int) ( $commentdata['user_id'] ?? 0 );
                                $ip_address = $commentdata['comment_author_IP'] ?? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
                                $user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

                                $consent_text_raw = $is_product
                                        ? get_option('f152_reviews_text', Helpers::default_personal_consent_text())
                                        : get_option('f152_comment_text', Helpers::default_personal_consent_text());
                                $consent_text = Helpers::replace_common_macros($consent_text_raw);

                                $policy_version = get_option('f152_policy_version', '');
                                $policy_url = get_option('f152_link_policy_pd', '');

                                \F152\ConsentLog::log([
                                        'source_type' => $source_type,
                                        'source_id' => $comment_ID,
                                        'email' => $email,
                                        'full_name' => $full_name,
                                        'user_id' => $user_id,
                                        'ip_address' => $ip_address,
                                        'user_agent' => $user_agent,
                                        'consent_text' => $consent_text,
                                        'policy_version' => $policy_version,
                                        'policy_url' => $policy_url,
                                ]);
                        }
                }
        }

	public static function render_checkout_checkbox() : void {
		$html = self::get_classic_checkout_checkbox_html();
		if ( '' === $html ) {
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function get_classic_checkout_checkbox_html() : string {
		if ( ! (bool) get_option( 'f152_checkout_enable', 1 ) ) {
			return '';
		}

		$text_raw = (string) get_option( 'f152_checkout_text', Helpers::default_personal_consent_text() );
		$text     = Helpers::kses_paragraph( Helpers::replace_common_macros( $text_raw ) );
		$nonce    = wp_create_nonce( 'f152_checkout_consent' );

		return '<div class="f152-checkout-consent" data-f152-classic-checkout-consent="1" style="margin:14px 0;">'
			. '<label style="display:flex; gap:8px; align-items:flex-start;">'
			. '<input type="checkbox" name="f152_consent_checkout" value="1" required>'
			. '<span>' . wp_kses_post( $text ) . '</span>'
			. '</label>'
			. '<input type="hidden" name="f152_checkout_consent_nonce" value="' . esc_attr( $nonce ) . '">'
			. '</div>';
	}

	public static function render_register_checkbox() : void {
		if ( ! (bool) get_option('f152_register_enable', 1) ) {
			return;
		}

		$text_raw = (string) get_option('f152_register_text', Helpers::default_personal_consent_text());
		$text     = Helpers::kses_paragraph( Helpers::replace_common_macros( $text_raw ) );

		?>
		<div class="f152-register-consent" style="margin:14px 0;">
			<label style="display:flex; gap:8px; align-items:flex-start;">
				<input type="checkbox" name="f152_consent_register" value="1" required>
				<span><?php echo wp_kses_post( $text ); ?></span>
			</label>
			<input type="hidden" name="f152_register_consent_present" value="1">
			<?php wp_nonce_field( 'f152_register_consent', 'f152_register_consent_nonce' ); ?>
		</div>
		<?php
	}

	public static function validate_register( \WP_Error $errors, $username, $email ) : \WP_Error {

		if ( ! (bool) get_option('f152_register_enable', 1) ) {
			return $errors;
		}

		if ( is_admin() ) {
			return $errors;
		}

		$present_raw = filter_input( INPUT_POST, 'f152_register_consent_present', FILTER_UNSAFE_RAW );
		$present     = is_string( $present_raw ) ? sanitize_text_field( wp_unslash( $present_raw ) ) : '';
		if ( $present !== '1' ) {
			return $errors;
		}

		$nonce_raw = filter_input( INPUT_POST, 'f152_register_consent_nonce', FILTER_UNSAFE_RAW );
		$nonce     = is_string( $nonce_raw ) ? sanitize_text_field( wp_unslash( $nonce_raw ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'f152_register_consent' ) ) {
			$errors->add(
				'f152_register_nonce_failed',
				__( 'Ошибка проверки безопасности. Обновите страницу и повторите попытку.', 'fz-152-rf' )
			);
			return $errors;
		}

		$consent_raw = filter_input( INPUT_POST, 'f152_consent_register', FILTER_UNSAFE_RAW );
		$consent_val = is_string( $consent_raw ) ? sanitize_text_field( wp_unslash( $consent_raw ) ) : '';
		if ( $consent_val !== '1' ) {
			$errors->add(
				'f152_register_consent_required',
				__( 'Для регистрации требуется согласие на обработку персональных данных.', 'fz-152-rf' )
			);
		}

		return $errors;
	}

	public static function save_register_consent( $customer_id, $new_customer_data = [], $password_generated = '' ) : void {

		if ( ! (bool) get_option('f152_register_enable', 1) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		$present_raw = filter_input( INPUT_POST, 'f152_register_consent_present', FILTER_UNSAFE_RAW );
		$present     = is_string( $present_raw ) ? sanitize_text_field( wp_unslash( $present_raw ) ) : '';
		if ( $present !== '1' ) {
			return;
		}

		$nonce_raw = filter_input( INPUT_POST, 'f152_register_consent_nonce', FILTER_UNSAFE_RAW );
		$nonce     = is_string( $nonce_raw ) ? sanitize_text_field( wp_unslash( $nonce_raw ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'f152_register_consent' ) ) {
			return;
		}

		$consent_raw = filter_input( INPUT_POST, 'f152_consent_register', FILTER_UNSAFE_RAW );
		$consent_val = is_string( $consent_raw ) ? sanitize_text_field( wp_unslash( $consent_raw ) ) : '';
		if ( $consent_val !== '1' ) {
			return;
		}

		$user_id = is_numeric( $customer_id ) ? (int) $customer_id : 0;
		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, '_f152_consent', 1 );

		if ( ! class_exists( '\\F152\\ConsentLog' ) ) {
			return;
		}

		$user = get_userdata( $user_id );
		$email     = ( $user && $user->user_email ) ? $user->user_email : '';
		$full_name = $user ? trim( (string) $user->first_name . ' ' . (string) $user->last_name ) : '';
		if ( $full_name === '' && $user && $user->display_name ) {
			$full_name = (string) $user->display_name;
		}

		$page_url = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) && is_scalar( $_SERVER['REQUEST_URI'] ) ) {
			$host = isset( $_SERVER['HTTP_HOST'] ) && is_scalar( $_SERVER['HTTP_HOST'] )
				? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_HOST'] ) )
				: '';
			if ( $host !== '' ) {
				$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $host
					. sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );
			}
		}

		\F152\ConsentLog::log(
			[
				'source_type'    => 'register',
				'source_id'      => $user_id,
				'email'          => $email,
				'full_name'      => $full_name,
				'user_id'        => $user_id,
				'ip_address'     => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
				'user_agent'     => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
				'consent_text'   => Helpers::replace_common_macros( get_option( 'f152_register_text', Helpers::default_personal_consent_text() ) ),
				'policy_version' => get_option( 'f152_policy_version', '' ),
				'policy_url'     => get_option( 'f152_link_policy_pd', '' ),
				'source_plugin'  => 'woocommerce',
				'source_label'   => 'WooCommerce registration',
				'page_url'       => $page_url,
			]
		);
	}

	public static function validate_checkout($data, \WP_Error $errors) : void {
		if ( ! (bool) get_option('f152_checkout_enable', 1) ) {
			return;
		}

			$nonce_raw = filter_input( INPUT_POST, 'f152_checkout_consent_nonce', FILTER_UNSAFE_RAW );
			$nonce_raw = is_string( $nonce_raw ) ? $nonce_raw : '';
			$nonce     = sanitize_text_field( wp_unslash( $nonce_raw ) );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'f152_checkout_consent' ) ) {
			$errors->add(
				'f152_checkout_nonce_failed',
				__( 'Ошибка проверки безопасности. Обновите страницу и повторите попытку.', 'fz-152-rf' )
			);
			self::$checkout_consent_verified = false;
			return;
		}

			$consent_raw = filter_input( INPUT_POST, 'f152_consent_checkout', FILTER_UNSAFE_RAW );
			$consent_raw = is_string( $consent_raw ) ? $consent_raw : '';
			$consent_val = sanitize_text_field( wp_unslash( $consent_raw ) );
			$consent     = ( $consent_val === '1' );

		if ( ! $consent ) {
			$errors->add(
				'f152_consent_missing',
				__( 'Для оформления заказа требуется согласие на обработку персональных данных.', 'fz-152-rf' )
			);
			self::$checkout_consent_verified = false;
			return;
		}

		self::$checkout_consent_verified = true;
	}

	public static function save_checkout_meta(int $order_id) : void {
		if ( ! (bool) get_option('f152_checkout_enable', 1) ) {
			return;
		}
		
		if ( self::$checkout_consent_verified ) {
			update_post_meta( $order_id, '_f152_consent', 1 );

			$order = wc_get_order( $order_id );
			if ( $order ) {
				self::log_order_consent( $order, $order_id );
			}
		}
	}

	public static function register_block_checkout_field() : void {
		$api_available = function_exists( 'woocommerce_register_additional_checkout_field' );

		if ( ! $api_available ) {
			self::log_diagnostic( 'block_checkout_api_not_available' );
			return;
		}

		if ( ! (bool) get_option( 'f152_enabled', 1 ) ) {
			self::log_diagnostic( 'block_checkout_plugin_disabled' );
			return;
		}

		if ( ! (bool) get_option( 'f152_checkout_enable', 1 ) ) {
			self::log_diagnostic( 'block_checkout_feature_disabled' );
			return;
		}

		$label_html = self::get_block_checkout_label_html();
		$label      = wp_strip_all_tags( $label_html );

		if ( '' === trim( $label ) ) {
			$label_html = self::get_block_checkout_label_html( Helpers::default_personal_consent_text() );
			$label      = wp_strip_all_tags( $label_html );
		}

		woocommerce_register_additional_checkout_field(
			[
				'id'            => 'f152/f152-consent-checkout',
				'label'         => $label,
				'location'      => 'order',
				'type'          => 'checkbox',
				'required'      => true,
				'attributes'    => [
					'data-f152-checkout-consent' => '1',
				],
				'error_message' => __( 'Для оформления заказа требуется согласие на обработку персональных данных.', 'fz-152-rf' ),
			]
		);

		self::log_diagnostic( 'block_checkout_field_registered' );
	}


	public static function get_block_checkout_label_html( ?string $raw_text = null ) : string {
		if ( null === $raw_text ) {
			$raw_text = (string) get_option( 'f152_checkout_text', Helpers::default_personal_consent_text() );
		}

		if ( '' === trim( $raw_text ) ) {
			$raw_text = Helpers::default_personal_consent_text();
		}

		$html = Helpers::replace_common_macros( $raw_text );

		return wp_kses(
			$html,
			[
				'a'      => [
					'href'   => true,
					'title'  => true,
					'target' => true,
					'rel'    => true,
				],
				'br'     => [],
				'em'     => [],
				'strong' => [],
				'span'   => [ 'class' => true ],
			]
		);
	}

	public static function sync_block_checkout_consent(
		string $field_key,
		$value,
		string $group,
		$wc_object
	) : void {
		if ( $field_key !== 'f152/f152-consent-checkout' ) {
			return;
		}

		if ( $group !== 'other' ) {
			return;
		}

		if ( ! $wc_object instanceof \WC_Order ) {
			return;
		}

		$consent_given = false;

		if ( is_bool( $value ) ) {
			$consent_given = $value;
		} else {
			$consent_given = in_array( (string) $value, [ '1', 'true', 'yes' ], true );
		}

		if ( ! $consent_given ) {
			return;
		}

		$order_id = (int) $wc_object->get_id();

		update_post_meta( $order_id, '_f152_consent', 1 );

		self::log_order_consent( $wc_object, $order_id );
	}

	private static function log_order_consent( \WC_Order $order, int $order_id ) : void {
		if ( ! class_exists( '\\F152\\ConsentLog' ) ) {
			return;
		}

		$email      = $order->get_billing_email();
		$full_name  = $order->get_formatted_billing_full_name();
		$phone      = '';
		if ( method_exists( $order, 'get_billing_phone' ) ) {
			$phone = (string) $order->get_billing_phone();
		}

		$user_id = 0;
		if ( method_exists( $order, 'get_customer_id' ) ) {
			$user_id = (int) $order->get_customer_id();
		} elseif ( method_exists( $order, 'get_user_id' ) ) {
			$user_id = (int) $order->get_user_id();
		}

		$ip_address = '';
		if ( method_exists( $order, 'get_customer_ip_address' ) ) {
			$ip_address = $order->get_customer_ip_address();
		}
		if ( empty( $ip_address ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		}

		$user_agent = '';
		if ( method_exists( $order, 'get_customer_user_agent' ) ) {
			$user_agent = $order->get_customer_user_agent();
		}
		if ( empty( $user_agent ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		}

		$consent_text_raw = get_option( 'f152_checkout_text', Helpers::default_personal_consent_text() );
		$consent_text     = Helpers::replace_common_macros( $consent_text_raw );
		$policy_version   = get_option( 'f152_policy_version', '' );
		$policy_url       = get_option( 'f152_link_policy_pd', '' );

		\F152\ConsentLog::log(
			[
				'source_type'    => 'order',
				'source_id'      => $order_id,
				'email'          => $email,
				'full_name'      => $full_name,
				'phone'          => $phone,
				'user_id'        => $user_id,
				'ip_address'     => $ip_address,
				'user_agent'     => $user_agent,
				'consent_text'   => $consent_text,
				'policy_version' => $policy_version,
				'policy_url'     => $policy_url,
			]
		);
	}

	private static function log_diagnostic( string $event ) : void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		$logger = wc_get_logger();
		$logger->debug(
			sprintf( 'FZ-152 block checkout: %s (woocommerce_init fired at %s)', $event, current_time( 'mysql' ) ),
			[ 'source' => 'fz152' ]
		);
	}
}