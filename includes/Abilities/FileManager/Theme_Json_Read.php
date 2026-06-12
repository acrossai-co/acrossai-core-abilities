<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Theme_Json_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/theme-json-read',
			'args' => array(
				'label'               => __( 'Read theme.json', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns the parsed contents of theme.json for the active (or specified) theme.', 'acrossai-core-abilities' ),
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
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object' ),
						'path'    => array( 'type' => 'string' ),
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
		$slug       = sanitize_text_field( $input['theme_slug'] ?? '' );
		$themes_dir = rtrim( get_theme_root(), '/' );
		$theme_dir  = '' !== $slug
			? realpath( $themes_dir . '/' . $slug )
			: realpath( get_stylesheet_directory() );

		if ( false === $theme_dir || 0 !== strpos( $theme_dir, $themes_dir ) || ! is_dir( $theme_dir ) ) {
			return array( 'success' => false, 'message' => __( 'Theme directory not found.', 'acrossai-core-abilities' ) );
		}

		$json_path = $theme_dir . '/theme.json';

		if ( ! is_file( $json_path ) ) {
			return array( 'success' => false, 'message' => __( 'theme.json not found in this theme.', 'acrossai-core-abilities' ) );
		}

		$raw = file_get_contents( $json_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $raw ) {
			return array( 'success' => false, 'message' => __( 'Could not read theme.json.', 'acrossai-core-abilities' ) );
		}

		$data = json_decode( $raw, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array( 'success' => false, 'message' => __( 'theme.json contains invalid JSON.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'data'    => $data,
			'path'    => $json_path,
		);
	}
}
