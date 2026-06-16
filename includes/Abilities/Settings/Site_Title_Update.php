<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Settings;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Site_Title_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/site-title-update',
			'args' => array(
				'label'               => __( 'Update Site Title', 'acrossai-core-abilities' ),
				'description'         => __( 'Updates the site title (the "blogname" option). Whitespace is trimmed; the value cannot be empty.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-settings',
				'sub_group'           => 'site-identity',
				'sub_group_label'     => __( 'Site Identity', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'title' => array(
							'type'        => 'string',
							'description' => __( 'New site title.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'title' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'message'        => array( 'type' => 'string' ),
						'title'          => array( 'type' => 'string' ),
						'previous_title' => array( 'type' => 'string' ),
						'updated'        => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$new = trim( (string) ( $input['title'] ?? '' ) );
		if ( '' === $new ) {
			return array(
				'success' => false,
				'message' => __( 'Site title cannot be empty.', 'acrossai-core-abilities' ),
			);
		}

		$previous = (string) get_option( 'blogname', '' );
		$updated  = update_option( 'blogname', sanitize_text_field( $new ) );

		return array(
			'success'        => true,
			/* translators: %s: new site title */
			'message'        => sprintf( __( 'Site title updated to "%s".', 'acrossai-core-abilities' ), $new ),
			'title'          => wp_specialchars_decode( (string) get_option( 'blogname', '' ), ENT_QUOTES ),
			'previous_title' => wp_specialchars_decode( $previous, ENT_QUOTES ),
			'updated'        => (bool) $updated,
		);
	}
}
