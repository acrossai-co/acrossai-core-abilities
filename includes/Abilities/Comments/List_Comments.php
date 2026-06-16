<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class List_Comments extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/list-comments',
			'args' => array(
				'label'               => __( 'List Comments', 'acrossai-core-abilities' ),
				'description'         => __( 'List comments via GET /wp/v2/comments. Supports search, post filter, status filter, and pagination.', 'acrossai-core-abilities' ),
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
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'   => array( 'type' => 'string' ),
						'post'     => array( 'type' => 'integer' ),
						'status'   => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'comments' => array( 'type' => 'array' ),
						'total'    => array( 'type' => 'integer' ),
						'message'  => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'page', max( 1, (int) ( $input['page'] ?? 1 ) ) );
		$request->set_param( 'per_page', min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) ) );
		if ( ! empty( $input['search'] ) ) {
			$request->set_param( 'search', sanitize_text_field( (string) $input['search'] ) );
		}
		if ( ! empty( $input['post'] ) ) {
			$request->set_param( 'post', (int) $input['post'] );
		}
		if ( ! empty( $input['status'] ) ) {
			$request->set_param( 'status', sanitize_key( (string) $input['status'] ) );
		}

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array( 'success' => false, 'message' => $response->as_error()->get_error_message() );
		}

		$data    = (array) $response->get_data();
		$headers = $response->get_headers();
		$total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $data );

		return array(
			'success'  => true,
			'comments' => array_values( $data ),
			'total'    => $total,
		);
	}
}
