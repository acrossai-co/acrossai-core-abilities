<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Settings;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the current WordPress permalink structure (Settings → Permalinks).
 * Reports which named preset the saved value matches, plus category/tag bases.
 */
class Permalink_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/permalink-get',
			'args' => array(
				'label'               => __( 'Get Permalink Structure', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns the current permalink_structure, the matching preset name (plain, day-and-name, month-and-name, numeric, post-name, or custom), plus category_base and tag_base.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-settings',
				'sub_group'           => 'permalinks',
				'sub_group_label'     => __( 'Permalinks', 'acrossai-core-abilities' ),
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
						'success'          => array( 'type' => 'boolean' ),
						'structure'        => array( 'type' => 'string' ),
						'structure_preset' => array( 'type' => 'string' ),
						'category_base'    => array( 'type' => 'string' ),
						'tag_base'         => array( 'type' => 'string' ),
						'is_pretty'        => array( 'type' => 'boolean' ),
						'permalink_examples' => array( 'type' => 'object' ),
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
		$structure     = (string) get_option( 'permalink_structure', '' );
		$category_base = (string) get_option( 'category_base', '' );
		$tag_base      = (string) get_option( 'tag_base', '' );

		$home = trailingslashit( (string) home_url() );

		return array(
			'success'          => true,
			'structure'        => $structure,
			'structure_preset' => Permalink_Presets::match( $structure ),
			'category_base'    => $category_base,
			'tag_base'         => $tag_base,
			'is_pretty'        => '' !== $structure,
			'permalink_examples' => array(
				'post' => $home . ltrim( '' === $structure ? '?p=123' : str_replace(
					array( '%year%', '%monthnum%', '%day%', '%postname%', '%post_id%' ),
					array( gmdate( 'Y' ), gmdate( 'm' ), gmdate( 'd' ), 'sample-post', '123' ),
					$structure
				), '/' ),
			),
		);
	}
}
