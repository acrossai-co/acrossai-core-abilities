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
		$request = new \WP_REST_Request( 'GET', '/wp/v2/menu-items' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'page', max( 1, (int) ( $input['page'] ?? 1 ) ) );
		$request->set_param( 'per_page', min( 100, max( 1, (int) ( $input['per_page'] ?? 25 ) ) ) );
		if ( ! empty( $input['menus'] ) ) {
			$request->set_param( 'menus', (int) $input['menus'] );
		}

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array( 'success' => false, 'message' => $response->as_error()->get_error_message() );
		}

		$data    = (array) $response->get_data();
		$headers = $response->get_headers();
		$total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $data );

		return array(
			'success' => true,
			'items'   => array_values( $data ),
			'total'   => $total,
		);
	}
}
