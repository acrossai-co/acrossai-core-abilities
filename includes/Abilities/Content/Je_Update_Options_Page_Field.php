<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Jet_Engine_Helpers;

defined( 'ABSPATH' ) || exit;

class Je_Update_Options_Page_Field extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/je-update-options-page-field',
			'args' => array(
				'label'               => __( 'Update Options Page Field', 'acrossai-core-abilities' ),
				'description'         => __( 'Write a single field value into a Jet Engine options page. The field value is stored inside the page\'s wp_options row.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'options-pages',
				'sub_group_label'     => __( 'Options Pages', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'slug'  => array( 'type' => 'string' ),
						'field' => array( 'type' => 'string' ),
						'value' => array( 'type' => array( 'string', 'integer', 'number', 'boolean', 'array', 'object', 'null' ) ),
					),
					'required'             => array( 'slug', 'field' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'slug'    => array( 'type' => 'string' ),
						'field'   => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$slug  = sanitize_key( (string) ( $input['slug'] ?? '' ) );
		$field = sanitize_key( (string) ( $input['field'] ?? '' ) );
		if ( '' === $slug || '' === $field ) {
			return array(
				'success' => false,
				'message' => __( 'slug and field are required.', 'acrossai-core-abilities' ),
			);
		}

		$result = Jet_Engine_Helpers::update_field( $slug, $field, $input['value'] ?? '' );
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'slug'    => $slug,
			'field'   => $field,
			/* translators: 1: field, 2: slug */
			'message' => sprintf( __( 'Updated field "%1$s" on options page "%2$s".', 'acrossai-core-abilities' ), $field, $slug ),
		);
	}
}
