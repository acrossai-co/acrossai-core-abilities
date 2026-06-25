<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Db_Optimize extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/db-optimize',
			'args' => array(
				'label'               => __( 'Optimize Database Tables', 'acrossai-core-abilities' ),
				'description'         => __( 'Runs OPTIMIZE TABLE on the specified tables. Defaults to all WordPress-prefixed tables when no tables are provided. Reclaims unused space and defragments data files.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-database',
				'sub_group'           => 'maintenance',
				'sub_group_label'     => __( 'Maintenance', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(
						'tables' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Table names to optimize. Omit to optimize all WordPress-prefixed tables.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'results' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'table'   => array( 'type' => 'string' ),
									'op'      => array( 'type' => 'string' ),
									'status'  => array( 'type' => 'string' ),
									'message' => array( 'type' => 'string' ),
								),
							),
						),
					),
					'required'            => array( 'success', 'results' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		global $wpdb;

		$requested = isset( $input['tables'] ) && is_array( $input['tables'] ) ? $input['tables'] : array();

		if ( empty( $requested ) ) {
			// Default: all WP-prefixed tables.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$requested = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . '%' ) );
			// phpcs:enable
		}

		// Filter out any table names with backticks.
		$requested = array_filter( (array) $requested, static function ( $t ) {
			return is_string( $t ) && '' !== $t && strpos( $t, '`' ) === false;
		} );

		$results = array();

		foreach ( $requested as $table ) {
			$escaped = '`' . esc_sql( $table ) . '`';

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "OPTIMIZE TABLE {$escaped}", ARRAY_A );
			// phpcs:enable

			foreach ( (array) $rows as $row ) {
				$results[] = array(
					'table'   => $row['Table'] ?? $table,
					'op'      => $row['Op'] ?? 'optimize',
					'status'  => $row['Msg_type'] ?? '',
					'message' => $row['Msg_text'] ?? '',
				);
			}
		}

		return array( 'success' => true, 'results' => $results );
	}
}
