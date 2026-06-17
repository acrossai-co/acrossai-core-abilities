<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Media_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-media-meta',
			'args' => array(
				'label'               => __( 'Update Media Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Write meta values on a media item via POST /wp/v2/media/{id} with a meta object. Only keys registered with register_meta show_in_rest=true accept writes.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-media',
				'sub_group'           => 'meta',
				'sub_group_label'     => __( 'Meta', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'   => array( 'type' => 'integer', 'minimum' => 1 ),
						'meta' => array(
							'type'        => 'object',
							'description' => __( 'Object of meta keys → values to write.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'id', 'meta' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'meta'    => array( 'type' => 'object' ),
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
		$id   = (int) ( $input['id'] ?? 0 );
		$meta = isset( $input['meta'] ) && is_array( $input['meta'] ) ? $input['meta'] : array();
		if ( $id <= 0 || empty( $meta ) ) {
			return array( 'success' => false, 'message' => __( 'id and a non-empty meta object are required.', 'acrossai-core-abilities' ) );
		}

		$post = get_post( $id );
		if ( ! ( $post instanceof \WP_Post ) || 'attachment' !== $post->post_type ) {
			return array( 'success' => false, 'message' => __( 'Attachment not found.', 'acrossai-core-abilities' ) );
		}

		foreach ( $meta as $key => $value ) {
			$key = (string) $key;
			if ( ! Media_Formatter::is_meta_key_writable( $key ) ) {
				continue;
			}
			$sanitized = sanitize_meta( $key, $value, 'post' );
			update_post_meta( $id, $key, wp_slash( $sanitized ) );
		}

		return array(
			'success' => true,
			'meta'    => Media_Formatter::build_meta_map( $id ),
			/* translators: %d: attachment ID */
			'message' => sprintf( __( 'Wrote meta on attachment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
