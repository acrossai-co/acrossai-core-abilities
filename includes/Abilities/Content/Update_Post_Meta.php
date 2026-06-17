<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Write a single post meta value via update_post_meta(). Works for ANY meta key.
 */
class Update_Post_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/update-post-meta',
			'args' => array(
				'label'               => __( 'Update Post Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Set a post meta value via update_post_meta(). If the meta is registered via register_meta() and protected, the request will be rejected.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'posts',
				'sub_group_label'     => __( 'Posts', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'key'     => array( 'type' => 'string' ),
						'value'   => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'array', 'object', 'null' ) ),
					),
					'required'             => array( 'post_id', 'key' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'updated' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$post_id = (int) ( $input['post_id'] ?? 0 );
		$key     = sanitize_text_field( (string) ( $input['key'] ?? '' ) );

		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Post not found.', 'acrossai-core-abilities' ),
			);
		}
		if ( '' === $key || is_protected_meta( $key, 'post' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid or protected meta key.', 'acrossai-core-abilities' ),
			);
		}

		$result = update_post_meta( $post_id, $key, $input['value'] ?? '' );

		return array(
			'success' => true,
			'updated' => (bool) $result,
			/* translators: 1: meta key, 2: post ID */
			'message' => sprintf( __( 'Wrote meta "%1$s" on post #%2$d.', 'acrossai-core-abilities' ), $key, $post_id ),
		);
	}
}
