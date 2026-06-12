<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Theme_Files_Edit extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/theme-files-edit',
			'args' => array(
				'label'               => __( 'Edit Theme File', 'acrossai-core-abilities' ),
				'description'         => __( 'Overwrites the contents of a file inside a theme directory. Defaults to the active theme.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme_slug' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Theme folder name. Defaults to the active theme.', 'acrossai-core-abilities' ),
						),
						'file_path'  => array(
							'type'        => 'string',
							'description' => __( 'File path relative to the theme root.', 'acrossai-core-abilities' ),
						),
						'content'    => array(
							'type'        => 'string',
							'description' => __( 'New file content.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'file_path', 'content' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'path'    => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'            => array( 'success', 'message' ),
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
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$slug       = sanitize_text_field( $input['theme_slug'] ?? '' );
		$rel_file   = sanitize_text_field( $input['file_path'] ?? '' );
		$content    = $input['content'] ?? '';
		$themes_dir = rtrim( get_theme_root(), '/' );
		$theme_dir  = '' !== $slug
			? realpath( $themes_dir . '/' . $slug )
			: realpath( get_stylesheet_directory() );

		if ( false === $theme_dir || 0 !== strpos( $theme_dir, $themes_dir ) || ! is_dir( $theme_dir ) ) {
			return array( 'success' => false, 'message' => __( 'Theme directory not found.', 'acrossai-core-abilities' ) );
		}

		$abs_file = realpath( $theme_dir . '/' . ltrim( $rel_file, '/' ) );

		if ( false === $abs_file || 0 !== strpos( $abs_file, $theme_dir ) || ! is_file( $abs_file ) ) {
			return array( 'success' => false, 'message' => __( 'File not found within theme directory.', 'acrossai-core-abilities' ) );
		}

		$result = file_put_contents( $abs_file, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $result ) {
			return array( 'success' => false, 'message' => __( 'Could not write file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $abs_file,
			'message' => __( 'Theme file saved.', 'acrossai-core-abilities' ),
		);
	}
}
