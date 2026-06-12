<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Roles;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Role_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/role-list',
			'args' => array(
				'label'               => __( 'List All Roles', 'acrossai-core-abilities' ),
				'description'         => __( 'List all registered WordPress roles, optionally with their capability maps.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-roles',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'list_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'include_capabilities' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Include the full capability map for each role.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'roles' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
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
		$include_caps = ! empty( $input['include_capabilities'] );

		$wp_roles = wp_roles();
		$roles    = array();

		foreach ( $wp_roles->roles as $slug => $details ) {
			$entry = array(
				'name'  => $slug,
				'label' => isset( $details['name'] ) ? translate_user_role( $details['name'] ) : $slug,
			);

			if ( $include_caps ) {
				$entry['capabilities'] = isset( $details['capabilities'] ) ? (object) $details['capabilities'] : (object) array();
			}

			$roles[] = $entry;
		}

		return array(
			'roles' => $roles,
			'total' => count( $roles ),
		);
	}
}
