<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Pattern_Delete extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-pattern-delete',
			'args' => array(
				'label'               => __( 'Delete Theme Block Pattern', 'acrossai-core-abilities' ),
				'description'         => __( 'Deletes a block-pattern PHP file from a theme\'s /patterns directory.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_themes' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme_slug' => array(
							'type'    => 'string',
							'default' => '',
						),
						'filename'   => array(
							'type'        => 'string',
							'description' => __( 'Pattern filename to delete.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'filename' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'path'    => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$theme_slug = sanitize_text_field( $input['theme_slug'] ?? '' );
		$theme_dir  = Pattern_Helper::resolve_theme_dir( $theme_slug );

		if ( is_wp_error( $theme_dir ) ) {
			return array( 'success' => false, 'message' => $theme_dir->get_error_message() );
		}

		$abs_path = Pattern_Helper::resolve_pattern_path( $theme_dir, sanitize_text_field( $input['filename'] ?? '' ) );
		if ( is_wp_error( $abs_path ) ) {
			return array( 'success' => false, 'message' => $abs_path->get_error_message() );
		}

		if ( ! is_file( $abs_path ) ) {
			return array( 'success' => false, 'message' => __( 'Pattern file not found.', 'acrossai-core-abilities' ) );
		}

		if ( ! unlink( $abs_path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			return array( 'success' => false, 'message' => __( 'Could not delete pattern file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $abs_path,
			'message' => __( 'Pattern deleted.', 'acrossai-core-abilities' ),
		);
	}
}
