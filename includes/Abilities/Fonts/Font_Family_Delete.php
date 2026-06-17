<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Fonts;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Deletes a font family. The core endpoint requires force=true (no trash for
 * font CPTs), so this ability sets it implicitly.
 *
 * WordPress core does not expose dedicated wp_*_font_family functions —
 * everything goes through the REST controller, so this ability does too.
 */
class Font_Family_Delete extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/font-family-delete',
			'args' => array(
				'label'               => __( 'Delete Font Family', 'acrossai-core-abilities' ),
				'description'         => __( 'Permanently delete a Font Library font family and all of its child font faces. Trash is not supported for font CPTs — deletion is immediate.', 'acrossai-core-abilities' ),
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
						'deleted' => array( 'type' => 'boolean' ),
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
						'readonly'    => false,
						'destructive' => true,
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

		$request = new \WP_REST_Request( 'DELETE', '/wp/v2/font-families/' . $id );
		$request->set_param( 'force', true );

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			$error = $response->as_error();
			return array(
				'success' => false,
				'message' => $error->get_error_message(),
			);
		}

		$data = (array) $response->get_data();

		return array(
			'success' => true,
			'deleted' => ! empty( $data['deleted'] ),
			'family'  => isset( $data['previous'] ) ? (array) $data['previous'] : array(),
			/* translators: %d: font family ID */
			'message' => sprintf( __( 'Deleted font family #%d.', 'acrossai-core-abilities' ), $id ),
		);
	}
}
