<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class File_Edit extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/file-edit',
			'args' => array(
				'label'               => __( 'Edit File', 'acrossai-core-abilities' ),
				'description'         => __( 'Overwrites the contents of an existing file within the WordPress installation. Path must be relative to ABSPATH.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'path'    => array(
							'type'        => 'string',
							'description' => __( 'File path relative to ABSPATH.', 'acrossai-core-abilities' ),
						),
						'content' => array(
							'type'        => 'string',
							'description' => __( 'New file content.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'path', 'content' ),
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
		$rel_path = sanitize_text_field( $input['path'] ?? '' );
		$content  = $input['content'] ?? '';
		$base     = rtrim( realpath( ABSPATH ) ?: ABSPATH, '/' );
		$real     = realpath( $base . '/' . ltrim( $rel_path, '/' ) );

		if ( false === $real || 0 !== strpos( $real, $base . '/' ) ) {
			return array( 'success' => false, 'message' => __( 'Invalid or disallowed file path.', 'acrossai-core-abilities' ) );
		}

		if ( ! is_file( $real ) ) {
			return array( 'success' => false, 'message' => __( 'File does not exist.', 'acrossai-core-abilities' ) );
		}

		$result = file_put_contents( $real, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $result ) {
			return array( 'success' => false, 'message' => __( 'Could not write file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $real,
			'message' => __( 'File saved.', 'acrossai-core-abilities' ),
		);
	}
}
