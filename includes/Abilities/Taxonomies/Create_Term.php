<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Create_Term extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/create-term',
			'args' => array(
				'label'               => __( 'Create Term', 'acrossai-core-abilities' ),
				'description'         => __( 'Create a term in a taxonomy via POST /wp/v2/{rest_base}.', 'acrossai-core-abilities' ),
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
						'taxonomy'    => array( 'type' => 'string' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer', 'default' => 0 ),
					),
					'required'             => array( 'taxonomy', 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'term'    => array( 'type' => 'object' ),
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
		$base = Taxonomy_Routes::rest_base( sanitize_key( (string) ( $input['taxonomy'] ?? '' ) ) );
		if ( is_wp_error( $base ) ) {
			return array( 'success' => false, 'message' => $base->get_error_message() );
		}

		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array( 'success' => false, 'message' => __( 'name is required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'POST', '/wp/v2/' . $base );
		$request->set_param( 'name', $name );
		if ( ! empty( $input['slug'] ) ) {
			$request->set_param( 'slug', sanitize_title( (string) $input['slug'] ) );
		}
		if ( isset( $input['description'] ) ) {
			$request->set_param( 'description', (string) $input['description'] );
		}
		if ( isset( $input['parent'] ) ) {
			$request->set_param( 'parent', (int) $input['parent'] );
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
			'term'    => (array) $response->get_data(),
			/* translators: 1: name, 2: taxonomy */
			'message' => sprintf( __( 'Created term "%1$s" in "%2$s".', 'acrossai-core-abilities' ), $name, $input['taxonomy'] ?? '' ),
		);
	}
}
