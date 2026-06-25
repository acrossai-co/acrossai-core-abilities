<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class List_Menus extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/list-menus',
			'args' => array(
				'label'               => __( 'List Menus', 'acrossai-core-abilities' ),
				'description'         => __( 'List nav menus via GET /wp/v2/menus.', 'acrossai-core-abilities' ),
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
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'menus'   => array( 'type' => 'array' ),
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

		$all = wp_get_nav_menus( array( 'hide_empty' => false ) );
		if ( ! is_array( $all ) ) {
			$all = array();
		}

		$total = count( $all );
		$slice = array_slice( $all, ( $page - 1 ) * $per_page, $per_page );

		$formatted = array_map(
			array( Menu_Formatter::class, 'menu_to_array' ),
			array_values(
				array_filter(
					$slice,
					static function ( $t ): bool {
						return $t instanceof \WP_Term;
					}
				)
			)
		);

		return array(
			'success' => true,
			'menus'   => $formatted,
			'total'   => $total,
		);
	}
}
