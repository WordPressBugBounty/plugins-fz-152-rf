<?php
namespace F152;

if ( ! defined('ABSPATH') ) exit;

final class Metrika {

        public static function init() : void {
                add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_script'], 20);
        }

        public static function enqueue_script() : void {
                if ( is_admin() ) {
                        return;
                }

                if ( ! get_option('f152_enabled', 1) ) {
                        return;
                }

                $id_raw   = (string) get_option('f152_ym_counter', '');
                $id_clean = preg_replace('~\D~', '', $id_raw);
                if ( $id_clean === '' ) {
                        return;
                }

                wp_enqueue_script(
                        'f152-metrika',
                        F152_URL . 'assets/js/metrika-loader.js',
                        ['f152'],
                        F152_VERSION,
                        true
                );
        }
}
