<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Returns the capability map for a single role.
 *
 * Replaces the dropped acrossai-core-abilities-roles category.
 */
class Role_Capabilities extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-role-capabilities',
			'args' => array(
				'label'               => __( 'Get Role Capabilities', 'acrossai-core-abilities' ),
				'description'         => __( 'Return the full capability map for a single registered role. Useful before granting a role via user-create / user-update.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'list_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'role' => array(
							'type'        => 'string',
							'description' => __( 'Role slug (e.g. administrator, editor).', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'role' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'message'      => array( 'type' => 'string' ),
						'role'         => array( 'type' => 'string' ),
						'label'        => array( 'type' => 'string' ),
						'capabilities' => array( 'type' => 'object' ),
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
		if ( empty( $input['role'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No role specified.', 'acrossai-core-abilities' ),
			);
		}

		$slug = sanitize_key( $input['role'] );
		$role = get_role( $slug );

		if ( null === $role ) {
			return array(
				'success' => false,
				/* translators: %s: role slug */
				'message' => sprintf( __( 'Role "%s" does not exist.', 'acrossai-core-abilities' ), $slug ),
			);
		}

		$wp_roles = wp_roles();
		$details  = $wp_roles->roles[ $slug ] ?? array();
		$label    = isset( $details['name'] ) ? translate_user_role( $details['name'] ) : $slug;

		return array(
			'success'      => true,
			/* translators: %s: role label */
			'message'      => sprintf( __( 'Capabilities for role "%s".', 'acrossai-core-abilities' ), $label ),
			'role'         => $slug,
			'label'        => $label,
			'capabilities' => (object) $role->capabilities,
		);
	}
}
