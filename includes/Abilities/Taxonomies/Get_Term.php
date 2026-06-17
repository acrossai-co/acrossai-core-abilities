<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Term extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-term',
			'args' => array(
				'label'               => __( 'Get Term', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch a single term in a taxonomy via GET /wp/v2/{rest_base}/{id}.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-taxonomies',
				'sub_group'           => 'terms',
				'sub_group_label'     => __( 'Terms', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy' => array( 'type' => 'string' ),
						'id'       => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					'required'             => array( 'taxonomy', 'id' ),
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
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? '' ) );
		$check    = Taxonomy_Routes::rest_base( $taxonomy );
		if ( is_wp_error( $check ) ) {
			return array( 'success' => false, 'message' => $check->get_error_message() );
		}
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$term = get_term( $id, $taxonomy );
		if ( is_wp_error( $term ) ) {
			return Term_Formatter::error_from( $term, __( 'Term not found.', 'acrossai-core-abilities' ) );
		}
		if ( ! ( $term instanceof \WP_Term ) ) {
			return array( 'success' => false, 'message' => __( 'Term not found.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'term'    => Term_Formatter::term_to_array( $term ),
		);
	}
}
