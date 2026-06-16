<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Delete_Comment extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/delete-comment',
			'args' => array(
				'label'               => __( 'Delete Comment', 'acrossai-core-abilities' ),
				'description'         => __( 'Delete a comment via DELETE /wp/v2/comments/{id}. Defaults to trash; pass force=true to delete permanently.', 'acrossai-core-abilities' ),
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
						'id'    => array( 'type' => 'integer', 'minimum' => 1 ),
						'force' => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'deleted' => array( 'type' => 'boolean' ),
						'comment' => array( 'type' => 'object' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$id    = (int) ( $input['id'] ?? 0 );
		$force = ! empty( $input['force'] );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'DELETE', '/wp/v2/comments/' . $id );
		$request->set_param( 'force', $force );

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
			'deleted' => ! empty( $data['deleted'] ),
			'comment' => isset( $data['previous'] ) ? (array) $data['previous'] : $data,
			'message' => $force
				/* translators: %d: comment ID */
				? sprintf( __( 'Permanently deleted comment #%d.', 'acrossai-core-abilities' ), $id )
				/* translators: %d: comment ID */
				: sprintf( __( 'Trashed comment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
