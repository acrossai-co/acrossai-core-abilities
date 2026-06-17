<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Create_Menu extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/create-menu',
			'args' => array(
				'label'               => __( 'Create Menu', 'acrossai-core-abilities' ),
				'description'         => __( 'Create a new nav menu via POST /wp/v2/menus.', 'acrossai-core-abilities' ),
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
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'locations'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'             => array( 'name' ),
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
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array( 'success' => false, 'message' => __( 'name is required.', 'acrossai-core-abilities' ) );
		}

		$args = array();
		if ( ! empty( $input['slug'] ) ) {
			$args['menu-slug'] = sanitize_title( (string) $input['slug'] );
		}
		if ( isset( $input['description'] ) ) {
			$args['description'] = (string) $input['description'];
		}

		$menu_id = wp_create_nav_menu( wp_slash( $name ) );
		if ( is_wp_error( $menu_id ) ) {
			return Menu_Formatter::error_from( $menu_id, __( 'Could not create menu.', 'acrossai-core-abilities' ) );
		}

		if ( ! empty( $args ) ) {
			wp_update_nav_menu_object( (int) $menu_id, wp_slash( $args ) );
		}

		if ( ! empty( $input['locations'] ) && is_array( $input['locations'] ) ) {
			Menu_Formatter::set_menu_locations(
				(int) $menu_id,
				array_values( array_filter( array_map( 'sanitize_key', $input['locations'] ) ) )
			);
		}

		$menu = wp_get_nav_menu_object( (int) $menu_id );
		if ( ! ( $menu instanceof \WP_Term ) ) {
			return array( 'success' => false, 'message' => __( 'Menu created but could not be retrieved.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'menu'    => Menu_Formatter::menu_to_array( $menu ),
			'message' => __( 'Menu created.', 'acrossai-core-abilities' ),
		);
	}
}
