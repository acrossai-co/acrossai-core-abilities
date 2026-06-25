<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Settings;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Resets / flushes WordPress rewrite rules. Equivalent to clicking
 * "Save Changes" on Settings → Permalinks without changing the structure.
 *
 * hard=true regenerates .htaccess on Apache hosts (and equivalent on IIS);
 * hard=false (default) only rebuilds the in-DB rewrite cache.
 */
class Permalink_Flush extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/permalink-flush',
			'args' => array(
				'label'               => __( 'Reset / Flush Permalinks', 'acrossai-core-abilities' ),
				'description'         => __( 'Rebuilds WordPress rewrite rules — useful after registering custom post types, taxonomies, or rewrite endpoints. Pass hard=true to also regenerate .htaccess (Apache) where supported.', 'acrossai-core-abilities' ),
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
					'properties'           => array(
						'hard' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Regenerate .htaccess on Apache (and equivalent on IIS).', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'   => array( 'type' => 'boolean' ),
						'message'   => array( 'type' => 'string' ),
						'hard'      => array( 'type' => 'boolean' ),
						'structure' => array( 'type' => 'string' ),
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
		$hard = ! empty( $input['hard'] );

		flush_rewrite_rules( $hard );

		return array(
			'success'   => true,
			'message'   => $hard
				? __( 'Rewrite rules flushed and .htaccess regenerated where supported.', 'acrossai-core-abilities' )
				: __( 'Rewrite rules flushed.', 'acrossai-core-abilities' ),
			'hard'      => $hard,
			'structure' => (string) get_option( 'permalink_structure', '' ),
		);
	}
}
