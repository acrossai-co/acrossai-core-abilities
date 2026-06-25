<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Fetch post meta directly via get_post_meta(). Works for ANY meta key — does
 * not require register_meta() / show_in_rest. Pass an empty key to get the
 * complete meta map.
 */
class Get_Post_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-post-meta',
			'args' => array(
				'label'               => __( 'Get Post Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch post meta via get_post_meta(). Pass key="" (the default) to retrieve every meta key for the post.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
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
						'key'     => array( 'type' => 'string', 'default' => '' ),
						'single'  => array( 'type' => 'boolean', 'default' => true ),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'meta'    => array( 'type' => array( 'object', 'string', 'array', 'integer', 'boolean', 'null' ) ),
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
		$post_id = (int) ( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Post not found.', 'acrossai-core-abilities' ),
			);
		}

		$key    = (string) ( $input['key'] ?? '' );
		$single = ! isset( $input['single'] ) || ! empty( $input['single'] );

		$value = get_post_meta( $post_id, $key, $single );

		return array(
			'success' => true,
			'meta'    => $value,
		);
	}
}
