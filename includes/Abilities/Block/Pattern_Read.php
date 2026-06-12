<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Pattern_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-pattern-read',
			'args' => array(
				'label'               => __( 'Read Theme Block Pattern', 'acrossai-core-abilities' ),
				'description'         => __( 'Reads a block-pattern file from a theme\'s /patterns directory and returns its parsed headers plus the raw block markup.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_theme_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme_slug' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Theme folder name. Defaults to the active theme.', 'acrossai-core-abilities' ),
						),
						'filename'   => array(
							'type'        => 'string',
							'description' => __( 'Pattern filename (e.g. "hero.php"). The .php extension is added if missing.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'filename' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'path'     => array( 'type' => 'string' ),
						'headers'  => array( 'type' => 'object' ),
						'body'     => array( 'type' => 'string' ),
						'contents' => array( 'type' => 'string' ),
						'message'  => array( 'type' => 'string' ),
					),
					'required'   => array( 'success' ),
					'additionalProperties' => false,
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
		$slug      = sanitize_text_field( $input['theme_slug'] ?? '' );
		$filename  = sanitize_text_field( $input['filename'] ?? '' );
		$theme_dir = Pattern_Helper::resolve_theme_dir( $slug );

		if ( is_wp_error( $theme_dir ) ) {
			return array( 'success' => false, 'message' => $theme_dir->get_error_message() );
		}

		$abs_path = Pattern_Helper::resolve_pattern_path( $theme_dir, $filename );
		if ( is_wp_error( $abs_path ) ) {
			return array( 'success' => false, 'message' => $abs_path->get_error_message() );
		}

		if ( ! is_file( $abs_path ) ) {
			return array( 'success' => false, 'message' => __( 'Pattern file not found.', 'acrossai-core-abilities' ) );
		}

		$contents = file_get_contents( $abs_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents ) {
			return array( 'success' => false, 'message' => __( 'Could not read pattern file.', 'acrossai-core-abilities' ) );
		}

		$parsed = Pattern_Helper::parse_file( $contents );

		return array(
			'success'  => true,
			'path'     => $abs_path,
			'headers'  => $parsed['headers'],
			'body'     => $parsed['body'],
			'contents' => $contents,
		);
	}
}
