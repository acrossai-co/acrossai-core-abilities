<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Themes;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Theme_Helpers;

defined( 'ABSPATH' ) || exit;

class Theme_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/theme-list',
			'args' => array(
				'label'               => __( 'List Themes', 'acrossai-core-abilities' ),
				'description'         => __( 'List all installed WordPress themes, optionally filtered by status.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-themes',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'switch_themes' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'status' => array(
							'type'        => 'string',
							'enum'        => array( 'all', 'active', 'inactive' ),
							'default'     => 'all',
							'description' => __( 'Filter themes by status.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'themes' => array( 'type' => 'array' ),
						'total'  => array( 'type' => 'integer' ),
						'active' => array( 'type' => 'integer' ),
					),
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
		$status = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'all';

		if ( ! in_array( $status, array( 'all', 'active', 'inactive' ), true ) ) {
			$status = 'all';
		}

		return Theme_Helpers::get_all_themes( $status );
	}
}
