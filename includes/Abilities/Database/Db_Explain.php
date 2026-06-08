<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Db_Explain extends Ability_Definition {

	protected function main_key(): string {
		return 'acrossai-core-database';
	}

	protected function main_key_label(): string {
		return __( 'Acrossai Core Database', 'acrossai-core-abilities' );
	}

	protected function sub_key(): string {
		return 'db-explain';
	}

	protected function sub_key_label(): string {
		return __( 'Explain Query', 'acrossai-core-abilities' );
	}

	protected function ability(): array {
		return array(
			'name' => 'wp-agentic-admin/db-explain',
			'args' => array(
				'label'               => __( 'Explain Query', 'acrossai-core-abilities' ),
				'description'         => __( 'Runs EXPLAIN on a SELECT query and returns the MySQL query execution plan. Useful for diagnosing slow queries.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-database',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'sql' => array(
							'type'        => 'string',
							'description' => __( 'SELECT query to explain.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'sql' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'plan'    => array( 'type' => 'array' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'            => array( 'success', 'plan' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		global $wpdb;

		$sql = trim( $input['sql'] ?? '' );

		if ( '' === $sql ) {
			return array( 'success' => false, 'plan' => array(), 'message' => __( 'sql is required.', 'acrossai-core-abilities' ) );
		}

		// Only allow SELECT to be EXPLAINed via this ability.
		$stripped = preg_replace( '/\/\*.*?\*\/|--[^\n]*|#[^\n]*/s', '', $sql );
		preg_match( '/^\s*(\w+)/i', $stripped, $m );
		$first_keyword = strtoupper( $m[1] ?? '' );

		if ( 'SELECT' !== $first_keyword ) {
			return array(
				'success' => false,
				'plan'    => array(),
				'message' => __( 'Only SELECT queries can be explained via this ability.', 'acrossai-core-abilities' ),
			);
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$plan = $wpdb->get_results( 'EXPLAIN ' . $sql, ARRAY_A );
		// phpcs:enable

		if ( null === $plan ) {
			return array(
				'success' => false,
				'plan'    => array(),
				'message' => $wpdb->last_error ?: __( 'EXPLAIN returned null.', 'acrossai-core-abilities' ),
			);
		}

		return array( 'success' => true, 'plan' => $plan );
	}
}
