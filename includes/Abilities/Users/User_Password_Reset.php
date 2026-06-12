<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class User_Password_Reset extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-password-reset',
			'args' => array(
				'label'               => __( 'Reset User Password', 'acrossai-core-abilities' ),
				'description'         => __( 'Send a password reset email to a user, or set a new password directly. Email notification is configurable.', 'acrossai-core-abilities' ),
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
						'method'     => array(
							'type'        => 'string',
							'enum'        => array( 'email', 'direct' ),
							'default'     => 'email',
							'description' => __( 'How to reset: "email" generates a reset link; "direct" sets a new password immediately.', 'acrossai-core-abilities' ),
						),
						'password'   => array(
							'type'        => 'string',
							'description' => __( 'New password (only used when method=direct). Auto-generated if omitted.', 'acrossai-core-abilities' ),
						),
						'send_email' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Whether to email the user. With method=email, false returns the reset link in the response instead. With method=direct, false skips the password-change notice.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'user' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'            => array( 'type' => 'boolean' ),
						'message'            => array( 'type' => 'string' ),
						'method'             => array( 'type' => 'string' ),
						'user_id'            => array( 'type' => 'integer' ),
						'email_sent'         => array( 'type' => 'boolean' ),
						'reset_link'         => array( 'type' => 'string' ),
						'generated_password' => array( 'type' => 'string' ),
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
						'idempotent'  => false,
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

		$method = isset( $input['method'] ) ? sanitize_text_field( $input['method'] ) : 'email';
		if ( ! in_array( $method, array( 'email', 'direct' ), true ) ) {
			$method = 'email';
		}

		$send_email = array_key_exists( 'send_email', $input ) ? (bool) $input['send_email'] : true;

		if ( 'direct' === $method ) {
			return $this->reset_direct( $user, $input, $send_email );
		}

		return $this->reset_via_email( $user, $send_email );
	}

	/**
	 * Set the user's password directly, optionally emailing them a change notice.
	 *
	 * @param \WP_User $user
	 * @param array    $input
	 * @param bool     $send_email
	 * @return array
	 */
	private function reset_direct( \WP_User $user, array $input, bool $send_email ): array {
		$generated = false;
		$password  = $input['password'] ?? '';
		if ( '' === $password ) {
			$password  = wp_generate_password( 16, true, true );
			$generated = true;
		}

		wp_set_password( $password, (int) $user->ID );

		$email_sent = false;
		if ( $send_email ) {
			$email_sent = $this->send_password_changed_notice( $user, $generated ? $password : '' );
		}

		$response = array(
			'success'    => true,
			/* translators: %s: user login */
			'message'    => sprintf( __( 'Password for "%s" has been updated.', 'acrossai-core-abilities' ), $user->user_login ),
			'method'     => 'direct',
			'user_id'    => (int) $user->ID,
			'email_sent' => $email_sent,
		);

		if ( $send_email && ! $email_sent ) {
			/* translators: %s: user email */
			$response['message'] .= ' ' . sprintf( __( '(Notification email to %s failed to send.)', 'acrossai-core-abilities' ), $user->user_email );
		}

		if ( $generated ) {
			$response['generated_password'] = $password;
		}

		return $response;
	}

	/**
	 * Generate a password reset link, optionally emailing it via WordPress.
	 *
	 * @param \WP_User $user
	 * @param bool     $send_email
	 * @return array
	 */
	private function reset_via_email( \WP_User $user, bool $send_email ): array {
		if ( $send_email ) {
			$result = retrieve_password( $user->user_login );

			if ( is_wp_error( $result ) ) {
				return array(
					'success'    => false,
					/* translators: %s: error message */
					'message'    => sprintf( __( 'Failed to send reset email: %s', 'acrossai-core-abilities' ), $result->get_error_message() ),
					'method'     => 'email',
					'user_id'    => (int) $user->ID,
					'email_sent' => false,
				);
			}

			return array(
				'success'    => true,
				/* translators: %s: user email */
				'message'    => sprintf( __( 'Password reset email sent to %s.', 'acrossai-core-abilities' ), $user->user_email ),
				'method'     => 'email',
				'user_id'    => (int) $user->ID,
				'email_sent' => true,
			);
		}

		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			return array(
				'success'    => false,
				/* translators: %s: error message */
				'message'    => sprintf( __( 'Failed to generate reset link: %s', 'acrossai-core-abilities' ), $key->get_error_message() ),
				'method'     => 'email',
				'user_id'    => (int) $user->ID,
				'email_sent' => false,
			);
		}

		$reset_link = network_site_url(
			'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user->user_login ),
			'login'
		);

		return array(
			'success'    => true,
			/* translators: %s: user login */
			'message'    => sprintf( __( 'Password reset link generated for "%s" (no email sent).', 'acrossai-core-abilities' ), $user->user_login ),
			'method'     => 'email',
			'user_id'    => (int) $user->ID,
			'email_sent' => false,
			'reset_link' => $reset_link,
		);
	}

	/**
	 * Send a "your password was changed" notice to the user.
	 *
	 * @param \WP_User $user
	 * @param string   $generated_password Only included in the email when non-empty.
	 * @return bool Whether the email was accepted for delivery.
	 */
	private function send_password_changed_notice( \WP_User $user, string $generated_password ): bool {
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] Your password has been changed', 'acrossai-core-abilities' ), $blogname );

		$display_name = $user->display_name ? $user->display_name : $user->user_login;

		/* translators: %s: display name */
		$message  = sprintf( __( 'Hi %s,', 'acrossai-core-abilities' ), $display_name ) . "\r\n\r\n";
		/* translators: %s: site URL */
		$message .= sprintf( __( 'Your password on %s has been reset by an administrator.', 'acrossai-core-abilities' ), home_url() ) . "\r\n\r\n";

		if ( '' !== $generated_password ) {
			/* translators: %s: new password */
			$message .= sprintf( __( 'Your new password is: %s', 'acrossai-core-abilities' ), $generated_password ) . "\r\n\r\n";
			$message .= __( 'Please log in and change it as soon as possible.', 'acrossai-core-abilities' ) . "\r\n\r\n";
		}

		$message .= __( 'If you did not expect this change, please contact the site administrator immediately.', 'acrossai-core-abilities' ) . "\r\n";

		return (bool) wp_mail( $user->user_email, $subject, $message );
	}
}
