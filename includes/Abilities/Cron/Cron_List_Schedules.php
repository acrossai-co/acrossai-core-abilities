<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Cron_List_Schedules extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-list-schedules',
			'args' => array(
				'label'               => __( 'List Schedules', 'acrossai-core-abilities' ),
				'description'         => __( 'List every registered cron schedule via wp_get_schedules() — includes core schedules (hourly/twicedaily/daily/weekly), schedules added by other plugins, and persisted custom schedules.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-cron',
				'sub_group'           => 'read',
				'sub_group_label'     => __( 'Read Cron Jobs', 'acrossai-core-abilities' ),
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
						'success'   => array( 'type' => 'boolean' ),
						'schedules' => array( 'type' => 'array' ),
						'total'     => array( 'type' => 'integer' ),
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
		$schedules = wp_get_schedules();
		$out       = array();
		foreach ( $schedules as $name => $def ) {
			$out[] = array(
				'name'     => (string) $name,
				'interval' => isset( $def['interval'] ) ? (int) $def['interval'] : 0,
				'display'  => isset( $def['display'] ) ? (string) $def['display'] : '',
			);
		}

		return array(
			'success'   => true,
			'schedules' => $out,
			'total'     => count( $out ),
		);
	}
}
