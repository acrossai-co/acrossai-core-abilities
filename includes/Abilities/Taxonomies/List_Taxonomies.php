<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * List taxonomies via the core REST endpoint GET /wp/v2/taxonomies.
 */
class List_Taxonomies extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/list-taxonomies',
			'args' => array(
				'label'               => __( 'List Taxonomies', 'acrossai-core-abilities' ),
				'description'         => __( 'List registered taxonomies via the core REST endpoint GET /wp/v2/taxonomies.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-taxonomies',
				'sub_group'           => 'taxonomies',
				'sub_group_label'     => __( 'Taxonomies', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_categories' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'type' => array(
							'type'        => 'string',
							'description' => __( 'Optional: restrict to taxonomies attached to a specific post type.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'taxonomies' => array( 'type' => 'array' ),
						'total'      => array( 'type' => 'integer' ),
						'message'    => array( 'type' => 'string' ),
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
		$request = new \WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		if ( ! empty( $input['type'] ) ) {
			$request->set_param( 'type', sanitize_key( (string) $input['type'] ) );
		}

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array(
				'success' => false,
				'message' => $response->as_error()->get_error_message(),
			);
		}

		$data = (array) $response->get_data();

		return array(
			'success'    => true,
			'taxonomies' => array_values( $data ),
			'total'      => count( $data ),
		);
	}
}
