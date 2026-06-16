<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Comment_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-comment-meta',
			'args' => array(
				'label'               => __( 'Get Comment Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch the REST-exposed meta map for a comment via GET /wp/v2/comments/{id} (only keys registered with register_meta show_in_rest=true).', 'acrossai-core-abilities' ),
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
						'id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'key' => array( 'type' => 'string', 'default' => '' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'meta'    => array( 'type' => array( 'object', 'string', 'array', 'integer', 'boolean', 'null' ) ),
						'message' => array( 'type' => 'string' ),
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
		$id  = (int) ( $input['id'] ?? 0 );
		$key = (string) ( $input['key'] ?? '' );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'GET', '/wp/v2/comments/' . $id );
		$request->set_param( 'context', 'edit' );

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array(
				'success' => false,
				'message' => $response->as_error()->get_error_message(),
			);
		}

		$data = (array) $response->get_data();
		$meta = isset( $data['meta'] ) ? (array) $data['meta'] : array();
		if ( '' !== $key ) {
			$meta = array_key_exists( $key, $meta ) ? $meta[ $key ] : null;
		}

		return array(
			'success' => true,
			'meta'    => $meta,
		);
	}
}
