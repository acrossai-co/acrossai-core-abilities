<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Enumerate registered post types via get_post_types() with `objects` output.
 * The result includes slug, labels, hierarchical flag, public flag, REST base,
 * and supported features — enough to drive a UI picker.
 */
class List_Post_Types extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/list-post-types',
			'args' => array(
				'label'               => __( 'List Post Types', 'acrossai-core-abilities' ),
				'description'         => __( 'List registered post types via get_post_types( objects ). Filterable by public/show_in_rest/hierarchical flags.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'cpt',
				'sub_group_label'     => __( 'Custom Post Types', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'public'        => array( 'type' => 'boolean' ),
						'show_in_rest'  => array( 'type' => 'boolean' ),
						'hierarchical'  => array( 'type' => 'boolean' ),
						'builtin_only'  => array( 'type' => 'boolean', 'default' => false ),
						'exclude_builtin' => array( 'type' => 'boolean', 'default' => false ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'types'   => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$args = array();
		foreach ( array( 'public', 'show_in_rest', 'hierarchical' ) as $flag ) {
			if ( isset( $input[ $flag ] ) ) {
				$args[ $flag ] = (bool) $input[ $flag ];
			}
		}
		if ( ! empty( $input['builtin_only'] ) ) {
			$args['_builtin'] = true;
		} elseif ( ! empty( $input['exclude_builtin'] ) ) {
			$args['_builtin'] = false;
		}

		$types = get_post_types( $args, 'objects' );
		$out   = array();
		foreach ( $types as $slug => $obj ) {
			$out[] = array(
				'slug'         => (string) $slug,
				'label'        => isset( $obj->label ) ? (string) $obj->label : (string) $slug,
				'name'         => isset( $obj->labels->name ) ? (string) $obj->labels->name : '',
				'public'       => (bool) $obj->public,
				'hierarchical' => (bool) $obj->hierarchical,
				'show_in_rest' => (bool) $obj->show_in_rest,
				'rest_base'    => isset( $obj->rest_base ) ? (string) $obj->rest_base : '',
				'supports'     => array_keys( (array) get_all_post_type_supports( $slug ) ),
				'builtin'      => (bool) $obj->_builtin,
			);
		}

		return array(
			'success' => true,
			'types'   => $out,
			'total'   => count( $out ),
		);
	}
}
