<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Taxonomy extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-taxonomy',
			'args' => array(
				'label'               => __( 'Get Taxonomy', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch a single taxonomy via GET /wp/v2/taxonomies/{taxonomy}.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-taxonomies',
				'sub_group'           => 'taxonomies',
				'sub_group_label'     => __( 'Taxonomies', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy' => array( 'type' => 'string' ),
					),
					'required'             => array( 'taxonomy' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'taxonomy' => array( 'type' => 'object' ),
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
		$tax = sanitize_key( (string) ( $input['taxonomy'] ?? '' ) );
		if ( '' === $tax ) {
			return array(
				'success' => false,
				'message' => __( 'taxonomy is required.', 'acrossai-core-abilities' ),
			);
		}

		$check = Taxonomy_Routes::rest_base( $tax );
		if ( is_wp_error( $check ) ) {
			return array( 'success' => false, 'message' => $check->get_error_message() );
		}

		$obj = get_taxonomy( $tax );
		if ( ! ( $obj instanceof \WP_Taxonomy ) ) {
			return array( 'success' => false, 'message' => __( 'Taxonomy not found.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success'  => true,
			'taxonomy' => Term_Formatter::taxonomy_to_array( $obj ),
		);
	}
}
