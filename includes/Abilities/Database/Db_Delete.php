<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Db_Delete extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/db-delete',
			'args' => array(
				'label'               => __( 'Delete Rows', 'acrossai-core-abilities' ),
				'description'         => __( 'Deletes rows matching the where clause using $wpdb->delete() (values are auto-escaped). Requires a non-empty where to prevent accidental full-table deletion.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-database',
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
						'where'        => array(
							'type'        => 'object',
							'description' => __( 'Column → value conditions (AND-joined). Must be non-empty.', 'acrossai-core-abilities' ),
						),
						'where_format' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string', 'enum' => array( '%s', '%d', '%f' ) ),
							'description' => __( 'Optional format per where column.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'table', 'where' ),
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
		$where        = $input['where'] ?? array();
		$where_format = $input['where_format'] ?? null;

		if ( '' === $table ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'table is required.', 'acrossai-core-abilities' ) );
		}

		if ( empty( $where ) || ! is_array( $where ) ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'where must be a non-empty object to prevent full-table deletion.', 'acrossai-core-abilities' ) );
		}

		// Validate table exists.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		// phpcs:enable
		if ( ! in_array( $table, (array) $tables, true ) ) {
			return array( 'success' => false, 'rows_affected' => 0, 'message' => __( 'Table not found in the database.', 'acrossai-core-abilities' ) );
		}

		$result = $wpdb->delete( $table, $where, $where_format );

		if ( false === $result ) {
			return array(
				'success'       => false,
				'rows_affected' => 0,
				'message'       => $wpdb->last_error ?: __( 'Delete failed.', 'acrossai-core-abilities' ),
			);
		}

		return array(
			'success'       => true,
			'rows_affected' => (int) $result,
		);
	}
}
