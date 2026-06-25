<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Reports whether DISABLE_WP_CRON / ALTERNATE_WP_CRON are set, the URL the
 * cron loopback hits, and the current server time so callers can sanity-check
 * UTC drift.
 */
class Cron_Status extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-status',
			'args' => array(
				'label'               => __( 'Get Cron Status', 'acrossai-core-abilities' ),
				'description'         => __( 'Report whether WP-Cron is disabled (DISABLE_WP_CRON) or running in alternate mode (ALTERNATE_WP_CRON), the wp-cron.php URL, and the current server timestamp.', 'acrossai-core-abilities' ),
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
						'success'         => array( 'type' => 'boolean' ),
						'disabled'        => array( 'type' => 'boolean' ),
						'alternate'       => array( 'type' => 'boolean' ),
						'cron_url'        => array( 'type' => 'string' ),
						'timestamp'       => array( 'type' => 'integer' ),
						'datetime'        => array( 'type' => 'string' ),
						'timezone_string' => array( 'type' => 'string' ),
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
		$now = (int) time();
		return array(
			'success'         => true,
			'disabled'        => defined( 'DISABLE_WP_CRON' ) && \DISABLE_WP_CRON,
			'alternate'       => defined( 'ALTERNATE_WP_CRON' ) && \ALTERNATE_WP_CRON,
			'cron_url'        => site_url( 'wp-cron.php' ),
			'timestamp'       => $now,
			'datetime'        => gmdate( 'c', $now ),
			'timezone_string' => (string) wp_timezone_string(),
		);
	}
}
