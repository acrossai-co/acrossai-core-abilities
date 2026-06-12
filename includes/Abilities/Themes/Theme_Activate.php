<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Themes;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Theme_Helpers;

defined( 'ABSPATH' ) || exit;

class Theme_Activate extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/theme-activate',
			'args' => array(
				'label'               => __( 'Activate Theme', 'acrossai-core-abilities' ),
				'description'         => __( 'Activate an installed WordPress theme by name, stylesheet, or partial match.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-themes',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'switch_themes' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme' => array(
							'type'        => 'string',
							'description' => __( 'Theme name, stylesheet directory (e.g. twentytwentyfour), or partial match.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'theme' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'message'       => array( 'type' => 'string' ),
						'matched_theme' => array( 'type' => 'string' ),
						'certainty'     => array( 'type' => 'number' ),
					),
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
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
		if ( empty( $input['theme'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No theme specified.', 'acrossai-core-abilities' ),
			);
		}

		return Theme_Helpers::activate_theme_by_slug( $input['theme'] );
	}
}
