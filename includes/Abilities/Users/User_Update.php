<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Update an existing user. Optionally set or delete user_meta in the same
 * call (replacing the dropped user-meta-update ability).
 */
class User_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-update',
			'args' => array(
				'label'               => __( 'Update User', 'acrossai-core-abilities' ),
				'description'         => __( 'Update an existing WordPress user. Only provided fields are changed. Pass "meta" to set user_meta values (JSON strings auto-decoded), and "delete_meta_keys" to remove keys — both run in the same call.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user'             => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'email'            => array( 'type' => 'string' ),
						'first_name'       => array( 'type' => 'string' ),
						'last_name'        => array( 'type' => 'string' ),
						'display_name'     => array( 'type' => 'string' ),
						'url'              => array( 'type' => 'string' ),
						'password'         => array( 'type' => 'string' ),
						'meta'             => array(
							'type'        => 'object',
							'description' => __( 'Map of user_meta key => value to set. String values that look like JSON are auto-decoded into arrays/objects.', 'acrossai-core-abilities' ),
						),
						'delete_meta_keys' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'user_meta keys to remove.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'user' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'message'      => array( 'type' => 'string' ),
						'user_id'      => array( 'type' => 'integer' ),
						'user'         => array( 'type' => 'object' ),
						'meta_updated' => array( 'type' => 'array' ),
						'meta_failed'  => array( 'type' => 'array' ),
						'meta_deleted' => array( 'type' => 'array' ),
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

		$update    = array( 'ID' => $user->ID );
		$has_field = false;

		if ( array_key_exists( 'email', $input ) ) {
			$email = sanitize_email( (string) $input['email'] );
			if ( '' === $email || ! is_email( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid email address.', 'acrossai-core-abilities' ),
				);
			}
			$update['user_email'] = $email;
			$has_field            = true;
		}
		if ( array_key_exists( 'first_name', $input ) ) {
			$update['first_name'] = sanitize_text_field( (string) $input['first_name'] );
			$has_field            = true;
		}
		if ( array_key_exists( 'last_name', $input ) ) {
			$update['last_name'] = sanitize_text_field( (string) $input['last_name'] );
			$has_field           = true;
		}
		if ( array_key_exists( 'display_name', $input ) ) {
			$update['display_name'] = sanitize_text_field( (string) $input['display_name'] );
			$has_field              = true;
		}
		if ( array_key_exists( 'url', $input ) ) {
			$update['user_url'] = esc_url_raw( (string) $input['url'] );
			$has_field          = true;
		}
		if ( ! empty( $input['password'] ) ) {
			$update['user_pass'] = (string) $input['password'];
			$has_field           = true;
		}

		// Apply core-field update only if at least one field was supplied.
		if ( $has_field ) {
			$result = wp_update_user( $update );

			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					/* translators: %s: error message */
					'message' => sprintf( __( 'Failed to update user: %s', 'acrossai-core-abilities' ), $result->get_error_message() ),
				);
			}
		}

		$user_id = (int) $user->ID;

		$meta_updated = array();
		$meta_failed  = array();
		if ( ! empty( $input['meta'] ) && ( is_array( $input['meta'] ) || is_object( $input['meta'] ) ) ) {
			$meta_result  = User_Helpers::apply_meta( $user_id, (array) $input['meta'] );
			$meta_updated = $meta_result['updated'];
			$meta_failed  = $meta_result['failed'];
		}

		$meta_deleted = array();
		if ( ! empty( $input['delete_meta_keys'] ) && is_array( $input['delete_meta_keys'] ) ) {
			$delete_result = User_Helpers::delete_meta( $user_id, $input['delete_meta_keys'] );
			$meta_deleted  = $delete_result['deleted'];
			$meta_failed   = array_merge( $meta_failed, $delete_result['failed'] );
		}

		$updated_user = get_user_by( 'id', $user_id );

		return array(
			'success'      => true,
			/* translators: %s: user login */
			'message'      => sprintf( __( 'User "%s" updated successfully.', 'acrossai-core-abilities' ), $user->user_login ),
			'user_id'      => $user_id,
			'user'         => $updated_user ? User_Helpers::format_user( $updated_user ) : array(),
			'meta_updated' => $meta_updated,
			'meta_failed'  => $meta_failed,
			'meta_deleted' => $meta_deleted,
		);
	}
}
