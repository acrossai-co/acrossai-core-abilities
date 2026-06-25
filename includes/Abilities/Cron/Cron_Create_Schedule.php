<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Cron_Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Persist a custom cron schedule. The schedule is stored in the
 * "acrossai_custom_cron_schedules" option and merged into wp_get_schedules()
 * on every request via the Cron_Helpers filter registered at plugins_loaded.
 */
class Cron_Create_Schedule extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-create-schedule',
			'args' => array(
				'label'               => __( 'Create Custom Schedule', 'acrossai-core-abilities' ),
				'description'         => __( 'Register a persistent custom cron schedule. The schedule is saved to wp_options and added back via the cron_schedules filter on every load.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-cron',
				'sub_group'           => 'write',
				'sub_group_label'     => __( 'Write Cron Jobs', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'     => array( 'type' => 'string' ),
						'interval' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Number of seconds between runs.', 'acrossai-core-abilities' ),
						),
						'display'  => array( 'type' => 'string' ),
					),
					'required'             => array( 'name', 'interval', 'display' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'name'     => array( 'type' => 'string' ),
						'interval' => array( 'type' => 'integer' ),
						'display'  => array( 'type' => 'string' ),
						'message'  => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$name     = sanitize_key( (string) ( $input['name'] ?? '' ) );
		$interval = (int) ( $input['interval'] ?? 0 );
		$display  = sanitize_text_field( (string) ( $input['display'] ?? '' ) );

		if ( '' === $name || $interval < 1 || '' === $display ) {
			return array(
				'success' => false,
				'message' => __( 'name (kebab-case), positive interval, and display are required.', 'acrossai-core-abilities' ),
			);
		}

		Cron_Helpers::add_custom( $name, $interval, $display );

		return array(
			'success'  => true,
			'name'     => $name,
			'interval' => $interval,
			'display'  => $display,
			/* translators: %s: schedule name */
			'message'  => sprintf( __( 'Registered custom schedule "%s".', 'acrossai-core-abilities' ), $name ),
		);
	}
}
