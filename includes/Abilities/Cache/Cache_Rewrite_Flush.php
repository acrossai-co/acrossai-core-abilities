<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cache;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Cache_Rewrite_Flush extends Ability_Definition {

	protected function main_key(): string {
		return 'acrossai-core-cache';
	}

	protected function main_key_label(): string {
		return __( 'Acrossai Core Cache', 'acrossai-core-abilities' );
	}

	protected function sub_key(): string {
		return 'rewrite-flush';
	}

	protected function sub_key_label(): string {
		return __( 'Flush Rewrite Rules', 'acrossai-core-abilities' );
	}

	protected function ability(): array {
		return array(
			'name' => 'wp-agentic-admin/rewrite-flush',
			'args' => array(
				'label'               => __( 'Flush Rewrite Rules', 'acrossai-core-abilities' ),
				'description'         => __( 'Flushes WordPress rewrite rules via flush_rewrite_rules(). Use hard=true (default) to also regenerate the .htaccess file, or hard=false for an in-memory-only rebuild.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-cache',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array( 'hard' => true ),
					'properties'           => array(
						'hard' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'true regenerates .htaccess (hard flush); false rebuilds only the in-memory rules (soft flush).', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'hard'    => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'hard', 'message' ),
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
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$hard = isset( $input['hard'] ) ? (bool) $input['hard'] : true;

		flush_rewrite_rules( $hard );

		return array(
			'success' => true,
			'hard'    => $hard,
			'message' => $hard
				? __( 'Rewrite rules flushed (hard — .htaccess regenerated).', 'acrossai-core-abilities' )
				: __( 'Rewrite rules flushed (soft — in-memory only).', 'acrossai-core-abilities' ),
		);
	}
}
