<?php
/**
 * Шаблон попапа «Настройка файлов cookie».
 *
 * По умолчанию класс Banner сам выводит попап через wp_footer.
 * Этот файл — запасной шаблон на случай ручного include в теме.
 */

if ( ! defined('ABSPATH') ) exit;

if ( class_exists('\\F152\\Banner') ) {
	\F152\Banner::render_popup();
}
