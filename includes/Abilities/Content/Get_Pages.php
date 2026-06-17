<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * List pages via the core get_pages() helper. Supports parent, child_of,
 * sort_column, and a free-form search-by-title via the post__in/exclude flags.
 */
class Get_Pages extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/get-pages',
			'args' => array(
				'label'               => __( 'Get Pages', 'acrossai-core-abilities' ),
				'description'         => __( 'List pages via get_pages(). Supports parent / child_of filters and sort_column / sort_order.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'pages',
				'sub_group_label'     => __( 'Pages', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'parent'      => array( 'type' => 'integer', 'default' => -1 ),
						'child_of'    => array( 'type' => 'integer', 'default' => 0 ),
						'sort_column' => array( 'type' => 'string', 'default' => 'post_title' ),
						'sort_order'  => array( 'type' => 'string', 'enum' => array( 'ASC', 'DESC', 'asc', 'desc' ), 'default' => 'ASC' ),
						'number'      => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
						'exclude'     => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'pages'   => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
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
		$args = array(
			'parent'      => isset( $input['parent'] ) ? (int) $input['parent'] : -1,
			'child_of'    => (int) ( $input['child_of'] ?? 0 ),
			'sort_column' => sanitize_key( (string) ( $input['sort_column'] ?? 'post_title' ) ),
			'sort_order'  => strtoupper( (string) ( $input['sort_order'] ?? 'ASC' ) ) === 'DESC' ? 'DESC' : 'ASC',
			'number'      => max( 0, (int) ( $input['number'] ?? 0 ) ),
		);
		if ( ! empty( $input['exclude'] ) && is_array( $input['exclude'] ) ) {
			$args['exclude'] = array_map( 'intval', $input['exclude'] );
		}

		$pages = get_pages( $args );
		if ( ! is_array( $pages ) ) {
			$pages = array();
		}

		$out = array();
		foreach ( $pages as $p ) {
			$out[] = (array) $p;
		}

		return array(
			'success' => true,
			'pages'   => $out,
			'total'   => count( $out ),
		);
	}
}
