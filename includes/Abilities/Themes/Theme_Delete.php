<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Themes;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\File_Mods_Guard;
use Acrossai_Core_Abilities\Includes\Utilities\Theme_Helpers;

defined( 'ABSPATH' ) || exit;

class Theme_Delete extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/theme-delete',
			'args' => array(
				'label'               => __( 'Delete Theme', 'acrossai-core-abilities' ),
				'description'         => __( 'Delete an installed WordPress theme by name, stylesheet, or partial match. The active theme cannot be deleted.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-themes',
				'sub_group'           => 'lifecycle',
				'sub_group_label'     => __( 'Lifecycle', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
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
						'destructive' => true,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$blocked = File_Mods_Guard::blocked_response( 'install' );
		if ( null !== $blocked ) {
			return $blocked;
		}

		if ( empty( $input['theme'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No theme specified.', 'acrossai-core-abilities' ),
			);
		}

		return Theme_Helpers::delete_theme_by_slug( $input['theme'] );
	}
}
