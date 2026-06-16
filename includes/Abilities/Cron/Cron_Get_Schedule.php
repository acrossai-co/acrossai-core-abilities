<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Cron_Get_Schedule extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-get-schedule',
			'args' => array(
				'label'               => __( 'Get Schedule Details', 'acrossai-core-abilities' ),
				'description'         => __( 'Return a single schedule definition by name (interval + display) from wp_get_schedules().', 'acrossai-core-abilities' ),
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
						'name' => array( 'type' => 'string' ),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'schedule' => array( 'type' => array( 'object', 'null' ) ),
						'message'  => array( 'type' => 'string' ),
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
		$name = sanitize_key( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array( 'success' => false, 'message' => __( 'name is required.', 'acrossai-core-abilities' ) );
		}

		$schedules = wp_get_schedules();
		if ( ! isset( $schedules[ $name ] ) ) {
			return array(
				'success'  => true,
				'schedule' => null,
				/* translators: %s: schedule name */
				'message'  => sprintf( __( 'No schedule registered under "%s".', 'acrossai-core-abilities' ), $name ),
			);
		}

		return array(
			'success'  => true,
			'schedule' => array(
				'name'     => $name,
				'interval' => isset( $schedules[ $name ]['interval'] ) ? (int) $schedules[ $name ]['interval'] : 0,
				'display'  => isset( $schedules[ $name ]['display'] ) ? (string) $schedules[ $name ]['display'] : '',
			),
		);
	}
}
