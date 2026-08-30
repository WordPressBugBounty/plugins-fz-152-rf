<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Banner {

	private static function allowed_actions_html() : array {
		return [
			'button' => [
				'type'             => true,
				'class'            => true,
				'aria-label'       => true,
				'data-f152-open'   => true,
				'data-f152-accept' => true,
				'data-f152-reject' => true,
				'data-f152-save'   => true,
				'data-f152-close'  => true,
			],
			'a' => [
				'href'             => true,
				'class'            => true,
				'role'             => true,
				'tabindex'         => true,
				'aria-label'       => true,
				'data-f152-open'   => true,
			],
		];
	}

	public static function render_banner() : void {
		if ( ! get_option( 'f152_enabled', 1 ) ) {
			return;
		}

		$raw = (string) get_option(
			'f152_banner_text',
			Helpers::default_banner_text()
		);

		$parts   = Helpers::split_banner_content( $raw );
		$message = Helpers::kses_paragraph( $parts['message'] );
		$actions = $parts['actions'];
		$banner_classes = apply_filters( 'f152/banner_classes', [ 'f152-banner' ] );
		$banner_classes = is_array( $banner_classes ) ? $banner_classes : [ 'f152-banner' ];

		$banner_classes = array_filter( array_map( 'sanitize_html_class', array_map( 'strval', $banner_classes ) ) );
		$banner_classes = array_values( array_unique( $banner_classes ) );

		if ( ! in_array( 'f152-banner', $banner_classes, true ) ) {
			array_unshift( $banner_classes, 'f152-banner' );
		}

		$banner_class_attr = implode( ' ', $banner_classes );

		?>
		<div class="<?php echo esc_attr( $banner_class_attr ); ?>" role="dialog" aria-live="polite" data-f152-banner hidden>
			<div class="f152-banner__inner">
				<div class="f152-banner__layout">
					<div class="f152-banner__message">
						<?php echo wp_kses_post( $message ); ?>
					</div>
					<?php if ( $actions !== '' ) : ?>
						<div class="f152-banner__actions">
							<?php echo wp_kses( $actions, self::allowed_actions_html() ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_popup() : void {
		if ( ! get_option( 'f152_enabled', 1 ) ) {
			return;
		}

		$upper = (string) get_option( 'f152_popup_upper', '' );
		$f_txt = (string) get_option( 'f152_popup_func', '' );
		$a_txt = (string) get_option( 'f152_popup_anal', '' );
		$m_txt = (string) get_option( 'f152_popup_mark', '' );

		if ( $upper === '' ) {
			$upper = \F152\Templates::default_for_option( 'f152_popup_upper' );
		}
		if ( $f_txt === '' ) {
			$f_txt = \F152\Templates::default_for_option( 'f152_popup_func' );
		}
		if ( $a_txt === '' ) {
			$a_txt = \F152\Templates::default_for_option( 'f152_popup_anal' );
		}
		if ( $m_txt === '' ) {
			$m_txt = \F152\Templates::default_for_option( 'f152_popup_mark' );
		}

		$upper = Helpers::kses_paragraph( Helpers::replace_common_macros( $upper ) );
		$f_txt = Helpers::kses_paragraph( Helpers::replace_common_macros( $f_txt ) );
		$a_txt = Helpers::kses_paragraph( Helpers::replace_common_macros( $a_txt ) );
		$m_txt = Helpers::kses_paragraph( Helpers::replace_common_macros( $m_txt ) );

		$chk = ' checked';

		?>
		<div class="f152-popup" data-f152-popup hidden aria-hidden="true" role="dialog" aria-modal="true">
			<div class="f152-popup__overlay" data-f152-close></div>

			<div class="f152-popup__window" role="document" tabindex="-1">
				<button type="button" class="f152-popup__close" aria-label="<?php echo esc_attr__( 'Закрыть', 'fz-152-rf' ); ?>" data-f152-close>×</button>

				<h3 class="f152-popup__title"><?php echo esc_html__( 'Настройка файлов cookie', 'fz-152-rf' ); ?></h3>

				<div class="f152-popup__content">
					<div class="f152-popup__intro">
						<?php echo wp_kses_post( $upper ); ?>
					</div>

					<div class="f152-popup__group">
						<label class="f152-row">
							<input type="checkbox" checked disabled>
							<span class="f152-row__title"><strong><?php echo esc_html__( 'Функциональные/технические файлы cookie', 'fz-152-rf' ); ?></strong></span>
							<small class="f152-row__desc"><?php echo wp_kses_post( $f_txt ); ?></small>
						</label>
					</div>

					<div class="f152-popup__group">
						<label class="f152-row">
							<input type="checkbox" data-f152-cat="analytics"<?php echo esc_attr( $chk ); ?>>
							<span class="f152-row__title"><strong><?php echo esc_html__( 'Аналитические файлы cookie', 'fz-152-rf' ); ?></strong></span>
							<small class="f152-row__desc"><?php echo wp_kses_post( $a_txt ); ?></small>
						</label>
					</div>

					<div class="f152-popup__group">
						<label class="f152-row">
							<input type="checkbox" data-f152-cat="marketing"<?php echo esc_attr( $chk ); ?>>
							<span class="f152-row__title"><strong><?php echo esc_html__( 'Рекламные/маркетинговые файлы cookie', 'fz-152-rf' ); ?></strong></span>
							<small class="f152-row__desc"><?php echo wp_kses_post( $m_txt ); ?></small>
						</label>
					</div>
				</div>

				<div class="f152-popup__actions">
					<?php echo wp_kses( Helpers::button_html( 'accept' ), self::allowed_actions_html() ); ?>
					<?php echo wp_kses( Helpers::button_html( 'reject' ), self::allowed_actions_html() ); ?>
					<button type="button" class="f152-btn f152-btn--save" data-f152-save><?php echo esc_html__( 'Сохранить', 'fz-152-rf' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}