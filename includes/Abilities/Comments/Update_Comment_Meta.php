<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Comment_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-comment-meta',
			'args' => array(
				'label'               => __( 'Update Comment Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Write meta values on a comment via POST /wp/v2/comments/{id} with a meta object. Only keys registered with register_meta show_in_rest=true accept writes.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-comments',
				'sub_group'           => 'meta',
				'sub_group_label'     => __( 'Meta', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'   => array( 'type' => 'integer', 'minimum' => 1 ),
						'meta' => array(
							'type'        => 'object',
							'description' => __( 'Object of meta keys → values to write.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'id', 'meta' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'meta'    => array( 'type' => 'object' ),
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
		$id   = (int) ( $input['id'] ?? 0 );
		$meta = isset( $input['meta'] ) && is_array( $input['meta'] ) ? $input['meta'] : array();
		if ( $id <= 0 || empty( $meta ) ) {
			return array( 'success' => false, 'message' => __( 'id and a non-empty meta object are required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'POST', '/wp/v2/comments/' . $id );
		$request->set_param( 'meta', $meta );

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array(
				'success' => false,
				'message' => $response->as_error()->get_error_message(),
			);
		}

		$data = (array) $response->get_data();

		return array(
			'success' => true,
			'meta'    => isset( $data['meta'] ) ? (array) $data['meta'] : array(),
			/* translators: %d: comment ID */
			'message' => sprintf( __( 'Wrote meta on comment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
