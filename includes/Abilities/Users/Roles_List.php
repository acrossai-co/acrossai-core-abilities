<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Lists every registered WordPress role.
 *
 * Replaces the dropped acrossai-core-abilities-roles category — role
 * management is part of user administration here.
 */
class Roles_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-roles-list',
			'args' => array(
				'label'               => __( 'List User Roles', 'acrossai-core-abilities' ),
				'description'         => __( 'List all registered WordPress roles, optionally with their capability maps. Use these slugs as input to user-create / user-update.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'sub_group'           => 'roles',
				'sub_group_label'     => __( 'Roles', 'acrossai-core-abilities' ),
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
