<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Plugins;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\File_Mods_Guard;

defined( 'ABSPATH' ) || exit;

class Plugin_Files_Manage extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/plugin-files-manage',
			'args' => array(
				'label'               => __( 'Manage Plugin Files', 'acrossai-core-abilities' ),
				'description'         => __( 'Copy or move a file within the WordPress plugins directory. Both source and destination must remain inside WP_PLUGIN_DIR.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
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
						'action'      => array(
							'type'        => 'string',
							'enum'        => array( 'copy', 'move' ),
							'description' => __( 'Operation to perform: copy or move.', 'acrossai-core-abilities' ),
						),
						'source'      => array(
							'type'        => 'string',
							'description' => __( 'Source file path relative to WP_PLUGIN_DIR.', 'acrossai-core-abilities' ),
						),
						'destination' => array(
							'type'        => 'string',
							'description' => __( 'Destination file path relative to WP_PLUGIN_DIR.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'action', 'source', 'destination' ),
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

		$action      = sanitize_text_field( $input['action'] ?? '' );
		$plugins_dir = rtrim( WP_PLUGIN_DIR, '/' );
		$src_real    = realpath( $plugins_dir . '/' . ltrim( sanitize_text_field( $input['source'] ?? '' ), '/' ) );
		$dst_path    = $plugins_dir . '/' . ltrim( sanitize_text_field( $input['destination'] ?? '' ), '/' );
		$dst_dir     = realpath( dirname( $dst_path ) );

		if ( false === $src_real || 0 !== strpos( $src_real, $plugins_dir . '/' ) || ! is_file( $src_real ) ) {
			return array( 'success' => false, 'message' => __( 'Source file not found or outside plugin directory.', 'acrossai-core-abilities' ) );
		}

		if ( false === $dst_dir || ( $dst_dir !== $plugins_dir && 0 !== strpos( $dst_dir, $plugins_dir . '/' ) ) ) {
			return array( 'success' => false, 'message' => __( 'Destination is outside the plugin directory.', 'acrossai-core-abilities' ) );
		}

		if ( ! in_array( $action, array( 'copy', 'move' ), true ) ) {
			return array( 'success' => false, 'message' => __( 'Invalid action. Use "copy" or "move".', 'acrossai-core-abilities' ) );
		}

		if ( 'copy' === $action ) {
			$ok = copy( $src_real, $dst_path );
		} else {
			$ok = rename( $src_real, $dst_path );
		}

		if ( ! $ok ) {
			return array( 'success' => false, 'message' => __( 'File operation failed.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			/* translators: 1: action (copy/move), 2: destination path */
			'message' => sprintf( __( 'File %1$s completed to %2$s.', 'acrossai-core-abilities' ), $action, $dst_path ),
		);
	}
}
