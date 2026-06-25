<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Settings;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Site_Title_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/site-title-get',
			'args' => array(
				'label'               => __( 'Get Site Title', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns the current site title (the "blogname" option, displayed in the browser tab and across the site).', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-settings',
				'sub_group'           => 'site-identity',
				'sub_group_label'     => __( 'Site Identity', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'title'   => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
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
		return array(
			'success' => true,
			'title'   => wp_specialchars_decode( (string) get_option( 'blogname', '' ), ENT_QUOTES ),
		);
	}
}
