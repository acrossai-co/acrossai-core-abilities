<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Menu extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-menu',
			'args' => array(
				'label'               => __( 'Update Menu', 'acrossai-core-abilities' ),
				'description'         => __( 'Update a nav menu via POST /wp/v2/menus/{id}. Only the supplied fields are touched.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-menus',
				'sub_group'           => 'menus',
				'sub_group_label'     => __( 'Menus', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_theme_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'          => array( 'type' => 'integer', 'minimum' => 1 ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'locations'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'menu'    => array( 'type' => 'object' ),
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
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'POST', '/wp/v2/menus/' . $id );
		if ( isset( $input['name'] ) ) {
			$request->set_param( 'name', sanitize_text_field( (string) $input['name'] ) );
		}
		if ( isset( $input['slug'] ) ) {
			$request->set_param( 'slug', sanitize_title( (string) $input['slug'] ) );
		}
		if ( isset( $input['description'] ) ) {
			$request->set_param( 'description', (string) $input['description'] );
		}
		if ( isset( $input['locations'] ) && is_array( $input['locations'] ) ) {
			$request->set_param( 'locations', array_map( 'sanitize_key', $input['locations'] ) );
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
			'menu'    => (array) $response->get_data(),
			/* translators: %d: menu ID */
			'message' => sprintf( __( 'Updated menu #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
