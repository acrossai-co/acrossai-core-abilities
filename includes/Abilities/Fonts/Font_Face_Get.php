<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Fonts;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches a single font face under a given font family.
 *
 * WordPress core does not expose dedicated wp_*_font_face functions —
 * everything goes through the REST controller, so this ability does too.
 */
class Font_Face_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/font-face-get',
			'args' => array(
				'label'               => __( 'Get Font Face', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch a single Font Library font face (wp_font_face CPT) under a given font family.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-fonts',
				'sub_group'           => 'font-faces',
				'sub_group_label'     => __( 'Font Faces', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'font_family_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Parent font family post ID.', 'acrossai-core-abilities' ),
						),
						'id'             => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Font face post ID.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'font_family_id', 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'face'    => array( 'type' => 'object' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$family_id = (int) ( $input['font_family_id'] ?? 0 );
		$face_id   = (int) ( $input['id'] ?? 0 );
		if ( $family_id <= 0 || $face_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Both font_family_id and id are required.', 'acrossai-core-abilities' ),
			);
		}

		$request = new \WP_REST_Request( 'GET', '/wp/v2/font-families/' . $family_id . '/font-faces/' . $face_id );

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			$error = $response->as_error();
			return array(
				'success' => false,
				'message' => $error->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'face'    => (array) $response->get_data(),
		);
	}
}
