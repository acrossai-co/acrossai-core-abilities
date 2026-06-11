<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Database;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Db_Select extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/db-select',
			'args' => array(
				'label'               => __( 'Run SELECT Query', 'acrossai-core-abilities' ),
				'description'         => __( 'Executes a read-only SQL query (SELECT, SHOW, DESCRIBE, EXPLAIN). Write statements are rejected. Results are capped by the limit parameter.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-database',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'sql'   => array(
							'type'        => 'string',
							'description' => __( 'SQL query to execute. Must start with SELECT, SHOW, DESCRIBE, DESC, or EXPLAIN.', 'acrossai-core-abilities' ),
						),
						'limit' => array(
							'type'        => 'integer',
							'default'     => 1000,
							'minimum'     => 1,
							'maximum'     => 10000,
							'description' => __( 'Maximum rows to return (1–10000, default 1000). Appended as LIMIT if the query does not already contain one.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'sql' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'   => array( 'type' => 'boolean' ),
						'rows'      => array( 'type' => 'array' ),
						'row_count' => array( 'type' => 'integer' ),
						'truncated' => array( 'type' => 'boolean' ),
						'message'   => array( 'type' => 'string' ),
					),
					'required'            => array( 'success', 'rows', 'row_count', 'truncated' ),
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

		$sql   = isset( $input['sql'] ) ? trim( $input['sql'] ) : '';
		$limit = isset( $input['limit'] ) ? min( (int) $input['limit'], 10000 ) : 1000;

		if ( '' === $sql ) {
			return array( 'success' => false, 'rows' => array(), 'row_count' => 0, 'truncated' => false, 'message' => __( 'sql is required.', 'acrossai-core-abilities' ) );
		}

		// Verb guard: strip SQL comments then check the first keyword.
		$stripped = preg_replace( '/\/\*.*?\*\/|--[^\n]*|#[^\n]*/s', '', $sql );
		preg_match( '/^\s*(\w+)/i', $stripped, $m );
		$first_keyword  = strtoupper( $m[1] ?? '' );
		$allowed_verbs  = array( 'SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN' );

		if ( ! in_array( $first_keyword, $allowed_verbs, true ) ) {
			return array(
				'success'   => false,
				'rows'      => array(),
				'row_count' => 0,
				'truncated' => false,
				'message'   => sprintf(
					/* translators: %s: comma-separated list of allowed SQL verbs */
					__( 'Only %s queries are permitted.', 'acrossai-core-abilities' ),
					implode( ', ', $allowed_verbs )
				),
			);
		}

		// Append LIMIT if absent and requested.
		if ( $limit > 0 && ! preg_match( '/\bLIMIT\b/i', $sql ) ) {
			$sql = rtrim( $sql, '; ' ) . ' LIMIT ' . $limit;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		// phpcs:enable

		if ( null === $rows ) {
			return array(
				'success'   => false,
				'rows'      => array(),
				'row_count' => 0,
				'truncated' => false,
				'message'   => $wpdb->last_error ?: __( 'Query returned null.', 'acrossai-core-abilities' ),
			);
		}

		$row_count = count( $rows );

		return array(
			'success'   => true,
			'rows'      => $rows,
			'row_count' => $row_count,
			'truncated' => $row_count === $limit,
		);
	}
}
