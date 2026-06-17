<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Fonts;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches a single font family by ID.
 *
 * Delegates to the core WP_REST_Font_Families_Controller via rest_do_request().
 * WordPress core does not expose dedicated wp_*_font_family functions —
 * everything goes through the REST controller, so this ability does too.
 */
class Font_Family_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/font-family-get',
			'args' => array(
				'label'               => __( 'Get Font Family', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch a single Font Library font family record (wp_font_family CPT) by its post ID.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-fonts',
				'sub_group'           => 'font-families',
				'sub_group_label'     => __( 'Font Families', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Font family post ID.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'family'  => array( 'type' => 'object' ),
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
		$id = (int) ( $input['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'A valid font family ID is required.', 'acrossai-core-abilities' ),
			);
		}

		$request = new \WP_REST_Request( 'GET', '/wp/v2/font-families/' . $id );

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
			'family'  => (array) $response->get_data(),
		);
	}
}
