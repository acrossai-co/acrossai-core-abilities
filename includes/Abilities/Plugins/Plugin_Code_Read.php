<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Plugins;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Plugin_Code_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/plugin-code-read',
			'args' => array(
				'label'               => __( 'Read Plugin Code', 'acrossai-core-abilities' ),
				'description'         => __( 'Reads the contents of a file inside a plugin directory.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-plugins',
				'sub_group'           => 'files',
				'sub_group_label'     => __( 'Files', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'plugin_slug' => array(
							'type'        => 'string',
							'description' => __( 'Plugin folder name.', 'acrossai-core-abilities' ),
						),
						'file_path'   => array(
							'type'        => 'string',
							'description' => __( 'File path relative to the plugin root (e.g. includes/class-main.php).', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'plugin_slug', 'file_path' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'content' => array( 'type' => 'string' ),
						'path'    => array( 'type' => 'string' ),
						'size'    => array( 'type' => 'integer' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'            => array( 'success' ),
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
		$slug        = sanitize_text_field( $input['plugin_slug'] ?? '' );
		$rel_file    = sanitize_text_field( $input['file_path'] ?? '' );
		$plugins_dir = rtrim( WP_PLUGIN_DIR, '/' );
		$plugin_path = realpath( $plugins_dir . '/' . $slug );

		if ( false === $plugin_path || 0 !== strpos( $plugin_path, $plugins_dir . '/' ) || ! is_dir( $plugin_path ) ) {
			return array( 'success' => false, 'message' => __( 'Plugin directory not found.', 'acrossai-core-abilities' ) );
		}

		$abs_file = realpath( $plugin_path . '/' . ltrim( $rel_file, '/' ) );

		if ( false === $abs_file || 0 !== strpos( $abs_file, $plugin_path . '/' ) || ! is_file( $abs_file ) ) {
			return array( 'success' => false, 'message' => __( 'File not found within plugin directory.', 'acrossai-core-abilities' ) );
		}

		$content = file_get_contents( $abs_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			return array( 'success' => false, 'message' => __( 'Could not read file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'content' => $content,
			'path'    => $abs_file,
			'size'    => strlen( $content ),
		);
	}
}
