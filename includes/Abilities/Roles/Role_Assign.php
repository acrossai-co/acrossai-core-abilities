<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Roles;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class Role_Assign extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/role-assign',
			'args' => array(
				'label'               => __( 'Assign Role to User', 'acrossai-core-abilities' ),
				'description'         => __( 'Add a role to a user. If replace=true, all existing roles are replaced with this one.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-roles',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'promote_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user'    => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'role'    => array(
							'type'        => 'string',
							'description' => __( 'Role slug to assign (e.g. editor, author, contributor).', 'acrossai-core-abilities' ),
						),
						'replace' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to replace all existing roles with this one.', 'acrossai-core-abilities' ),
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
						'destructive' => false,
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

		if ( null === get_role( $role ) ) {
			return array(
				'success' => false,
				/* translators: %s: role slug */
				'message' => sprintf( __( 'Role "%s" does not exist.', 'acrossai-core-abilities' ), $role ),
			);
		}

		$replace = ! empty( $input['replace'] );

		if ( $replace ) {
			$user->set_role( $role );
			/* translators: 1: role slug, 2: user login */
			$message = sprintf( __( 'Replaced roles for "%2$s" with "%1$s".', 'acrossai-core-abilities' ), $role, $user->user_login );
		} else {
			$user->add_role( $role );
			/* translators: 1: role slug, 2: user login */
			$message = sprintf( __( 'Added role "%1$s" to user "%2$s".', 'acrossai-core-abilities' ), $role, $user->user_login );
		}

		$refreshed = get_user_by( 'id', (int) $user->ID );

		return array(
			'success' => true,
			'message' => $message,
			'user_id' => (int) $user->ID,
			'role'    => $role,
			'roles'   => $refreshed ? array_values( (array) $refreshed->roles ) : array(),
		);
	}
}
