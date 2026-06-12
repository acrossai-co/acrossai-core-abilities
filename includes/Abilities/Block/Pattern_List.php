<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Pattern_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-pattern-list',
			'args' => array(
				'label'               => __( 'List Theme Block Patterns', 'acrossai-core-abilities' ),
				'description'         => __( 'Lists all block-pattern PHP files inside a theme\'s /patterns directory along with their parsed headers (Title, Slug, Categories, etc.). Defaults to the active theme.', 'acrossai-core-abilities' ),
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
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'theme'    => array( 'type' => 'string' ),
						'path'     => array( 'type' => 'string' ),
						'patterns' => array( 'type' => 'array' ),
						'total'    => array( 'type' => 'integer' ),
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
		$theme_dir = Pattern_Helper::resolve_theme_dir( $slug );

		if ( is_wp_error( $theme_dir ) ) {
			return array( 'success' => false, 'message' => $theme_dir->get_error_message() );
		}

		$patterns_dir = $theme_dir . '/patterns';

		if ( ! is_dir( $patterns_dir ) ) {
			return array(
				'success'  => true,
				'theme'    => basename( $theme_dir ),
				'path'     => $patterns_dir,
				'patterns' => array(),
				'total'    => 0,
				'message'  => __( 'No /patterns directory in this theme.', 'acrossai-core-abilities' ),
			);
		}

		$results = array();
		foreach ( glob( $patterns_dir . '/*.php' ) ?: array() as $file ) {
			$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $contents ) {
				continue;
			}
			$parsed    = Pattern_Helper::parse_file( $contents );
			$results[] = array(
				'filename' => basename( $file ),
				'path'     => $file,
				'headers'  => $parsed['headers'],
			);
		}

		return array(
			'success'  => true,
			'theme'    => basename( $theme_dir ),
			'path'     => $patterns_dir,
			'patterns' => $results,
			'total'    => count( $results ),
		);
	}
}
