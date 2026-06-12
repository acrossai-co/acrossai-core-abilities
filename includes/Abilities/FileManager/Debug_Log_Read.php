<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Debug_Log_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/debug-log-read',
			'args' => array(
				'label'               => __( 'Read Debug Log', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns the contents of wp-content/debug.log. Use the lines parameter to limit output to the last N lines.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'lines' => array(
							'type'        => 'integer',
							'default'     => 0,
							'minimum'     => 0,
							'maximum'     => 10000,
							'description' => __( 'Return only the last N lines. 0 returns the full file.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'content' => array( 'type' => 'string' ),
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
		$log_path = WP_CONTENT_DIR . '/debug.log';

		if ( ! is_file( $log_path ) ) {
			return array( 'success' => false, 'message' => __( 'debug.log does not exist.', 'acrossai-core-abilities' ) );
		}

		$content = file_get_contents( $log_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			return array( 'success' => false, 'message' => __( 'Could not read debug.log.', 'acrossai-core-abilities' ) );
		}

		$lines = isset( $input['lines'] ) ? (int) $input['lines'] : 0;

		if ( $lines > 0 ) {
			$all_lines = explode( "\n", $content );
			$content   = implode( "\n", array_slice( $all_lines, -$lines ) );
		}

		return array(
			'success' => true,
			'content' => $content,
			'size'    => strlen( $content ),
		);
	}
}
