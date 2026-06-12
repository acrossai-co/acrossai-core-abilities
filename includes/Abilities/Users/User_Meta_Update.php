<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class User_Meta_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-meta-update',
			'args' => array(
				'label'               => __( 'Update User Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Set or delete a user meta value. JSON strings are auto-decoded into arrays/objects.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user'       => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'meta_key'   => array(
							'type'        => 'string',
							'description' => __( 'Meta key to set or delete.', 'acrossai-core-abilities' ),
						),
						'meta_value' => array(
							'type'        => 'string',
							'description' => __( 'Meta value (string; JSON-encoded values are auto-decoded). Ignored when action=delete.', 'acrossai-core-abilities' ),
						),
						'action'     => array(
							'type'        => 'string',
							'enum'        => array( 'set', 'delete' ),
							'default'     => 'set',
							'description' => __( 'Whether to set or delete the meta key.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'user', 'meta_key' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'message'  => array( 'type' => 'string' ),
						'user_id'  => array( 'type' => 'integer' ),
						'meta_key' => array( 'type' => 'string' ),
						'action'   => array( 'type' => 'string' ),
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
		if ( empty( $input['user'] ) || empty( $input['meta_key'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Both "user" and "meta_key" are required.', 'acrossai-core-abilities' ),
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

		$meta_key = sanitize_text_field( $input['meta_key'] );
		$action   = isset( $input['action'] ) ? sanitize_text_field( $input['action'] ) : 'set';

		if ( ! in_array( $action, array( 'set', 'delete' ), true ) ) {
			$action = 'set';
		}

		if ( 'delete' === $action ) {
			$result = delete_user_meta( (int) $user->ID, $meta_key );

			return array(
				'success'  => (bool) $result,
				/* translators: 1: meta key, 2: user login */
				'message'  => $result
					? sprintf( __( 'Deleted meta "%1$s" for user "%2$s".', 'acrossai-core-abilities' ), $meta_key, $user->user_login )
					: sprintf( __( 'Failed to delete meta "%1$s" for user "%2$s".', 'acrossai-core-abilities' ), $meta_key, $user->user_login ),
				'user_id'  => (int) $user->ID,
				'meta_key' => $meta_key,
				'action'   => 'delete',
			);
		}

		$value  = array_key_exists( 'meta_value', $input ) ? $input['meta_value'] : '';
		$value  = User_Helpers::maybe_decode_json( $value );
		$result = update_user_meta( (int) $user->ID, $meta_key, $value );

		if ( false === $result ) {
			return array(
				'success'  => false,
				/* translators: 1: meta key, 2: user login */
				'message'  => sprintf( __( 'Failed to update meta "%1$s" for user "%2$s".', 'acrossai-core-abilities' ), $meta_key, $user->user_login ),
				'user_id'  => (int) $user->ID,
				'meta_key' => $meta_key,
				'action'   => 'set',
			);
		}

		return array(
			'success'  => true,
			/* translators: 1: meta key, 2: user login */
			'message'  => sprintf( __( 'Updated meta "%1$s" for user "%2$s".', 'acrossai-core-abilities' ), $meta_key, $user->user_login ),
			'user_id'  => (int) $user->ID,
			'meta_key' => $meta_key,
			'action'   => 'set',
		);
	}
}
