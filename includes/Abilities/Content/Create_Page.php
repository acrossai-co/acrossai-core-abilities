<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Create a page (post_type=page, hierarchical) via wp_insert_post().
 */
class Create_Page extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/create-page',
			'args' => array(
				'label'               => __( 'Create Page', 'acrossai-core-abilities' ),
				'description'         => __( 'Create a page via wp_insert_post() (post_type=page). Supports parent and menu_order for hierarchical layouts.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'pages',
				'sub_group_label'     => __( 'Pages', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_pages' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'title'      => array( 'type' => 'string' ),
						'content'    => array( 'type' => 'string' ),
						'status'     => array( 'type' => 'string', 'default' => 'draft' ),
						'parent'     => array( 'type' => 'integer', 'default' => 0 ),
						'menu_order' => array( 'type' => 'integer', 'default' => 0 ),
						'slug'       => array( 'type' => 'string' ),
						'author'     => array( 'type' => 'integer' ),
						'meta'       => array( 'type' => 'object' ),
					),
					'required'             => array( 'title' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'id'      => array( 'type' => 'integer' ),
						'page'    => array( 'type' => 'object' ),
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
		$args = array(
			'post_type'    => 'page',
			'post_title'   => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
			'post_content' => (string) ( $input['content'] ?? '' ),
			'post_status'  => sanitize_key( (string) ( $input['status'] ?? 'draft' ) ),
			'post_parent'  => (int) ( $input['parent'] ?? 0 ),
			'menu_order'   => (int) ( $input['menu_order'] ?? 0 ),
		);
		if ( ! empty( $input['slug'] ) ) {
			$args['post_name'] = sanitize_title( (string) $input['slug'] );
		}
		if ( ! empty( $input['author'] ) ) {
			$args['post_author'] = (int) $input['author'];
		}
		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
			$args['meta_input'] = $input['meta'];
		}

		$id = wp_insert_post( $args, true );
		if ( is_wp_error( $id ) ) {
			return array(
				'success' => false,
				'message' => $id->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'id'      => (int) $id,
			'page'    => (array) get_post( (int) $id, ARRAY_A ),
			/* translators: %d: page ID */
			'message' => sprintf( __( 'Created page #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
