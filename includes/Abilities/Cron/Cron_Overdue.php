<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Cron_Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Returns events whose scheduled timestamp is in the past — these should have
 * already run. A non-empty result is usually a signal that WP-Cron is not
 * firing (loopback blocked, DISABLE_WP_CRON, no real cron driver).
 */
class Cron_Overdue extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-overdue',
			'args' => array(
				'label'               => __( 'Get Overdue Cron Jobs', 'acrossai-core-abilities' ),
				'description'         => __( 'Return scheduled events whose timestamp is already in the past — useful to detect a stalled WP-Cron loopback or DISABLE_WP_CRON without a real cron driver.', 'acrossai-core-abilities' ),
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
						'grace_seconds' => array(
							'type'        => 'integer',
							'minimum'     => 0,
							'default'     => 0,
							'description' => __( 'Ignore events overdue by fewer than this many seconds.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'events'  => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
						'now'     => array( 'type' => 'integer' ),
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
		$grace  = max( 0, (int) ( $input['grace_seconds'] ?? 0 ) );
		$now    = (int) time();
		$cutoff = $now - $grace;

		$overdue = array_values(
			array_filter(
				Cron_Helpers::flatten_events(),
				static function ( array $event ) use ( $cutoff ): bool {
					return $event['timestamp'] <= $cutoff;
				}
			)
		);

		foreach ( $overdue as &$event ) {
			$event['overdue_seconds'] = $now - $event['timestamp'];
		}
		unset( $event );

		return array(
			'success' => true,
			'events'  => $overdue,
			'total'   => count( $overdue ),
			'now'     => $now,
		);
	}
}
