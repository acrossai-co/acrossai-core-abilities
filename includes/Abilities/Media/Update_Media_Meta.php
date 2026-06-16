<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Media_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-media-meta',
			'args' => array(
				'label'               => __( 'Update Media Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Write meta values on a media item via POST /wp/v2/media/{id} with a meta object. Only keys registered with register_meta show_in_rest=true accept writes.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-media',
				'sub_group'           => 'meta',
				'sub_group_label'     => __( 'Meta', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'upload_files' );
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

		$request = new \WP_REST_Request( 'POST', '/wp/v2/media/' . $id );
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
			/* translators: %d: attachment ID */
			'message' => sprintf( __( 'Wrote meta on attachment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
