<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class File_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/file-read',
			'args' => array(
				'label'               => __( 'Read File', 'acrossai-core-abilities' ),
				'description'         => __( 'Reads the contents of a file within the WordPress installation. Path must be relative to ABSPATH.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'path' => array(
							'type'        => 'string',
							'description' => __( 'File path relative to ABSPATH (e.g. wp-content/uploads/test.txt).', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'path' ),
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
		$rel_path = sanitize_text_field( $input['path'] ?? '' );
		$abs_path = $this->resolve_safe_path( $rel_path, ABSPATH );

		if ( null === $abs_path ) {
			return array( 'success' => false, 'message' => __( 'Invalid or disallowed file path.', 'acrossai-core-abilities' ) );
		}

		if ( ! is_file( $abs_path ) ) {
			return array( 'success' => false, 'message' => __( 'File does not exist.', 'acrossai-core-abilities' ) );
		}

		$content = file_get_contents( $abs_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			return array( 'success' => false, 'message' => __( 'Could not read file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'content' => $content,
			'path'    => $abs_path,
			'size'    => strlen( $content ),
		);
	}

	private function resolve_safe_path( string $rel_path, string $base_dir ): ?string {
		$base = rtrim( realpath( $base_dir ) ?: $base_dir, '/' );
		$full = realpath( $base . '/' . ltrim( $rel_path, '/' ) );
		if ( false === $full || 0 !== strpos( $full, $base . '/' ) ) {
			return null;
		}
		return $full;
	}
}
