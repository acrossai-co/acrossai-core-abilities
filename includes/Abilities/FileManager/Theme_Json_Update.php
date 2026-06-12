<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Theme_Json_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/theme-json-update',
			'args' => array(
				'label'               => __( 'Update theme.json', 'acrossai-core-abilities' ),
				'description'         => __( 'Deep-merges a partial settings object into theme.json for the active (or specified) theme and saves it.', 'acrossai-core-abilities' ),
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
						'data'       => array(
							'type'        => 'object',
							'description' => __( 'Partial theme.json object to deep-merge into the existing file.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'data' ),
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
		$partial    = $input['data'] ?? array();
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

		$existing = json_decode( $raw, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array( 'success' => false, 'message' => __( 'Existing theme.json contains invalid JSON.', 'acrossai-core-abilities' ) );
		}

		$merged  = $this->deep_merge( $existing, $partial );
		$encoded = wp_json_encode( $merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( false === $encoded ) {
			return array( 'success' => false, 'message' => __( 'Could not encode merged data as JSON.', 'acrossai-core-abilities' ) );
		}

		if ( false === file_put_contents( $json_path, $encoded ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return array( 'success' => false, 'message' => __( 'Could not write theme.json.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $json_path,
			'message' => __( 'theme.json updated.', 'acrossai-core-abilities' ),
		);
	}

	private function deep_merge( array $base, array $override ): array {
		foreach ( $override as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = $this->deep_merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}
}
