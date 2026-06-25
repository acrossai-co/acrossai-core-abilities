<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Post_Revisions extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-post-revisions',
			'args' => array(
				'label'               => __( 'Get Post Revisions', 'acrossai-core-abilities' ),
				'description'         => __( 'List all stored revisions for a post by ID. Autosaves are hidden by default; pass include_autosaves=true to surface them.', 'acrossai-core-abilities' ),
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
						'id'                => array( 'type' => 'integer', 'minimum' => 1 ),
						'post_type'         => array(
							'type'        => 'string',
							'description' => __( 'Optional: error if the parent post does not match this type.', 'acrossai-core-abilities' ),
						),
						'include_autosaves' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'per_page'          => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25 ),
						'page'              => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'           => array( 'type' => 'boolean' ),
						'revisions'         => array( 'type' => 'array' ),
						'total'             => array( 'type' => 'integer' ),
						'revisions_enabled' => array( 'type' => 'boolean' ),
						'message'           => array( 'type' => 'string' ),
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
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$post = get_post( $id );
		if ( ! $post ) {
			return array( 'success' => false, 'message' => __( 'Post not found.', 'acrossai-core-abilities' ) );
		}

		$expected_type = isset( $input['post_type'] ) ? sanitize_key( (string) $input['post_type'] ) : '';
		if ( '' !== $expected_type && $post->post_type !== $expected_type ) {
			return array(
				'success' => false,
				/* translators: %s: expected post type */
				'message' => sprintf( __( 'Post does not match type "%s".', 'acrossai-core-abilities' ), $expected_type ),
			);
		}

		$include_autosaves = ! empty( $input['include_autosaves'] );
		$per_page          = min( 100, max( 1, (int) ( $input['per_page'] ?? 25 ) ) );
		$page              = max( 1, (int) ( $input['page'] ?? 1 ) );

		$all = wp_get_post_revisions( $id, array( 'check_enabled' => false ) );
		[ $slice, $total ] = Revision_Formatter::paginate( $all, $include_autosaves, $page, $per_page );

		return array(
			'success'           => true,
			'revisions'         => array_map( array( Revision_Formatter::class, 'to_array' ), $slice ),
			'total'             => $total,
			'revisions_enabled' => (bool) wp_revisions_enabled( $post ),
		);
	}
}
