<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\File_Mods_Guard;

defined( 'ABSPATH' ) || exit;

class Debug_Log_Clear extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/debug-log-clear',
			'args' => array(
				'label'               => __( 'Clear Debug Log', 'acrossai-core-abilities' ),
				'description'         => __( 'Truncates wp-content/debug.log to zero bytes.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'sub_group'           => 'debug',
				'sub_group_label'     => __( 'Debug', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(),
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

		$log_path = WP_CONTENT_DIR . '/debug.log';

		if ( ! is_file( $log_path ) ) {
			return array( 'success' => true, 'message' => __( 'debug.log does not exist; nothing to clear.', 'acrossai-core-abilities' ) );
		}

		if ( false === file_put_contents( $log_path, '' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return array( 'success' => false, 'message' => __( 'Could not clear debug.log.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'message' => __( 'debug.log cleared.', 'acrossai-core-abilities' ),
		);
	}
}
