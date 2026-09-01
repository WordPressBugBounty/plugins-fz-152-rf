<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ServiceInventory {

	private static $theme_code_matches = null;

	public static function get(): array {
		$catalog = ServiceCatalog::all();
		$scan = class_exists( '\\F152\\ServiceScanner' ) && is_callable( [ '\\F152\\ServiceScanner', 'get_scan_snapshot' ] )
			? ServiceScanner::get_scan_snapshot()
			: [];
		$scan_services = isset( $scan['services'] ) && is_array( $scan['services'] ) ? $scan['services'] : [];
		$control = class_exists( '\\F152\\ServiceScanner' ) && is_callable( [ '\\F152\\ServiceScanner', 'get_control_snapshot' ] )
			? ServiceScanner::get_control_snapshot()
			: [];
		$plugins = self::plugin_matches( $catalog );
		$theme_code = self::theme_code_matches( $catalog );
		$result = [];

		foreach ( $catalog as $service_id => $definition ) {
			$detection = isset( $scan_services[ $service_id ] ) && is_array( $scan_services[ $service_id ] ) ? $scan_services[ $service_id ] : [];
			$status = isset( $control[ $service_id ] ) && is_array( $control[ $service_id ] ) ? $control[ $service_id ] : [];
			$plugin = $plugins[ $service_id ] ?? [ 'active' => [], 'inactive' => [] ];
			$code_matches = isset( $theme_code[ $service_id ] ) && is_array( $theme_code[ $service_id ] ) ? $theme_code[ $service_id ] : [];
			$external_frontend = ! empty( $detection['found_external'] );
			$managed_frontend  = ! empty( $detection['found_managed'] );
			$frontend          = $external_frontend || $managed_frontend;
			$configured = ! empty( $status['configured'] );
			$configuration_evidence = array_key_exists( 'inventory_evidence', $status )
				? ! empty( $status['inventory_evidence'] )
				: $configured;
			$active_control = ! empty( $status['active'] );
			$confirmed = $frontend || ( $configuration_evidence && $active_control );
			if ( 'yandex_maps' === sanitize_key( (string) $service_id ) && $confirmed && ! get_option( 'f152_yandex_maps_policy_confirmed', 0 ) ) {
				update_option( 'f152_yandex_maps_policy_confirmed', 1, false );
			}
			$active_plugins = isset( $plugin['active'] ) && is_array( $plugin['active'] ) ? $plugin['active'] : [];
			$inactive_plugins = isset( $plugin['inactive'] ) && is_array( $plugin['inactive'] ) ? $plugin['inactive'] : [];

			if ( ! $frontend && ! $configuration_evidence && empty( $active_plugins ) && empty( $inactive_plugins ) && empty( $code_matches ) ) {
				continue;
			}

			$external_locations = isset( $detection['locations'] ) && is_array( $detection['locations'] ) ? $detection['locations'] : [];
			$managed_locations  = isset( $detection['managed_locations'] ) && is_array( $detection['managed_locations'] ) ? $detection['managed_locations'] : [];
			$locations          = self::merge_locations( $external_locations, $managed_locations );

			$result[ $service_id ] = [
				'id'                 => $service_id,
				'label'              => (string) ( $definition['label'] ?? $service_id ),
				'category'           => (string) ( $definition['category'] ?? 'other' ),
				'policy_mode'        => (string) ( $definition['policy_mode'] ?? 'review' ),
				'policy_owner'       => (string) ( $definition['policy_owner'] ?? '' ),
				'frontend'           => $frontend,
				'confirmed'          => $confirmed,
				'external_frontend'  => $external_frontend,
				'managed_frontend'   => $managed_frontend,
				'configured'         => $configuration_evidence,
				'control_configured' => $configured,
				'active_control'     => $active_control,
				'active_plugins'     => $active_plugins,
				'inactive_plugins'   => $inactive_plugins,
				'theme_code_matches' => $code_matches,
				'ids'                => isset( $detection['ids'] ) && is_array( $detection['ids'] ) ? $detection['ids'] : [],
				'locations'          => $locations,
				'external_locations' => $external_locations,
				'managed_locations'  => $managed_locations,
			];
		}

		return $result;
	}

	public static function policy_review_items(): array {
		$items = [];
		foreach ( self::get() as $service_id => $service ) {
			$mode = (string) ( $service['policy_mode'] ?? 'review' );
			if ( 'container_info' === $mode ) {
				continue;
			}
			$confirmed = array_key_exists( 'confirmed', $service )
				? ! empty( $service['confirmed'] )
				: ! empty( $service['frontend'] );
			$is_data_transfer = 'data_transfer' === sanitize_key( (string) ( $service['category'] ?? '' ) );
			$active_plugin_candidate = $is_data_transfer && ! empty( $service['active_plugins'] );
			$theme_code_candidate = $is_data_transfer && ! empty( $service['theme_code_matches'] );
			if ( ! $confirmed && ! $active_plugin_candidate && ! $theme_code_candidate ) {
				continue;
			}
			$reflection_status = class_exists( '\\F152\\PolicyServices' ) && is_callable( [ '\\F152\\PolicyServices', 'reflection_status' ] )
				? PolicyServices::reflection_status( $service_id )
				: ( class_exists( '\\F152\\PolicyServices' ) && PolicyServices::reflected_in_generated_policies( $service_id ) ? 'managed' : 'missing' );
			if ( ! in_array( $reflection_status, [ 'missing', 'unreadable' ], true ) ) {
				continue;
			}
			$service['reflected'] = false;
			$service['reflection_status'] = $reflection_status;
			$service['detection_basis'] = $confirmed ? 'confirmed' : ( $active_plugin_candidate ? 'active_plugin' : 'theme_code' );
			$items[ $service_id ] = $service;
		}
		return $items;
	}

	private static function merge_locations( array $external, array $managed ): array {
		$result = [];
		$seen   = [];
		foreach ( array_merge( $external, $managed ) as $location ) {
			if ( ! is_array( $location ) ) {
				continue;
			}
			$url = isset( $location['url'] ) ? (string) $location['url'] : '';
			$key = '' !== $url ? $url : sanitize_title( (string) ( $location['title'] ?? '' ) );
			if ( '' === $key || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$result[] = $location;
		}
		return $result;
	}

	private static function theme_code_matches( array $catalog ): array {
		if ( null !== self::$theme_code_matches ) {
			return self::$theme_code_matches;
		}
		self::$theme_code_matches = [];
		if ( ! function_exists( 'get_stylesheet_directory' ) || ! function_exists( 'get_template_directory' ) ) {
			return self::$theme_code_matches;
		}

		$definitions = [];
		foreach ( $catalog as $service_id => $definition ) {
			if ( 'data_transfer' !== sanitize_key( (string) ( $definition['category'] ?? '' ) ) ) {
				continue;
			}
			$patterns = array_merge(
				isset( $definition['patterns'] ) && is_array( $definition['patterns'] ) ? $definition['patterns'] : [],
				isset( $definition['candidate_patterns'] ) && is_array( $definition['candidate_patterns'] ) ? $definition['candidate_patterns'] : []
			);
			if ( ! empty( $patterns ) ) {
				$definitions[ sanitize_key( (string) $service_id ) ] = $patterns;
			}
		}
		if ( empty( $definitions ) ) {
			return self::$theme_code_matches;
		}

		$roots = array_values( array_unique( array_filter( [ get_stylesheet_directory(), get_template_directory() ], 'is_dir' ) ) );
		$checked = 0;
		$max_files = 250;
		$max_size = 524288;
		$allowed_ext = [ 'php', 'js', 'mjs', 'ts', 'html', 'twig' ];

		foreach ( $roots as $root ) {
			try {
				$iterator = new \RecursiveIteratorIterator(
					new \RecursiveCallbackFilterIterator(
						new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
						static function( $current ): bool {
							if ( $current->isDir() ) {
								return ! in_array( strtolower( $current->getFilename() ), [ 'node_modules', 'vendor', '.git', 'cache' ], true );
							}
							return true;
						}
					)
				);
			} catch ( \UnexpectedValueException $e ) {
				continue;
			}

			foreach ( $iterator as $file ) {
				if ( $checked >= $max_files ) {
					break 2;
				}
				if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
					continue;
				}
				$ext = strtolower( (string) $file->getExtension() );
				if ( ! in_array( $ext, $allowed_ext, true ) || $file->getSize() <= 0 || $file->getSize() > $max_size ) {
					continue;
				}
				$checked++;
				$path = $file->getPathname();
				$source = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local active-theme source inspection only.
				if ( ! is_string( $source ) || '' === $source ) {
					continue;
				}
				foreach ( $definitions as $service_id => $patterns ) {
					foreach ( $patterns as $pattern ) {
						if ( 1 !== preg_match( $pattern, $source ) ) {
							continue;
						}
						$relative = ltrim( str_replace( wp_normalize_path( $root ), '', wp_normalize_path( $path ) ), '/' );
						self::$theme_code_matches[ $service_id ][] = sanitize_text_field( $relative ?: basename( $path ) );
						self::$theme_code_matches[ $service_id ] = array_slice( array_values( array_unique( self::$theme_code_matches[ $service_id ] ) ), 0, 5 );
						break;
					}
				}
			}
		}

		return self::$theme_code_matches;
	}

	private static function plugin_matches( array $catalog ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			return [];
		}
		$installed = get_plugins();
		$active = (array) get_option( 'active_plugins', [] );
		$result = [];

		foreach ( $catalog as $service_id => $definition ) {
			$keywords = isset( $definition['plugin_keywords'] ) && is_array( $definition['plugin_keywords'] ) ? $definition['plugin_keywords'] : [];
			if ( empty( $keywords ) ) {
				continue;
			}
			foreach ( $installed as $plugin_file => $plugin_data ) {
				$haystack = strtolower( $plugin_file . ' ' . (string) ( $plugin_data['Name'] ?? '' ) . ' ' . (string) ( $plugin_data['TextDomain'] ?? '' ) );
				$matched = false;
				foreach ( $keywords as $keyword ) {
					$keyword = strtolower( trim( (string) $keyword ) );
					if ( '' !== $keyword && false !== strpos( $haystack, $keyword ) ) {
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					continue;
				}
				$entry = [
					'file' => sanitize_text_field( (string) $plugin_file ),
					'name' => sanitize_text_field( (string) ( $plugin_data['Name'] ?? $plugin_file ) ),
				];
				$bucket = in_array( $plugin_file, $active, true ) || ( is_multisite() && is_plugin_active_for_network( $plugin_file ) ) ? 'active' : 'inactive';
				$result[ $service_id ][ $bucket ][] = $entry;
			}
		}
		return $result;
	}
}
