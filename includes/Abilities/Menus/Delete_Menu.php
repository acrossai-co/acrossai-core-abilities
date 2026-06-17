<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Delete_Menu extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/delete-menu',
			'args' => array(
				'label'               => __( 'Delete Menu', 'acrossai-core-abilities' ),
				'description'         => __( 'Delete a nav menu via DELETE /wp/v2/menus/{id}. Menus do not support trash — force=true is sent implicitly.', 'acrossai-core-abilities' ),
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
						'deleted' => array( 'type' => 'boolean' ),
						'menu'    => array( 'type' => 'object' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
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

		$snapshot = Menu_Formatter::menu_to_array( $menu );
		$result   = wp_delete_nav_menu( $id );

		if ( is_wp_error( $result ) || false === $result ) {
			return Menu_Formatter::error_from(
				$result,
				/* translators: %d: menu ID */
				sprintf( __( 'Could not delete menu #%d.', 'acrossai-core-abilities' ), $id )
			);
		}

		return array(
			'success' => true,
			'deleted' => true,
			'menu'    => $snapshot,
			/* translators: %d: menu ID */
			'message' => sprintf( __( 'Deleted menu #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
