<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Delete a post (any post type). Defaults to a trash; pass force=true to bypass
 * trash and remove permanently.
 */
class Delete_Post extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/delete-post',
			'args' => array(
				'label'               => __( 'Delete Post', 'acrossai-core-abilities' ),
				'description'         => __( 'Delete a post (any post type) via wp_delete_post(). Defaults to trash; pass force=true to delete permanently.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'posts',
				'sub_group_label'     => __( 'Posts', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'delete_posts' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'    => array( 'type' => 'integer', 'minimum' => 1 ),
						'force' => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'id'      => array( 'type' => 'integer' ),
						'force'   => array( 'type' => 'boolean' ),
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
		$id    = (int) ( $input['id'] ?? 0 );
		$force = ! empty( $input['force'] );

		if ( $id <= 0 || ! get_post( $id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Post not found.', 'acrossai-core-abilities' ),
			);
		}

		if ( ! current_user_can( 'delete_post', $id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to delete this post.', 'acrossai-core-abilities' ),
			);
		}

		$result = $force ? wp_delete_post( $id, true ) : wp_trash_post( $id );
		if ( ! $result ) {
			return array(
				'success' => false,
				'message' => __( 'Could not delete the post.', 'acrossai-core-abilities' ),
			);
		}

		return array(
			'success' => true,
			'id'      => $id,
			'force'   => $force,
			'message' => $force
				/* translators: %d: post ID */
				? sprintf( __( 'Permanently deleted post #%d.', 'acrossai-core-abilities' ), $id )
				/* translators: %d: post ID */
				: sprintf( __( 'Moved post #%d to trash.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
