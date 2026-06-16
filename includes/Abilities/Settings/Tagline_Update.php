<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Settings;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Tagline_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/tagline-update',
			'args' => array(
				'label'               => __( 'Update Tagline', 'acrossai-core-abilities' ),
				'description'         => __( 'Updates the site tagline (the "blogdescription" option). Empty values are accepted to clear the tagline.', 'acrossai-core-abilities' ),
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
						'tagline' => array(
							'type'        => 'string',
							'description' => __( 'New tagline. Empty string clears it.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'tagline' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'          => array( 'type' => 'boolean' ),
						'message'          => array( 'type' => 'string' ),
						'tagline'          => array( 'type' => 'string' ),
						'previous_tagline' => array( 'type' => 'string' ),
						'updated'          => array( 'type' => 'boolean' ),
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
		if ( ! array_key_exists( 'tagline', $input ) ) {
			return array(
				'success' => false,
				'message' => __( 'A "tagline" value is required (pass an empty string to clear it).', 'acrossai-core-abilities' ),
			);
		}

		$new      = sanitize_text_field( (string) $input['tagline'] );
		$previous = (string) get_option( 'blogdescription', '' );
		$updated  = update_option( 'blogdescription', $new );

		return array(
			'success'          => true,
			'message'          => '' === $new
				? __( 'Tagline cleared.', 'acrossai-core-abilities' )
				/* translators: %s: new tagline */
				: sprintf( __( 'Tagline updated to "%s".', 'acrossai-core-abilities' ), $new ),
			'tagline'          => wp_specialchars_decode( (string) get_option( 'blogdescription', '' ), ENT_QUOTES ),
			'previous_tagline' => wp_specialchars_decode( $previous, ENT_QUOTES ),
			'updated'          => (bool) $updated,
		);
	}
}
