<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\File_Mods_Guard;

defined( 'ABSPATH' ) || exit;

class File_Create extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/file-create',
			'args' => array(
				'label'               => __( 'Create File', 'acrossai-core-abilities' ),
				'description'         => __( 'Creates a new file within the WordPress installation. Fails if the file already exists. Path must be relative to ABSPATH.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
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
						'path'    => array(
							'type'        => 'string',
							'description' => __( 'File path relative to ABSPATH.', 'acrossai-core-abilities' ),
						),
						'content' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Initial file content.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'path' ),
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
						'idempotent'  => false,
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
		$content  = $input['content'] ?? '';
		$base     = rtrim( realpath( ABSPATH ) ?: ABSPATH, '/' );
		$abs_path = $base . '/' . ltrim( $rel_path, '/' );
		$real     = realpath( dirname( $abs_path ) );

		if ( false === $real || 0 !== strpos( $real, $base . '/' ) ) {
			return array( 'success' => false, 'message' => __( 'Invalid or disallowed file path.', 'acrossai-core-abilities' ) );
		}

		if ( file_exists( $abs_path ) ) {
			return array( 'success' => false, 'message' => __( 'File already exists. Use file-edit to overwrite.', 'acrossai-core-abilities' ) );
		}

		$result = file_put_contents( $abs_path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $result ) {
			return array( 'success' => false, 'message' => __( 'Could not create file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $abs_path,
			'message' => __( 'File created.', 'acrossai-core-abilities' ),
		);
	}
}
