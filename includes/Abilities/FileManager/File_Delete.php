<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\File_Mods_Guard;

defined( 'ABSPATH' ) || exit;

class File_Delete extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/file-delete',
			'args' => array(
				'label'               => __( 'Delete File', 'acrossai-core-abilities' ),
				'description'         => __( 'Deletes a file within the WordPress installation. Path must be relative to ABSPATH.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'sub_group'           => 'files',
				'sub_group_label'     => __( 'Files', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'path' => array(
							'type'        => 'string',
							'description' => __( 'File path relative to ABSPATH.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'path' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
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
						'destructive' => true,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$blocked = File_Mods_Guard::blocked_response();
		if ( null !== $blocked ) {
			return $blocked;
		}

		$rel_path = sanitize_text_field( $input['path'] ?? '' );
		$base     = rtrim( realpath( ABSPATH ) ?: ABSPATH, '/' );
		$real     = realpath( $base . '/' . ltrim( $rel_path, '/' ) );

		if ( false === $real || 0 !== strpos( $real, $base . '/' ) ) {
			return array( 'success' => false, 'message' => __( 'Invalid or disallowed file path.', 'acrossai-core-abilities' ) );
		}

		if ( ! is_file( $real ) ) {
			return array( 'success' => false, 'message' => __( 'File does not exist.', 'acrossai-core-abilities' ) );
		}

		if ( ! wp_delete_file( $real ) ) {
			return array( 'success' => false, 'message' => __( 'Could not delete file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'message' => __( 'File deleted.', 'acrossai-core-abilities' ),
		);
	}
}
