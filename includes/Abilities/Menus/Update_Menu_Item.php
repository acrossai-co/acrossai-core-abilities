<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Menu_Item extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-menu-item',
			'args' => array(
				'label'               => __( 'Update Menu Item', 'acrossai-core-abilities' ),
				'description'         => __( 'Update a menu item via POST /wp/v2/menu-items/{id}. Only the supplied fields are touched.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-menus',
				'sub_group'           => 'menu-items',
				'sub_group_label'     => __( 'Menu Items', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_theme_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'          => array( 'type' => 'integer', 'minimum' => 1 ),
						'title'       => array( 'type' => 'string' ),
						'url'         => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer' ),
						'menu_order'  => array( 'type' => 'integer' ),
						'menus'       => array( 'type' => 'integer' ),
						'target'      => array( 'type' => 'string', 'enum' => array( '', '_blank' ) ),
						'classes'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'description' => array( 'type' => 'string' ),
						'xfn'         => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'item'    => array( 'type' => 'object' ),
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

		$request = new \WP_REST_Request( 'POST', '/wp/v2/menu-items/' . $id );
		foreach ( array( 'title', 'url', 'parent', 'menu_order', 'menus', 'target', 'classes', 'description', 'xfn' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$request->set_param( $field, $input[ $field ] );
			}
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
			'item'    => (array) $response->get_data(),
			/* translators: %d: menu item ID */
			'message' => sprintf( __( 'Updated menu item #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
