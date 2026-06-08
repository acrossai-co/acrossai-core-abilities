<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Plugins;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Plugin_Helpers;

defined( 'ABSPATH' ) || exit;

class Plugin_Deactivate extends Ability_Definition {

	protected function main_key(): string {
		return 'acrossai-core-plugins';
	}

	protected function main_key_label(): string {
		return __( 'Acrossai Core Plugins', 'acrossai-core-abilities' );
	}

	protected function sub_key(): string {
		return 'plugin-deactivate';
	}

	protected function sub_key_label(): string {
		return __( 'Deactivate Plugin', 'acrossai-core-abilities' );
	}

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/plugin-deactivate',
			'args' => array(
				'label'               => __( 'Deactivate Plugin', 'acrossai-core-abilities' ),
				'description'         => __( 'Deactivate an active WordPress plugin by name, slug, or partial match.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-plugins',
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
						'destructive' => true,
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

		return Plugin_Helpers::deactivate_plugin_by_slug( $input['plugin'] );
	}
}
