<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class User_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-update',
			'args' => array(
				'label'               => __( 'Update User', 'acrossai-core-abilities' ),
				'description'         => __( 'Update an existing WordPress user. Only provided fields are changed.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user'         => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'email'        => array(
							'type'        => 'string',
							'description' => __( 'New email address.', 'acrossai-core-abilities' ),
						),
						'first_name'   => array(
							'type'        => 'string',
							'description' => __( 'First name.', 'acrossai-core-abilities' ),
						),
						'last_name'    => array(
							'type'        => 'string',
							'description' => __( 'Last name.', 'acrossai-core-abilities' ),
						),
						'display_name' => array(
							'type'        => 'string',
							'description' => __( 'Display name.', 'acrossai-core-abilities' ),
						),
						'url'          => array(
							'type'        => 'string',
							'description' => __( 'User website URL.', 'acrossai-core-abilities' ),
						),
						'password'     => array(
							'type'        => 'string',
							'description' => __( 'New password.', 'acrossai-core-abilities' ),
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
						'user_id' => array( 'type' => 'integer' ),
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

		$update = array( 'ID' => $user->ID );

		if ( array_key_exists( 'email', $input ) ) {
			$email = sanitize_email( (string) $input['email'] );
			if ( '' === $email || ! is_email( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid email address.', 'acrossai-core-abilities' ),
				);
			}
			$update['user_email'] = $email;
		}

		if ( array_key_exists( 'first_name', $input ) ) {
			$update['first_name'] = sanitize_text_field( (string) $input['first_name'] );
		}
		if ( array_key_exists( 'last_name', $input ) ) {
			$update['last_name'] = sanitize_text_field( (string) $input['last_name'] );
		}
		if ( array_key_exists( 'display_name', $input ) ) {
			$update['display_name'] = sanitize_text_field( (string) $input['display_name'] );
		}
		if ( array_key_exists( 'url', $input ) ) {
			$update['user_url'] = esc_url_raw( (string) $input['url'] );
		}
		if ( ! empty( $input['password'] ) ) {
			$update['user_pass'] = (string) $input['password'];
		}

		$result = wp_update_user( $update );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				/* translators: %s: error message */
				'message' => sprintf( __( 'Failed to update user: %s', 'acrossai-core-abilities' ), $result->get_error_message() ),
			);
		}

		$updated = get_user_by( 'id', (int) $result );

		return array(
			'success' => true,
			/* translators: %s: user login */
			'message' => sprintf( __( 'User "%s" updated successfully.', 'acrossai-core-abilities' ), $user->user_login ),
			'user_id' => (int) $result,
			'user'    => $updated ? User_Helpers::format_user( $updated ) : array(),
		);
	}
}
