<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Tables_List extends Ability_Definition {

	protected function main_key(): string {
		return 'acrossai-core-database';
	}

	protected function main_key_label(): string {
		return __( 'Acrossai Core Database', 'acrossai-core-abilities' );
	}

	protected function sub_key(): string {
		return 'tables-list';
	}

	protected function sub_key_label(): string {
		return __( 'List Tables', 'acrossai-core-abilities' );
	}

	protected function ability(): array {
		return array(
			'name' => 'wp-agentic-admin/tables-list',
			'args' => array(
				'label'               => __( 'List Database Tables', 'acrossai-core-abilities' ),
				'description'         => __( 'Lists all tables in the database with engine, approximate row count, and storage size.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-database',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'tables'  => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'              => array( 'type' => 'string' ),
									'engine'            => array( 'type' => 'string' ),
									'row_count'         => array( 'type' => 'integer' ),
									'data_size_bytes'   => array( 'type' => 'integer' ),
									'index_size_bytes'  => array( 'type' => 'integer' ),
									'total_size_bytes'  => array( 'type' => 'integer' ),
								),
							),
						),
					),
					'required'            => array( 'success', 'tables' ),
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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			'SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
			 FROM information_schema.TABLES
			 WHERE TABLE_SCHEMA = DATABASE()
			 ORDER BY TABLE_NAME',
			ARRAY_A
		);
		// phpcs:enable

		$tables = array();
		foreach ( (array) $rows as $row ) {
			$data_size  = (int) $row['DATA_LENGTH'];
			$index_size = (int) $row['INDEX_LENGTH'];
			$tables[]   = array(
				'name'             => $row['TABLE_NAME'],
				'engine'           => $row['ENGINE'] ?? '',
				'row_count'        => (int) $row['TABLE_ROWS'],
				'data_size_bytes'  => $data_size,
				'index_size_bytes' => $index_size,
				'total_size_bytes' => $data_size + $index_size,
			);
		}

		return array( 'success' => true, 'tables' => $tables );
	}
}
