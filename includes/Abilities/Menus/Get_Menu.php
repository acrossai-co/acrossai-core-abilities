<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Menu extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-menu',
			'args' => array(
				'label'               => __( 'Get Menu', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch a nav menu via GET /wp/v2/menus/{id}.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-menus',
				'sub_group'           => 'menus',
				'sub_group_label'     => __( 'Menus', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array( 'type' => 'integer', 'minimum' => 1 ),
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
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$menu = wp_get_nav_menu_object( $id );
		if ( ! ( $menu instanceof \WP_Term ) ) {
			return array( 'success' => false, 'message' => __( 'Menu not found.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'menu'    => Menu_Formatter::menu_to_array( $menu ),
		);
	}
}
