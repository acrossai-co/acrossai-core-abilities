<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Upload a file to the Media Library from a URL. The bytes are fetched into a
 * temporary file (download_url) and handed to media_handle_sideload() so all
 * standard hooks/metadata generation run.
 */
class Upload_Media extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/upload-media',
			'args' => array(
				'label'               => __( 'Upload Media', 'acrossai-core-abilities' ),
				'description'         => __( 'Sideload an attachment from a remote URL into the Media Library via media_handle_sideload(). Optionally attach it to a post.', 'acrossai-core-abilities' ),
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
						'url'         => array( 'type' => 'string', 'format' => 'uri' ),
						'post_id'     => array( 'type' => 'integer', 'default' => 0 ),
						'title'       => array( 'type' => 'string' ),
						'alt_text'    => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'caption'     => array( 'type' => 'string' ),
					),
					'required'             => array( 'url' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'id'      => array( 'type' => 'integer' ),
						'media'   => array( 'type' => 'object' ),
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
		$url = sanitize_url( (string) ( $input['url'] ?? '' ) );
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return array(
				'success' => false,
				'message' => __( 'A valid http(s) URL is required.', 'acrossai-core-abilities' ),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return array(
				'success' => false,
				'message' => $tmp->get_error_message(),
			);
		}

		$file_array = array(
			'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ?: 'upload' ),
			'tmp_name' => $tmp,
		);

		$post_id = (int) ( $input['post_id'] ?? 0 );
		$desc    = sanitize_text_field( (string) ( $input['description'] ?? '' ) );
		$id      = media_handle_sideload( $file_array, $post_id, $desc );

		if ( is_wp_error( $id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return array(
				'success' => false,
				'message' => $id->get_error_message(),
			);
		}

		$updates = array();
		if ( ! empty( $input['title'] ) ) {
			$updates['post_title'] = sanitize_text_field( (string) $input['title'] );
		}
		if ( isset( $input['caption'] ) ) {
			$updates['post_excerpt'] = (string) $input['caption'];
		}
		if ( $updates ) {
			$updates['ID'] = (int) $id;
			wp_update_post( $updates );
		}
		if ( ! empty( $input['alt_text'] ) ) {
			update_post_meta( (int) $id, '_wp_attachment_image_alt', sanitize_text_field( (string) $input['alt_text'] ) );
		}

		return array(
			'success' => true,
			'id'      => (int) $id,
			'media'   => (array) get_post( (int) $id, ARRAY_A ),
			/* translators: %d: attachment ID */
			'message' => sprintf( __( 'Uploaded attachment #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
