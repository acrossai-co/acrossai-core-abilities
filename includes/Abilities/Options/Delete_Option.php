<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Options;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Delete_Option extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/delete-option',
			'args' => array(
				'label'               => __( 'Delete Option', 'acrossai-core-abilities' ),
				'description'         => __( 'Delete a wp_options row via delete_option(). Idempotent — succeeds even if the option does not exist.', 'acrossai-core-abilities' ),
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
						'name' => array( 'type' => 'string' ),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'deleted' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array( 'success' => false, 'message' => __( 'name is required.', 'acrossai-core-abilities' ) );
		}

		$deleted = delete_option( $name );

		return array(
			'success' => true,
			'deleted' => (bool) $deleted,
			/* translators: %s: option name */
			'message' => sprintf( __( 'Deleted option "%s".', 'acrossai-core-abilities' ), $name ),
		);
	}
}
