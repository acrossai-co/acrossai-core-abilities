<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Sessions;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class Session_Force_Logout extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/session-force-logout',
			'args' => array(
				'label'               => __( 'Force Logout User', 'acrossai-core-abilities' ),
				'description'         => __( 'Destroy all active sessions for a user, forcing them to log in again everywhere.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-sessions',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user' => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'user' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'           => array( 'type' => 'boolean' ),
						'message'           => array( 'type' => 'string' ),
						'user_id'           => array( 'type' => 'integer' ),
						'sessions_destroyed' => array( 'type' => 'integer' ),
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
		if ( empty( $input['user'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No user specified.', 'acrossai-core-abilities' ),
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

		$manager  = \WP_Session_Tokens::get_instance( (int) $user->ID );
		$existing = count( $manager->get_all() );
		$manager->destroy_all();

		return array(
			'success'            => true,
			/* translators: 1: number of sessions, 2: user login */
			'message'            => sprintf( __( 'Destroyed %1$d session(s) for user "%2$s".', 'acrossai-core-abilities' ), $existing, $user->user_login ),
			'user_id'            => (int) $user->ID,
			'sessions_destroyed' => (int) $existing,
		);
	}
}
