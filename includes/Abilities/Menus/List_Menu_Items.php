<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class List_Menu_Items extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/list-menu-items',
			'args' => array(
				'label'               => __( 'List Menu Items', 'acrossai-core-abilities' ),
				'description'         => __( 'List menu items via GET /wp/v2/menu-items. Use menus={id} to scope to a single menu.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-menus',
				'sub_group'           => 'menu-items',
				'sub_group_label'     => __( 'Menu Items', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'menus'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'items'   => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
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
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 25 ) ) );

		if ( ! empty( $input['menus'] ) ) {
			// Scoped to a single menu — wp_get_nav_menu_items handles ordering
			// (`update_post_term_cache` off saves a few queries).
			$menu_id = (int) $input['menus'];
			$all     = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );
			if ( ! is_array( $all ) ) {
				$all = array();
			}
		} else {
			$query = new \WP_Query(
				array(
					'post_type'      => 'nav_menu_item',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				)
			);
			$all   = $query->posts;
		}

		$total = count( $all );
		$slice = array_slice( $all, ( $page - 1 ) * $per_page, $per_page );

		$formatted = array_map(
			array( Menu_Formatter::class, 'item_to_array' ),
			array_values(
				array_filter(
					$slice,
					static function ( $p ): bool {
						return $p instanceof \WP_Post;
					}
				)
			)
		);

		return array(
			'success' => true,
			'items'   => $formatted,
			'total'   => $total,
		);
	}
}
