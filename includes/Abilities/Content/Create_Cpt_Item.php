<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Create a record of any post type. post_type is required and validated via
 * post_type_exists(). Distinct from create-post in that it forbids defaults —
 * the caller must declare the target type explicitly.
 */
class Create_Cpt_Item extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/create-cpt-item',
			'args' => array(
				'label'               => __( 'Create CPT Item', 'acrossai-core-abilities' ),
				'description'         => __( 'Create a custom post type record. post_type is required and must be registered.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'cpt',
				'sub_group_label'     => __( 'Custom Post Types', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type' => array( 'type' => 'string' ),
						'title'     => array( 'type' => 'string' ),
						'content'   => array( 'type' => 'string' ),
						'excerpt'   => array( 'type' => 'string' ),
						'status'    => array( 'type' => 'string', 'default' => 'draft' ),
						'slug'      => array( 'type' => 'string' ),
						'author'    => array( 'type' => 'integer' ),
						'meta'      => array( 'type' => 'object' ),
					),
					'required'             => array( 'post_type', 'title' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'id'      => array( 'type' => 'integer' ),
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
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? '' ) );
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return array(
				'success' => false,
				/* translators: %s: post type */
				'message' => sprintf( __( 'Unknown post type "%s".', 'acrossai-core-abilities' ), $post_type ),
			);
		}

		$cap = get_post_type_object( $post_type )->cap->edit_posts ?? 'edit_posts';
		if ( ! current_user_can( $cap ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to create items of this type.', 'acrossai-core-abilities' ),
			);
		}

		$args = array(
			'post_type'    => $post_type,
			'post_title'   => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
			'post_content' => (string) ( $input['content'] ?? '' ),
			'post_excerpt' => (string) ( $input['excerpt'] ?? '' ),
			'post_status'  => sanitize_key( (string) ( $input['status'] ?? 'draft' ) ),
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
			'item'    => (array) get_post( (int) $id, ARRAY_A ),
			/* translators: 1: post type, 2: ID */
			'message' => sprintf( __( 'Created %1$s #%2$d.', 'acrossai-core-abilities' ), $post_type, $id ),
		);
	}
}
