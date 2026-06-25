<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Options;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Option extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-option',
			'args' => array(
				'label'               => __( 'Get Option', 'acrossai-core-abilities' ),
				'description'         => __( 'Read a wp_options row via get_option().', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-options',
				'sub_group'           => 'manage',
				'sub_group_label'     => __( 'Manage', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'    => array( 'type' => 'string' ),
						'default' => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'array', 'object', 'null' ) ),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'name'    => array( 'type' => 'string' ),
						'value'   => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'array', 'object', 'null' ) ),
						'exists'  => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array( 'success' => false, 'message' => __( 'name is required.', 'acrossai-core-abilities' ) );
		}

		$sentinel = '__acrossai_missing__';
		$value    = get_option( $name, $sentinel );
		$exists   = $value !== $sentinel;

		return array(
			'success' => true,
			'name'    => $name,
			'value'   => $exists ? $value : ( $input['default'] ?? null ),
			'exists'  => $exists,
		);
	}
}
