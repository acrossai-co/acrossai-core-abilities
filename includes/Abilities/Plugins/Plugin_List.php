<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Plugins;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Plugin_Helpers;

defined( 'ABSPATH' ) || exit;

class Plugin_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/plugin-list',
			'args' => array(
				'label'               => __( 'List Plugins', 'acrossai-core-abilities' ),
				'description'         => __( 'List all installed WordPress plugins, optionally filtered by status.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-plugins',
				'sub_group'           => 'info',
				'sub_group_label'     => __( 'Info', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'status' => array(
							'type'        => 'string',
							'enum'        => array( 'all', 'active', 'inactive' ),
							'default'     => 'all',
							'description' => __( 'Filter plugins by status.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'plugins' => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
						'active'  => array( 'type' => 'integer' ),
					),
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
		$status = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'all';

		if ( ! in_array( $status, array( 'all', 'active', 'inactive' ), true ) ) {
			$status = 'all';
		}

		return Plugin_Helpers::get_all_plugins( $status );
	}
}
