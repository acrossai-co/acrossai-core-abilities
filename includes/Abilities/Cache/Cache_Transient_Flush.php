<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cache;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Cache_Transient_Flush extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/transient-flush',
			'args' => array(
				'label'               => __( 'Flush Transients', 'acrossai-core-abilities' ),
				'description'         => __( 'Deletes WordPress transients. Use scope "expired" (default) to remove only expired transients, or "all" to remove every transient regardless of expiry.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-cache',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array( 'scope' => 'expired' ),
					'properties'           => array(
						'scope' => array(
							'type'        => 'string',
							'enum'        => array( 'expired', 'all' ),
							'default'     => 'expired',
							'description' => __( '"expired" deletes only expired transients; "all" deletes every transient.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'scope'   => array( 'type' => 'string' ),
						'deleted' => array( 'type' => 'integer', 'description' => __( 'Number of rows deleted (available for scope "all" only; -1 when not applicable).', 'acrossai-core-abilities' ) ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'scope', 'deleted', 'message' ),
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

		$scope = isset( $input['scope'] ) && 'all' === $input['scope'] ? 'all' : 'expired';

		if ( 'all' === $scope ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = (int) $wpdb->query(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE '\_transient\_%'
				    OR option_name LIKE '\_site\_transient\_%'"
			);
			// phpcs:enable

			return array(
				'success' => true,
				'scope'   => 'all',
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of rows deleted */
					_n( '%d transient deleted.', '%d transients deleted.', $deleted, 'acrossai-core-abilities' ),
					$deleted
				),
			);
		}

		delete_expired_transients( true );

		return array(
			'success' => true,
			'scope'   => 'expired',
			'deleted' => -1,
			'message' => __( 'Expired transients deleted.', 'acrossai-core-abilities' ),
		);
	}
}
