<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Create_Menu_Item extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/create-menu-item',
			'args' => array(
				'label'               => __( 'Create Menu Item', 'acrossai-core-abilities' ),
				'description'         => __( 'Create a menu item via POST /wp/v2/menu-items. title and (object/object_id or url) are required.', 'acrossai-core-abilities' ),
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
						'title'       => array( 'type' => 'string' ),
						'type'        => array( 'type' => 'string', 'enum' => array( 'post_type', 'taxonomy', 'custom', 'block', 'post_type_archive' ), 'default' => 'custom' ),
						'object'      => array( 'type' => 'string' ),
						'object_id'   => array( 'type' => 'integer' ),
						'url'         => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer', 'default' => 0 ),
						'menu_order'  => array( 'type' => 'integer', 'default' => 0 ),
						'menus'       => array( 'type' => 'integer' ),
						'target'      => array( 'type' => 'string', 'enum' => array( '', '_blank' ) ),
						'classes'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'description' => array( 'type' => 'string' ),
						'xfn'         => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
					'required'             => array( 'title' ),
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
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$request = new \WP_REST_Request( 'POST', '/wp/v2/menu-items' );
		foreach ( array( 'title', 'type', 'object', 'object_id', 'url', 'parent', 'menu_order', 'menus', 'target', 'classes', 'description', 'xfn' ) as $field ) {
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
			'message' => __( 'Menu item created.', 'acrossai-core-abilities' ),
		);
	}
}
