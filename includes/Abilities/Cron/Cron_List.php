<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Cron_Helpers;

defined( 'ABSPATH' ) || exit;

class Cron_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-list',
			'args' => array(
				'label'               => __( 'List Cron Jobs', 'acrossai-core-abilities' ),
				'description'         => __( 'List every scheduled WP-Cron event via _get_cron_array(), flattened to one row per event with timestamp, hook, schedule, interval, and args.', 'acrossai-core-abilities' ),
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
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'events'  => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
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
		$events = Cron_Helpers::flatten_events();
		return array(
			'success' => true,
			'events'  => $events,
			'total'   => count( $events ),
		);
	}
}
