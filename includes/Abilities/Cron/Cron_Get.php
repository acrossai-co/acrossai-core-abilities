<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Cron_Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Return every scheduled event matching a hook name. A hook may have multiple
 * scheduled instances (recurring + one-off, or different args sets) — all are
 * returned ordered by timestamp.
 */
class Cron_Get extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-get',
			'args' => array(
				'label'               => __( 'Get Cron Job Details', 'acrossai-core-abilities' ),
				'description'         => __( 'Return all scheduled WP-Cron events for a given hook name (multiple instances possible — different args, recurring + one-off, etc.).', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-cron',
				'sub_group'           => 'read',
				'sub_group_label'     => __( 'Read Cron Jobs', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'hook' => array( 'type' => 'string' ),
					),
					'required'             => array( 'hook' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'hook'    => array( 'type' => 'string' ),
						'events'  => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$hook = sanitize_text_field( (string) ( $input['hook'] ?? '' ) );
		if ( '' === $hook ) {
			return array( 'success' => false, 'message' => __( 'hook is required.', 'acrossai-core-abilities' ) );
		}

		$events = array_values(
			array_filter(
				Cron_Helpers::flatten_events(),
				static function ( array $event ) use ( $hook ): bool {
					return $event['hook'] === $hook;
				}
			)
		);

		return array(
			'success' => true,
			'hook'    => $hook,
			'events'  => $events,
			'total'   => count( $events ),
		);
	}
}
