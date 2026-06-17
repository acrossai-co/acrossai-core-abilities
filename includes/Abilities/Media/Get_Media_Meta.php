<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Fetch the "meta" field of a media item via GET /wp/v2/media/{id}. Only keys
 * registered with register_meta( show_in_rest => true ) appear here.
 */
class Get_Media_Meta extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/get-media-meta',
			'args' => array(
				'label'               => __( 'Get Media Meta', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch the REST-exposed meta map for a media item (only keys registered with register_meta show_in_rest=true are returned).', 'acrossai-core-abilities' ),
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
						'id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'key' => array( 'type' => 'string', 'default' => '' ),
					),
					'required'             => array( 'id' ),
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
		$id  = (int) ( $input['id'] ?? 0 );
		$key = (string) ( $input['key'] ?? '' );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$post = get_post( $id );
		if ( ! ( $post instanceof \WP_Post ) || 'attachment' !== $post->post_type ) {
			return array( 'success' => false, 'message' => __( 'Attachment not found.', 'acrossai-core-abilities' ) );
		}

		$meta_map = Media_Formatter::build_meta_map( $id );
		if ( '' !== $key ) {
			return array(
				'success' => true,
				'meta'    => array_key_exists( $key, $meta_map ) ? $meta_map[ $key ] : null,
			);
		}

		return array(
			'success' => true,
			'meta'    => $meta_map,
		);
	}
}
