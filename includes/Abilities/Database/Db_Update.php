<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Db_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/db-update',
			'args' => array(
				'label'               => __( 'Update Rows', 'acrossai-core-abilities' ),
				'description'         => __( 'Updates rows matching the where clause using $wpdb->update() (values are auto-escaped). Requires a non-empty where to prevent accidental full-table updates.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-database',
				'sub_group'           => 'queries',
				'sub_group_label'     => __( 'Queries', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'table'        => array(
							'type'        => 'string',
							'description' => __( 'Target table name.', 'acrossai-core-abilities' ),
						),
						'data'         => array(
							'type'        => 'object',
							'description' => __( 'Column → value map of fields to update.', 'acrossai-core-abilities' ),
						),
						'where'        => array(
							'type'        => 'object',
							'description' => __( 'Column → value conditions (AND-joined). Must be non-empty.', 'acrossai-core-abilities' ),
						),
						'data_format'  => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string', 'enum' => array( '%s', '%d', '%f' ) ),
							'description' => __( 'Optional format per data column.', 'acrossai-core-abilities' ),
						),
						'where_format' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string', 'enum' => array( '%s', '%d', '%f' ) ),
							'description' => __( 'Optional format per where column.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'table', 'data', 'where' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'rows_affected' => array( 'type' => 'integer' ),
						'message'       => array( 'type' => 'string' ),
					),
					'required'            => array( 'success', 'rows_affected' ),
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
						'destructive' => true,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		global $wpdb;

		$table        = sanitize_text_field( $input['table'] ?? '' );
		$data         = $input['data'] ?? array();
		$where        = $input['where'] ?? array();
		$data_format  = $input['data_format'] ?? null;
		$where_format = $input['where_format'] ?? null;

		if ( '' === $table ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'table is required.', 'acrossai-core-abilities' ) );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'data must be a non-empty object.', 'acrossai-core-abilities' ) );
		}

		if ( empty( $where ) || ! is_array( $where ) ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'where must be a non-empty object to prevent full-table updates.', 'acrossai-core-abilities' ) );
		}

		// Validate table exists.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		// phpcs:enable
		if ( ! in_array( $table, (array) $tables, true ) ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'Table not found in the database.', 'acrossai-core-abilities' ) );
		}

		$result = $wpdb->update( $table, $data, $where, $data_format, $where_format );

		if ( false === $result ) {
			return array(
				'success'       => false,
				'rows_affected' => 0,
				'message'       => $wpdb->last_error ?: __( 'Update failed.', 'acrossai-core-abilities' ),
			);
		}

		return array(
			'success'       => true,
			'rows_affected' => (int) $result,
		);
	}
}
