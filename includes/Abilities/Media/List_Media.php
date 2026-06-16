<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class List_Media extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/list-media',
			'args' => array(
				'label'               => __( 'List Media', 'acrossai-core-abilities' ),
				'description'         => __( 'List media items via GET /wp/v2/media. Supports search, pagination, and a mime_type filter.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-media',
				'sub_group'           => 'manage',
				'sub_group_label'     => __( 'Manage', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'upload_files' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'page'      => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'    => array( 'type' => 'string' ),
						'mime_type' => array( 'type' => 'string' ),
						'parent'    => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'media'   => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
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
		$request = new \WP_REST_Request( 'GET', '/wp/v2/media' );
		$request->set_param( 'page', max( 1, (int) ( $input['page'] ?? 1 ) ) );
		$request->set_param( 'per_page', min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) ) );
		if ( ! empty( $input['search'] ) ) {
			$request->set_param( 'search', sanitize_text_field( (string) $input['search'] ) );
		}
		if ( ! empty( $input['mime_type'] ) ) {
			$request->set_param( 'mime_type', sanitize_mime_type( (string) $input['mime_type'] ) );
		}
		if ( isset( $input['parent'] ) ) {
			$request->set_param( 'parent', (int) $input['parent'] );
		}

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array( 'success' => false, 'message' => $response->as_error()->get_error_message() );
		}

		$data    = (array) $response->get_data();
		$headers = $response->get_headers();
		$total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $data );

		return array(
			'success' => true,
			'media'   => array_values( $data ),
			'total'   => $total,
		);
	}
}
