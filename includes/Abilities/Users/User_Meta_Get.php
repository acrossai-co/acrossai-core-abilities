<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class User_Meta_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-meta-get',
			'args' => array(
				'label'               => __( 'Get User Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Retrieve a single user meta value by user and meta_key.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'user'     => array(
							'type'        => array( 'string', 'integer' ),
							'description' => __( 'User ID, login, email, or slug.', 'acrossai-core-abilities' ),
						),
						'meta_key' => array(
							'type'        => 'string',
							'description' => __( 'Meta key to retrieve.', 'acrossai-core-abilities' ),
						),
						'single'   => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Whether to return a single value (true) or an array of all values for the key (false).', 'acrossai-core-abilities' ),
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
						'value'    => array(),
						'exists'   => array( 'type' => 'boolean' ),
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
		$single   = isset( $input['single'] ) ? (bool) $input['single'] : true;

		$exists = metadata_exists( 'user', $user->ID, $meta_key );
		$value  = get_user_meta( $user->ID, $meta_key, $single );

		return array(
			'success'  => true,
			'message'  => __( 'User meta retrieved.', 'acrossai-core-abilities' ),
			'user_id'  => (int) $user->ID,
			'meta_key' => $meta_key,
			'value'    => $value,
			'exists'   => (bool) $exists,
		);
	}
}
