<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a single block pattern from any storage layer. Resolves source
 * automatically when there is one obvious location, or returns a
 * multiple_locations error with the list when the caller hasn't picked one.
 */
class Pattern_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-pattern-read',
			'args' => array(
				'label'               => __( 'Read Block Pattern', 'acrossai-core-abilities' ),
				'description'         => __( 'Reads a pattern by slug from one of: db (wp_block CPT), theme /patterns folder, or plugin /patterns folder. Omit "source" to auto-detect — if the slug exists in more than one location the call fails with error_code=multiple_locations and the list of locations.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_theme_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'slug'        => array(
							'type'        => 'string',
							'description' => __( 'Bare pattern slug (the post_name for DB; the filename minus .php for files).', 'acrossai-core-abilities' ),
						),
						'source'      => array(
							'type'    => 'string',
							'enum'    => array( 'db', 'theme', 'plugin' ),
						),
						'theme_type'  => array(
							'type'    => 'string',
							'enum'    => array( 'child', 'parent', 'theme' ),
						),
						'plugin_slug' => array(
							'type'    => 'string',
						),
					),
					'required'             => array( 'slug' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'message'    => array( 'type' => 'string' ),
						'error_code' => array( 'type' => 'string' ),
						'locations'  => array( 'type' => 'array' ),
						'pattern'    => array( 'type' => 'object' ),
					),
					'required'   => array( 'success' ),
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$slug        = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$source      = sanitize_text_field( $input['source'] ?? '' );
		$theme_type  = sanitize_text_field( $input['theme_type'] ?? '' );
		$plugin_slug = sanitize_key( $input['plugin_slug'] ?? '' );

		if ( '' === $slug ) {
			return array( 'success' => false, 'message' => __( 'slug is required.', 'acrossai-core-abilities' ), 'error_code' => 'invalid_slug' );
		}

		$locations = Pattern_Detector::locate( $slug );
		$selected  = Pattern_Detector::select( $locations, $source, $theme_type, $plugin_slug );

		if ( is_wp_error( $selected ) ) {
			$data = $selected->get_error_data();
			return array(
				'success'    => false,
				'message'    => $selected->get_error_message(),
				'error_code' => $selected->get_error_code(),
				'locations'  => isset( $data['locations'] ) ? $data['locations'] : $locations,
			);
		}

		if ( 'db' === $selected['source'] ) {
			$post = get_post( (int) $selected['post_id'] );
			if ( ! $post ) {
				return array( 'success' => false, 'message' => __( 'Pattern post disappeared.', 'acrossai-core-abilities' ), 'error_code' => 'not_found' );
			}
			return array(
				'success' => true,
				'pattern' => Pattern_Db::to_row( $post ),
			);
		}

		// File-based (theme or plugin)
		$contents = file_get_contents( $selected['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents ) {
			return array( 'success' => false, 'message' => __( 'Could not read pattern file.', 'acrossai-core-abilities' ), 'error_code' => 'read_failed' );
		}
		$parsed = Pattern_Helper::parse_file( $contents );

		$pattern = array_merge(
			$selected,
			array(
				'headers' => $parsed['headers'],
				'body'    => $parsed['body'],
			)
		);

		return array(
			'success' => true,
			'pattern' => $pattern,
		);
	}
}
