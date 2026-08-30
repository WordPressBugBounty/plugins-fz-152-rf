<?php
/**
 * Шаблон баннера ФЗ-152.
 *
 * По умолчанию класс Banner сам выводит баннер через wp_footer.
 * Этот файл — запасной шаблон на случай ручного include в теме.
 *
 * Пример использования в теме:
 *   if ( function_exists('\\F152\\Banner::render_banner') ) { \F152\Banner::render_banner(); }
 */

if ( ! defined('ABSPATH') ) exit;

if ( class_exists('\\F152\\Banner') ) {
	\F152\Banner::render_banner();
}
