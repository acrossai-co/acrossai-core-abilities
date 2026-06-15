<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Plugins;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Plugin_Helpers;

defined( 'ABSPATH' ) || exit;

class Plugin_Activate extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/plugin-activate',
			'args' => array(
				'label'               => __( 'Activate Plugin', 'acrossai-core-abilities' ),
				'description'         => __( 'Activate an installed WordPress plugin by name, slug, or partial match.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-plugins',
				'sub_group'           => 'lifecycle',
				'sub_group_label'     => __( 'Lifecycle', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'plugin' => array(
							'type'        => 'string',
							'description' => __( 'Plugin name, file path (e.g. akismet/akismet.php), or partial match.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'plugin' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'message'        => array( 'type' => 'string' ),
						'matched_plugin' => array( 'type' => 'string' ),
						'certainty'      => array( 'type' => 'number' ),
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
		if ( empty( $input['plugin'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No plugin specified.', 'acrossai-core-abilities' ),
			);
		}

		return Plugin_Helpers::activate_plugin_by_slug( $input['plugin'] );
	}
}
