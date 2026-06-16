<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Comment extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-comment',
			'args' => array(
				'label'               => __( 'Update Comment', 'acrossai-core-abilities' ),
				'description'         => __( 'Update a comment via POST /wp/v2/comments/{id}. Only the supplied fields are touched.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-comments',
				'sub_group'           => 'manage',
				'sub_group_label'     => __( 'Manage', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'           => array( 'type' => 'integer', 'minimum' => 1 ),
						'content'      => array( 'type' => 'string' ),
						'status'       => array( 'type' => 'string' ),
						'author_name'  => array( 'type' => 'string' ),
						'author_email' => array( 'type' => 'string' ),
						'author_url'   => array( 'type' => 'string' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'comment' => array( 'type' => 'object' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'POST', '/wp/v2/comments/' . $id );
		foreach ( array( 'content', 'status', 'author_name', 'author_email', 'author_url' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$request->set_param( $field, (string) $input[ $field ] );
			}
		}

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array(
				'success' => false,
				'message' => $response->as_error()->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'comment' => (array) $response->get_data(),
			/* translators: %d: comment ID */
			'message' => sprintf( __( 'Updated comment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
