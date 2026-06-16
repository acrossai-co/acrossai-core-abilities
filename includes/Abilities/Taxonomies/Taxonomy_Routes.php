<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Tiny helper that resolves a taxonomy slug to its REST base path segment.
 * Returns a WP_Error if the taxonomy is missing or not REST-exposed, so the
 * caller can surface a clean structured error instead of a 404.
 */
final class Taxonomy_Routes {

	/**
	 * @return string|\WP_Error
	 */
	public static function rest_base( string $taxonomy ) {
		if ( '' === $taxonomy ) {
			return new \WP_Error( 'invalid_taxonomy', __( 'taxonomy is required.', 'acrossai-core-abilities' ) );
		}

		$obj = get_taxonomy( $taxonomy );
		if ( ! $obj ) {
			return new \WP_Error(
				'unknown_taxonomy',
				/* translators: %s: taxonomy slug */
				sprintf( __( 'Unknown taxonomy "%s".', 'acrossai-core-abilities' ), $taxonomy )
			);
		}
		if ( empty( $obj->show_in_rest ) ) {
			return new \WP_Error(
				'taxonomy_not_in_rest',
				/* translators: %s: taxonomy slug */
				sprintf( __( 'Taxonomy "%s" is not exposed via REST (show_in_rest is false).', 'acrossai-core-abilities' ), $taxonomy )
			);
		}

		$base = ! empty( $obj->rest_base ) ? $obj->rest_base : $taxonomy;
		return (string) $base;
	}
}
