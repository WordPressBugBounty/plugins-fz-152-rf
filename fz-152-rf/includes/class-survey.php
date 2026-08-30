<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Survey {
	private const STATUS_OPTION = 'f152_survey_status_v1';
	private const ENDPOINT      = 'https://kotikblog.ru/wp-json/ls/v1/f152-survey';

	public static function init() : void {
		add_action( 'admin_post_f152_submit_survey', [ __CLASS__, 'handle_submit' ] );
		add_action( 'admin_post_f152_skip_survey', [ __CLASS__, 'handle_skip' ] );
	}

	private static function settings_url( string $result = '' ) : string {
		$url = admin_url( 'options-general.php?page=f152' );
		if ( '' !== $result ) {
			$url = add_query_arg( 'f152_survey', $result, $url );
		}
		return $url;
	}

	private static function partner_url() : string {
		return (string) apply_filters(
			'f152_partner_program_url',
			'https://kotikblog.ru/fz152-partner/?utm_source=free_plugin&utm_medium=survey&utm_campaign=partner_program'
		);
	}

	private static function should_show() : bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( defined( 'F152_PRO_VERSION' ) ) {
			return false;
		}

		$status = (string) get_option( self::STATUS_OPTION, '' );
		return ! in_array( $status, [ 'completed', 'skipped' ], true );
	}

	public static function render() : void {
		$result_raw = filter_input( INPUT_GET, 'f152_survey', FILTER_UNSAFE_RAW );
		$result     = is_string( $result_raw ) ? sanitize_key( wp_unslash( $result_raw ) ) : '';

		if ( 'thanks' === $result ) {
			echo '<div class="notice notice-success is-dismissible f152-survey-notice"><p>'
				. esc_html__( 'Спасибо! Ответ сохранён.', 'fz-152-rf' )
				. '</p></div>';
			return;
		}

		if ( 'thanks_partner' === $result ) {
			$partner_url = self::partner_url();
			?>
			<div class="notice notice-success is-dismissible f152-survey-notice"><p><?php esc_html_e( 'Спасибо! Ответ сохранён.', 'fz-152-rf' ); ?></p></div>
			<section class="f152-partner-card f152-survey-partner-result" aria-labelledby="f152-survey-partner-title">
				<div class="f152-partner-card__icon" aria-hidden="true"><span class="dashicons dashicons-groups"></span></div>
				<div class="f152-partner-card__content">
					<span class="f152-card-eyebrow"><?php esc_html_e( 'Партнёрская программа для вебмастеров и агентств', 'fz-152-rf' ); ?></span>
					<h2 id="f152-survey-partner-title"><?php esc_html_e( 'Подключаете ФЗ-152 Pro клиентам?', 'fz-152-rf' ); ?></h2>
					<p><?php esc_html_e( 'Я сделал партнёрскую программу и готов дать вам скидку на все следующие лицензии, за то, что вы берете на себя его настройку и поддержку для клиентов.', 'fz-152-rf' ); ?></p>
					<p class="f152-partner-card__note"><?php esc_html_e( '*При оформлении лицензии на клиента, она будет закреплена за ним, а не за вами.', 'fz-152-rf' ); ?></p>
					<?php if ( '' !== $partner_url ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( $partner_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Подробнее о партнёрской программе', 'fz-152-rf' ); ?></a>
					<?php endif; ?>
				</div>
			</section>
			<?php
			return;
		}

		if ( 'error' === $result ) {
			echo '<div class="notice notice-error f152-survey-notice"><p>'
				. esc_html__( 'Не удалось отправить ответ. Можно попробовать ещё раз или закрыть опрос.', 'fz-152-rf' )
				. '</p></div>';
		}

		if ( ! self::should_show() ) {
			return;
		}
		?>
		<div class="f152-survey-card" data-f152-survey-card>
			<div class="f152-survey-card__intro">
				<div>
					<h2><?php esc_html_e( 'Помогите понять, кто использует FZ-152', 'fz-152-rf' ); ?></h2>
				</div>
				<span class="f152-survey-card__badge"><?php esc_html_e( '≈ 20 секунд', 'fz-152-rf' ); ?></span>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-f152-survey>
				<input type="hidden" name="action" value="f152_submit_survey">
				<?php wp_nonce_field( 'f152_submit_survey', 'f152_survey_nonce' ); ?>

				<div data-f152-survey-step="role">
					<fieldset class="f152-survey-question">
						<legend><?php esc_html_e( 'В какой роли вы настраиваете FZ-152 на этом сайте?', 'fz-152-rf' ); ?></legend>
						<label><input type="radio" name="role" value="owner" required> <?php esc_html_e( 'Владелец сайта / бизнеса — настраиваю для себя', 'fz-152-rf' ); ?></label>
						<label><input type="radio" name="role" value="employee"> <?php esc_html_e( 'Сотрудник компании — настраиваю сайт работодателя', 'fz-152-rf' ); ?></label>
						<label><input type="radio" name="role" value="webmaster"> <?php esc_html_e( 'Вебмастер / разработчик — настраиваю сайты клиентов', 'fz-152-rf' ); ?></label>
						<label><input type="radio" name="role" value="agency"> <?php esc_html_e( 'Веб-студия / агентство — настраиваем сайты клиентов', 'fz-152-rf' ); ?></label>
					</fieldset>

					<div class="f152-survey-actions">
						<button type="button" class="button button-primary" data-f152-survey-next><?php esc_html_e( 'Далее', 'fz-152-rf' ); ?></button>
					</div>
				</div>

				<div data-f152-survey-step="partner" hidden>
					<fieldset class="f152-survey-question f152-survey-question--partner" data-f152-survey-partner>
						<legend><?php esc_html_e( 'Я сделал партнёрскую программу: для вебмастеров и агентств действует скидка на следующие клиентские лицензии за то, что вы берёте на себя настройку и поддержку. Хотели бы пользоваться такой программой?', 'fz-152-rf' ); ?></legend>
						<div class="f152-survey-inline-options">
							<label><input type="radio" name="partner_interest" value="yes" disabled> <?php esc_html_e( 'Да', 'fz-152-rf' ); ?></label>
							<label><input type="radio" name="partner_interest" value="maybe" disabled> <?php esc_html_e( 'Возможно', 'fz-152-rf' ); ?></label>
							<label><input type="radio" name="partner_interest" value="no" disabled> <?php esc_html_e( 'Нет', 'fz-152-rf' ); ?></label>
						</div>
					</fieldset>

					<div class="f152-survey-actions f152-survey-actions--steps">
						<button type="button" class="button" data-f152-survey-back><?php esc_html_e( 'Назад', 'fz-152-rf' ); ?></button>
						<?php submit_button( __( 'Отправить ответ', 'fz-152-rf' ), 'primary', 'submit', false, [ 'data-f152-survey-submit' => '1' ] ); ?>
					</div>
				</div>

				<p class="f152-survey-privacy">
					<?php esc_html_e( 'Домен сайта, email и данные пользователей не отправляются, опрос анонимный.', 'fz-152-rf' ); ?>
				</p>

				<button type="submit" class="f152-survey-owner-submit" data-f152-survey-owner-submit aria-hidden="true" tabindex="-1"><?php esc_html_e( 'Отправить ответ', 'fz-152-rf' ); ?></button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="f152-survey-skip-form">
				<input type="hidden" name="action" value="f152_skip_survey">
				<?php wp_nonce_field( 'f152_skip_survey', 'f152_survey_skip_nonce' ); ?>
				<button type="submit" class="button-link f152-survey-skip"><?php esc_html_e( 'Не хочу участвовать — больше не показывать', 'fz-152-rf' ); ?></button>
			</form>
		</div>
		<?php
	}

	public static function handle_submit() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'fz-152-rf' ) );
		}

		check_admin_referer( 'f152_submit_survey', 'f152_survey_nonce' );

		$role_raw = filter_input( INPUT_POST, 'role', FILTER_UNSAFE_RAW );
		$role     = is_string( $role_raw ) ? sanitize_key( wp_unslash( $role_raw ) ) : '';
		$roles    = [ 'owner', 'employee', 'webmaster', 'agency' ];
		if ( ! in_array( $role, $roles, true ) ) {
			wp_safe_redirect( self::settings_url( 'error' ) );
			exit;
		}

		$partner_interest = '';
		if ( 'owner' !== $role ) {
			$interest_raw     = filter_input( INPUT_POST, 'partner_interest', FILTER_UNSAFE_RAW );
			$partner_interest = is_string( $interest_raw ) ? sanitize_key( wp_unslash( $interest_raw ) ) : '';
			if ( ! in_array( $partner_interest, [ 'yes', 'maybe', 'no' ], true ) ) {
				wp_safe_redirect( self::settings_url( 'error' ) );
				exit;
			}
		}

		$payload = [
			'role'             => $role,
			'partner_interest' => $partner_interest,
			'plugin_version'   => defined( 'F152_VERSION' ) ? (string) F152_VERSION : '',
		];

		$response = wp_remote_post(
			self::ENDPOINT,
			[
				'timeout'     => 8,
				'redirection' => 0,
				'headers'     => [
					'Content-Type' => 'application/json; charset=utf-8',
					'Accept'       => 'application/json',
					'User-Agent'   => 'FZ-152-RF/' . ( defined( 'F152_VERSION' ) ? F152_VERSION : 'unknown' ),
				],
				'body'        => wp_json_encode( $payload ),
				'data_format' => 'body',
			],
		);

		if ( is_wp_error( $response ) ) {
			wp_safe_redirect( self::settings_url( 'error' ) );
			exit;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) || empty( $body['ok'] ) ) {
			wp_safe_redirect( self::settings_url( 'error' ) );
			exit;
		}

		update_option( self::STATUS_OPTION, 'completed', false );
		$result = in_array( $partner_interest, [ 'yes', 'maybe' ], true ) ? 'thanks_partner' : 'thanks';
		wp_safe_redirect( self::settings_url( $result ) );
		exit;
	}

	public static function handle_skip() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'fz-152-rf' ) );
		}

		check_admin_referer( 'f152_skip_survey', 'f152_survey_skip_nonce' );
		update_option( self::STATUS_OPTION, 'skipped', false );
		wp_safe_redirect( self::settings_url() );
		exit;
	}
}
