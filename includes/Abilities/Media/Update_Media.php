<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Update_Media extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/update-media',
			'args' => array(
				'label'               => __( 'Update Media', 'acrossai-core-abilities' ),
				'description'         => __( 'Update an attachment\'s title, caption, description, or alt text via POST /wp/v2/media/{id}.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-media',
				'sub_group'           => 'manage',
				'sub_group_label'     => __( 'Manage', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'          => array( 'type' => 'integer', 'minimum' => 1 ),
						'title'       => array( 'type' => 'string' ),
						'caption'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'alt_text'    => array( 'type' => 'string' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'media'   => array( 'type' => 'object' ),
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
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$post = get_post( $id );
		if ( ! ( $post instanceof \WP_Post ) || 'attachment' !== $post->post_type ) {
			return array( 'success' => false, 'message' => __( 'Attachment not found.', 'acrossai-core-abilities' ) );
		}

		$post_data  = array( 'ID' => $id );
		$has_change = false;
		if ( isset( $input['title'] ) ) {
			$post_data['post_title'] = (string) $input['title'];
			$has_change              = true;
		}
		if ( isset( $input['caption'] ) ) {
			$post_data['post_excerpt'] = (string) $input['caption'];
			$has_change                = true;
		}
		if ( isset( $input['description'] ) ) {
			$post_data['post_content'] = (string) $input['description'];
			$has_change                = true;
		}

		if ( $has_change ) {
			$result = wp_update_post( wp_slash( $post_data ), true );
			if ( is_wp_error( $result ) || 0 === $result ) {
				return Media_Formatter::error_from(
					$result,
					/* translators: %d: attachment ID */
					sprintf( __( 'Could not update attachment #%d.', 'acrossai-core-abilities' ), $id )
				);
			}
		}

		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( (string) $input['alt_text'] ) );
		}

		$updated = get_post( $id );
		return array(
			'success' => true,
			'media'   => $updated instanceof \WP_Post ? Media_Formatter::to_array( $updated ) : array(),
			/* translators: %d: attachment ID */
			'message' => sprintf( __( 'Updated attachment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
