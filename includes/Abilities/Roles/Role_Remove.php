<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Roles;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class Role_Remove extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/role-remove',
			'args' => array(
				'label'               => __( 'Remove Role from User', 'acrossai-core-abilities' ),
				'description'         => __( 'Remove a role from a user.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-roles',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'promote_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user' => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'role' => array(
							'type'        => 'string',
							'description' => __( 'Role slug to remove.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'user', 'role' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'user_id' => array( 'type' => 'integer' ),
						'role'    => array( 'type' => 'string' ),
						'roles'   => array( 'type' => 'array' ),
					),
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
		if ( empty( $input['user'] ) || empty( $input['role'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Both "user" and "role" are required.', 'acrossai-core-abilities' ),
			);
		}

		$user = User_Helpers::resolve_user( $input['user'] );

		if ( null === $user ) {
			return array(
				'success' => false,
				/* translators: %s: user identifier */
				'message' => sprintf( __( 'No user found matching "%s".', 'acrossai-core-abilities' ), (string) $input['user'] ),
			);
		}

		$role = sanitize_key( $input['role'] );

		if ( ! in_array( $role, (array) $user->roles, true ) ) {
			return array(
				'success' => false,
				/* translators: 1: role slug, 2: user login */
				'message' => sprintf( __( 'User "%2$s" does not have role "%1$s".', 'acrossai-core-abilities' ), $role, $user->user_login ),
				'user_id' => (int) $user->ID,
				'role'    => $role,
				'roles'   => array_values( (array) $user->roles ),
			);
		}

		$user->remove_role( $role );
		$refreshed = get_user_by( 'id', (int) $user->ID );

		return array(
			'success' => true,
			/* translators: 1: role slug, 2: user login */
			'message' => sprintf( __( 'Removed role "%1$s" from user "%2$s".', 'acrossai-core-abilities' ), $role, $user->user_login ),
			'user_id' => (int) $user->ID,
			'role'    => $role,
			'roles'   => $refreshed ? array_values( (array) $refreshed->roles ) : array(),
		);
	}
}
