<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * List terms in any taxonomy via the core REST endpoint GET /wp/v2/{rest_base}.
 * The taxonomy must have show_in_rest=true and a rest_base.
 */
class List_Terms extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/list-terms',
			'args' => array(
				'label'               => __( 'List Terms', 'acrossai-core-abilities' ),
				'description'         => __( 'List terms in a taxonomy via the core REST endpoint GET /wp/v2/{rest_base}.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-taxonomies',
				'sub_group'           => 'terms',
				'sub_group_label'     => __( 'Terms', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_categories' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy' => array( 'type' => 'string' ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'   => array( 'type' => 'string' ),
						'parent'   => array( 'type' => 'integer' ),
						'orderby'  => array( 'type' => 'string', 'default' => 'name' ),
						'order'    => array( 'type' => 'string', 'enum' => array( 'asc', 'desc' ), 'default' => 'asc' ),
					),
					'required'             => array( 'taxonomy' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'terms'   => array( 'type' => 'array' ),
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
		$rest_base = Taxonomy_Routes::rest_base( sanitize_key( (string) ( $input['taxonomy'] ?? '' ) ) );
		if ( is_wp_error( $rest_base ) ) {
			return array( 'success' => false, 'message' => $rest_base->get_error_message() );
		}

		$request = new \WP_REST_Request( 'GET', '/wp/v2/' . $rest_base );
		$request->set_param( 'page', max( 1, (int) ( $input['page'] ?? 1 ) ) );
		$request->set_param( 'per_page', min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) ) );
		if ( ! empty( $input['search'] ) ) {
			$request->set_param( 'search', sanitize_text_field( (string) $input['search'] ) );
		}
		if ( isset( $input['parent'] ) ) {
			$request->set_param( 'parent', (int) $input['parent'] );
		}
		if ( ! empty( $input['orderby'] ) ) {
			$request->set_param( 'orderby', sanitize_key( (string) $input['orderby'] ) );
		}
		if ( ! empty( $input['order'] ) ) {
			$request->set_param( 'order', strtolower( (string) $input['order'] ) === 'desc' ? 'desc' : 'asc' );
		}

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array(
				'success' => false,
				'message' => $response->as_error()->get_error_message(),
			);
		}

		$data    = (array) $response->get_data();
		$headers = $response->get_headers();
		$total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $data );

		return array(
			'success' => true,
			'terms'   => array_values( $data ),
			'total'   => $total,
		);
	}
}
