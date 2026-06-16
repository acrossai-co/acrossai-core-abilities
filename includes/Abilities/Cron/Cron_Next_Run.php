<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Cron_Next_Run extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-next-run',
			'args' => array(
				'label'               => __( 'Get Next Run Time', 'acrossai-core-abilities' ),
				'description'         => __( 'Return the next scheduled run timestamp for a hook (and optional args) via wp_next_scheduled().', 'acrossai-core-abilities' ),
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
						'args' => array( 'type' => 'array' ),
					),
					'required'             => array( 'hook' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'hook'       => array( 'type' => 'string' ),
						'scheduled'  => array( 'type' => 'boolean' ),
						'timestamp'  => array( 'type' => array( 'integer', 'null' ) ),
						'datetime'   => array( 'type' => array( 'string', 'null' ) ),
						'seconds_until' => array( 'type' => array( 'integer', 'null' ) ),
						'message'    => array( 'type' => 'string' ),
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

		$args      = isset( $input['args'] ) && is_array( $input['args'] ) ? $input['args'] : array();
		$timestamp = wp_next_scheduled( $hook, $args );

		if ( false === $timestamp ) {
			return array(
				'success'       => true,
				'hook'          => $hook,
				'scheduled'     => false,
				'timestamp'     => null,
				'datetime'      => null,
				'seconds_until' => null,
			);
		}

		return array(
			'success'       => true,
			'hook'          => $hook,
			'scheduled'     => true,
			'timestamp'     => (int) $timestamp,
			'datetime'      => gmdate( 'c', (int) $timestamp ),
			'seconds_until' => (int) $timestamp - (int) time(),
		);
	}
}
