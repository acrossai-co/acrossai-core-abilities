<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Cron_Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Remove a persisted custom schedule. Built-in WordPress schedules (hourly,
 * twicedaily, daily, weekly) and schedules added by other plugins are
 * untouched — this only removes entries we wrote into our own option.
 */
class Cron_Delete_Schedule extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/cron-delete-schedule',
			'args' => array(
				'label'               => __( 'Delete Custom Schedule', 'acrossai-core-abilities' ),
				'description'         => __( 'Remove a custom schedule previously registered by cron-create-schedule. Built-in and plugin-defined schedules are not affected.', 'acrossai-core-abilities' ),
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
						'name' => array( 'type' => 'string' ),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'name'    => array( 'type' => 'string' ),
						'removed' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
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
		$name = sanitize_key( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array( 'success' => false, 'message' => __( 'name is required.', 'acrossai-core-abilities' ) );
		}

		$custom = Cron_Helpers::get_custom();
		if ( ! isset( $custom[ $name ] ) ) {
			return array(
				'success' => true,
				'name'    => $name,
				'removed' => false,
				/* translators: %s: schedule name */
				'message' => sprintf( __( 'No custom schedule "%s" to remove.', 'acrossai-core-abilities' ), $name ),
			);
		}

		Cron_Helpers::remove_custom( $name );

		return array(
			'success' => true,
			'name'    => $name,
			'removed' => true,
			/* translators: %s: schedule name */
			'message' => sprintf( __( 'Removed custom schedule "%s".', 'acrossai-core-abilities' ), $name ),
		);
	}
}
