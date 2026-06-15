<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieve a single user. Optionally include user_meta (replacing the
 * dropped user-meta-get ability — pass include_meta=true or meta_keys=[...]).
 */
class User_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-get',
			'args' => array(
				'label'               => __( 'Get User', 'acrossai-core-abilities' ),
				'description'         => __( 'Retrieve a single WordPress user by ID, login, email, or slug. Optionally attach user_meta via include_meta (all keys) or meta_keys (specific keys). Pass include_sessions to attach the user\'s active login sessions (login time, expiration, IP, UA).', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'sub_group'           => 'users',
				'sub_group_label'     => __( 'Users', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'list_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user'         => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'include_meta' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Attach a "meta" map of all user_meta values to the response.', 'acrossai-core-abilities' ),
						),
						'meta_keys'        => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Restrict the attached meta map to these keys. Implies include_meta=true.', 'acrossai-core-abilities' ),
						),
						'include_sessions' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Attach the user\'s active login sessions to the response.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'user' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'user'    => array( 'type' => 'object' ),
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
		if ( ! isset( $input['user'] ) || '' === $input['user'] ) {
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

		$meta_keys    = isset( $input['meta_keys'] ) && is_array( $input['meta_keys'] ) ? $input['meta_keys'] : array();
		$include_meta = ! empty( $input['include_meta'] ) || ! empty( $meta_keys );

		return array(
			'success' => true,
			'message' => __( 'User retrieved.', 'acrossai-core-abilities' ),
			'user'    => User_Helpers::format_user(
				$user,
				array(
					'include_meta'     => $include_meta,
					'meta_keys'        => $meta_keys,
					'include_sessions' => ! empty( $input['include_sessions'] ),
				)
			),
		);
	}
}
