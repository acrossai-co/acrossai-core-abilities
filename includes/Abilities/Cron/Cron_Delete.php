<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Delete a single scheduled event. WP-Cron identifies events by hook + args +
 * timestamp, so the caller may supply timestamp explicitly or rely on
 * wp_next_scheduled() to find the next instance.
 */
class Cron_Delete extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-delete',
			'args' => array(
				'label'               => __( 'Delete Cron Job', 'acrossai-core-abilities' ),
				'description'         => __( 'Unschedule a single event via wp_unschedule_event(). If timestamp is omitted, the next scheduled run for the hook+args is used.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-cron',
				'sub_group'           => 'delete',
				'sub_group_label'     => __( 'Delete Cron Jobs', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'hook'      => array( 'type' => 'string' ),
						'timestamp' => array( 'type' => 'integer' ),
						'args'      => array( 'type' => 'array' ),
					),
					'required'             => array( 'hook' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'   => array( 'type' => 'boolean' ),
						'hook'      => array( 'type' => 'string' ),
						'timestamp' => array( 'type' => 'integer' ),
						'message'   => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => true, 'idempotent' => true ),
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
		$timestamp = isset( $input['timestamp'] ) ? (int) $input['timestamp'] : 0;

		if ( $timestamp <= 0 ) {
			$timestamp = (int) wp_next_scheduled( $hook, $args );
			if ( $timestamp <= 0 ) {
				return array(
					'success' => false,
					/* translators: %s: hook name */
					'message' => sprintf( __( 'No scheduled event for hook "%s" with the given args.', 'acrossai-core-abilities' ), $hook ),
				);
			}
		}

		$result = wp_unschedule_event( $timestamp, $hook, $args, true );
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}
		if ( false === $result ) {
			return array(
				'success' => false,
				'message' => __( 'Could not unschedule the event.', 'acrossai-core-abilities' ),
			);
		}

		return array(
			'success'   => true,
			'hook'      => $hook,
			'timestamp' => $timestamp,
			/* translators: 1: hook, 2: timestamp */
			'message'   => sprintf( __( 'Unscheduled "%1$s" at %2$d.', 'acrossai-core-abilities' ), $hook, $timestamp ),
		);
	}
}
