<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Db_Insert extends Ability_Definition {

	protected function main_key(): string {
		return 'acrossai-core-database';
	}

	protected function main_key_label(): string {
		return __( 'Acrossai Core Database', 'acrossai-core-abilities' );
	}

	protected function sub_key(): string {
		return 'db-insert';
	}

	protected function sub_key_label(): string {
		return __( 'Insert Row', 'acrossai-core-abilities' );
	}

	protected function ability(): array {
		return array(
			'name' => 'wp-agentic-admin/db-insert',
			'args' => array(
				'label'               => __( 'Insert Row', 'acrossai-core-abilities' ),
				'description'         => __( 'Inserts a single row into a database table using $wpdb->insert() (values are auto-escaped). Not idempotent — each call adds a new row.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-database',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'table'  => array(
							'type'        => 'string',
							'description' => __( 'Target table name (must exist in the database).', 'acrossai-core-abilities' ),
						),
						'data'   => array(
							'type'        => 'object',
							'description' => __( 'Column → value map for the new row.', 'acrossai-core-abilities' ),
						),
						'format' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string', 'enum' => array( '%s', '%d', '%f' ) ),
							'description' => __( 'Optional format per column (%s string, %d integer, %f float). Defaults to %s for each column.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'table', 'data' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'insert_id'     => array( 'type' => 'integer' ),
						'rows_affected' => array( 'type' => 'integer' ),
						'message'       => array( 'type' => 'string' ),
					),
					'required'            => array( 'success', 'insert_id', 'rows_affected' ),
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
						'idempotent'  => false,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		global $wpdb;

		$table  = sanitize_text_field( $input['table'] ?? '' );
		$data   = $input['data'] ?? array();
		$format = $input['format'] ?? null;

		if ( '' === $table ) {
			return array( 'success' => false, 'insert_id' => 0, 'rows_affected' => 0, 'message' => __( 'table is required.', 'acrossai-core-abilities' ) );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array( 'success' => false, 'insert_id' => 0, 'rows_affected' => 0, 'message' => __( 'data must be a non-empty object.', 'acrossai-core-abilities' ) );
		}

		// Validate table exists.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		// phpcs:enable
		if ( ! in_array( $table, (array) $tables, true ) ) {
			return array( 'success' => false, 'insert_id' => 0, 'rows_affected' => 0, 'message' => __( 'Table not found in the database.', 'acrossai-core-abilities' ) );
		}

		$result = $wpdb->insert( $table, $data, $format );

		if ( false === $result ) {
			return array(
				'success'       => false,
				'insert_id'     => 0,
				'rows_affected' => 0,
				'message'       => $wpdb->last_error ?: __( 'Insert failed.', 'acrossai-core-abilities' ),
			);
		}

		return array(
			'success'       => true,
			'insert_id'     => (int) $wpdb->insert_id,
			'rows_affected' => (int) $result,
		);
	}
}
