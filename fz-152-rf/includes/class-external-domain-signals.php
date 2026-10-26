<?php
namespace F152;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ExternalDomainSignals {

	public static function extract( string $html, string $page_url, string $page_title ): array {
		if ( '' === trim( $html ) ) return [];
		$signals = [];

		if ( class_exists( '\DOMDocument' ) ) {
			$dom = new \DOMDocument();
			$old = libxml_use_internal_errors( true );
			$loaded = $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
			libxml_clear_errors();
			libxml_use_internal_errors( $old );
			if ( $loaded ) {
				$selectors = [
					'script' => [ 'src' ],
					'iframe' => [ 'src' ],
					'img'    => [ 'src', 'srcset' ],
					'link'   => [ 'href' ],
					'embed'  => [ 'src' ],
					'object' => [ 'data' ],
					'form'   => [ 'action' ],
					'source' => [ 'src', 'srcset' ],
					'video'  => [ 'poster' ],
				];
				foreach ( $selectors as $tag => $attributes ) {
					foreach ( $dom->getElementsByTagName( $tag ) as $element ) {
						if ( ! $element instanceof \DOMElement ) continue;
						 
						if ( 'link' === $tag && preg_match( '/(?:^|\s)profile(?:\s|$)/i', $element->getAttribute( 'rel' ) ) ) continue;
						foreach ( $attributes as $attribute ) {
							if ( ! $element->hasAttribute( $attribute ) ) continue;
							foreach ( self::attribute_urls( trim( $element->getAttribute( $attribute ) ), 'srcset' === $attribute ) as $resource_url ) {
								self::append( $signals, $resource_url, $tag, $page_url, $page_title );
							}
						}
					}
				}
				return $signals;
			}
		}

		if ( preg_match_all( '~<(script|iframe|img|link|embed|object|form|source|video)\b[^>]*>~is', $html, $tags, PREG_SET_ORDER ) ) {
			foreach ( $tags as $match ) {
				$tag = strtolower( (string) $match[1] );
				$attributes = 'object' === $tag ? [ 'data' ] : ( 'form' === $tag ? [ 'action' ] : ( 'link' === $tag ? [ 'href' ] : ( 'video' === $tag ? [ 'poster' ] : [ 'src', 'srcset' ] ) ) );
				foreach ( $attributes as $attribute ) {
					if ( ! preg_match( '~\b' . preg_quote( $attribute, '~' ) . '\s*=\s*(["\'])(.*?)\1~is', (string) $match[0], $attr_match ) ) continue;
					foreach ( self::attribute_urls( (string) $attr_match[2], 'srcset' === $attribute ) as $resource_url ) {
						self::append( $signals, $resource_url, $tag, $page_url, $page_title );
					}
				}
			}
		}
		return $signals;
	}

	public static function merge( array $existing, array $incoming ): array {
		foreach ( $incoming as $host => $signal ) {
			if ( ! is_array( $signal ) ) continue;
			$host = self::normalize_host( (string) $host );
			if ( '' === $host ) continue;
			if ( ! isset( $existing[ $host ] ) || ! is_array( $existing[ $host ] ) ) {
				$existing[ $host ] = $signal;
				continue;
			}
			$current = $existing[ $host ];
			$current['resource_types'] = array_values( array_unique( array_merge( (array) ( $current['resource_types'] ?? [] ), (array) ( $signal['resource_types'] ?? [] ) ) ) );
			$current['service_ids'] = array_values( array_unique( array_merge( (array) ( $current['service_ids'] ?? [] ), (array) ( $signal['service_ids'] ?? [] ) ) ) );
			$current['service_labels'] = array_values( array_unique( array_merge( (array) ( $current['service_labels'] ?? [] ), (array) ( $signal['service_labels'] ?? [] ) ) ) );
			$current['known'] = ! empty( $current['service_ids'] );
			$current['resources'] = array_slice( array_values( array_unique( array_merge( (array) ( $current['resources'] ?? [] ), (array) ( $signal['resources'] ?? [] ) ) ) ), 0, 8 );
			$locations = [];
			foreach ( array_merge( (array) ( $current['locations'] ?? [] ), (array) ( $signal['locations'] ?? [] ) ) as $location ) {
				if ( ! is_array( $location ) ) continue;
				$key = (string) ( $location['url'] ?? '' );
				if ( '' === $key ) $key = (string) ( $location['title'] ?? '' );
				if ( '' !== $key ) $locations[ $key ] = $location;
			}
			$current['locations'] = array_slice( array_values( $locations ), 0, 12 );
			$current['occurrences'] = max( 1, (int) ( $current['occurrences'] ?? 1 ) ) + max( 1, (int) ( $signal['occurrences'] ?? 1 ) );
			$existing[ $host ] = $current;
		}
		ksort( $existing );
		return count( $existing ) > 250 ? array_slice( $existing, 0, 250, true ) : $existing;
	}

	private static function append( array &$signals, string $resource_url, string $resource_type, string $page_url, string $page_title ): void {
		$resource_url = html_entity_decode( trim( $resource_url ), ENT_QUOTES, 'UTF-8' );
		if ( '' === $resource_url || 0 === strpos( $resource_url, '#' ) ) return;
		if ( 0 === strpos( $resource_url, '//' ) ) $resource_url = ( is_ssl() ? 'https:' : 'http:' ) . $resource_url;
		if ( ! preg_match( '~^https?://~i', $resource_url ) ) return;

		$host = self::normalize_host( (string) wp_parse_url( $resource_url, PHP_URL_HOST ) );
		if ( '' === $host || self::is_internal_host( $host ) ) return;
		 
		if ( 'link' === $resource_type && 'gmpg.org' === $host && preg_match( '~^https?://gmpg\.org/xfn/(?:11|1)(?:[/?#]|$)~i', $resource_url ) ) return;

		$known = self::known_services( $resource_url );
		$signal = [
			'host' => $host,
			'known' => ! empty( $known['ids'] ),
			'service_ids' => $known['ids'],
			'service_labels' => $known['labels'],
			'resource_types' => [ sanitize_key( $resource_type ) ],
			'resources' => [ esc_url_raw( $resource_url ) ],
			'locations' => [ [ 'title' => sanitize_text_field( $page_title ), 'url' => esc_url_raw( strtok( $page_url, '?' ) ) ] ],
			'occurrences' => 1,
		];
		$signals = self::merge( $signals, [ $host => $signal ] );
	}

	private static function attribute_urls( string $value, bool $srcset ): array {
		if ( '' === $value ) return [];
		if ( ! $srcset ) return [ $value ];
		$urls = [];
		foreach ( explode( ',', $value ) as $candidate ) {
			$parts = preg_split( '/\s+/', trim( $candidate ) );
			if ( is_array( $parts ) && ! empty( $parts[0] ) ) $urls[] = (string) $parts[0];
		}
		return $urls;
	}

	private static function normalize_host( string $host ): string {
		$host = strtolower( trim( $host ) );
		$host = preg_replace( '/:\d+$/', '', $host );
		if ( 0 === strpos( $host, 'www.' ) ) $host = substr( $host, 4 );
		return rtrim( (string) $host, '.' );
	}

	private static function is_internal_host( string $host ): bool {
		$home_host = self::normalize_host( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( '' === $home_host ) return false;
		if ( $host === $home_host ) return true;
		if ( strlen( $host ) > strlen( $home_host ) && substr( $host, -1 - strlen( $home_host ) ) === '.' . $home_host ) return true;
		if ( strlen( $home_host ) > strlen( $host ) && substr( $home_host, -1 - strlen( $host ) ) === '.' . $host ) return true;
		return false;
	}

	private static function known_services( string $resource_url ): array {
		$ids = [];
		$labels = [];
		if ( ! class_exists( '\F152\ServiceCatalog' ) ) return [ 'ids' => [], 'labels' => [] ];
		foreach ( ServiceCatalog::definitions() as $service_id => $definition ) {
			$patterns = array_merge( (array) ( $definition['patterns'] ?? [] ), (array) ( $definition['candidate_patterns'] ?? [] ) );
			foreach ( $patterns as $pattern ) {
				if ( 1 === @preg_match( $pattern, $resource_url ) ) {
					$ids[] = sanitize_key( (string) $service_id );
					$full = ServiceCatalog::get( (string) $service_id );
					$labels[] = sanitize_text_field( (string) ( $full['label'] ?? $service_id ) );
					break;
				}
			}
		}
		return [ 'ids' => array_values( array_unique( array_filter( $ids ) ) ), 'labels' => array_values( array_unique( array_filter( $labels ) ) ) ];
	}
}
