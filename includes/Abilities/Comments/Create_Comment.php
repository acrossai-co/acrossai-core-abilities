<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Create_Comment extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/create-comment',
			'args' => array(
				'label'               => __( 'Create Comment', 'acrossai-core-abilities' ),
				'description'         => __( 'Create a comment via POST /wp/v2/comments. Requires post and content.', 'acrossai-core-abilities' ),
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
						'post'         => array( 'type' => 'integer', 'minimum' => 1 ),
						'content'      => array( 'type' => 'string' ),
						'author'       => array( 'type' => 'integer' ),
						'author_name'  => array( 'type' => 'string' ),
						'author_email' => array( 'type' => 'string' ),
						'author_url'   => array( 'type' => 'string' ),
						'parent'       => array( 'type' => 'integer', 'default' => 0 ),
						'status'       => array( 'type' => 'string' ),
					),
					'required'             => array( 'post', 'content' ),
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
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$request = new \WP_REST_Request( 'POST', '/wp/v2/comments' );
		foreach ( array( 'post', 'content', 'author', 'author_name', 'author_email', 'author_url', 'parent', 'status' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$request->set_param( $field, $input[ $field ] );
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
			'message' => __( 'Comment created.', 'acrossai-core-abilities' ),
		);
	}
}
