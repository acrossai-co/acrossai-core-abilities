<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Page extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/get-page',
			'args' => array(
				'label'               => __( 'Get Page', 'acrossai-core-abilities' ),
				'description'         => __( 'Fetch a single page by ID via get_post(); errors if the post is not of type "page".', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'pages',
				'sub_group_label'     => __( 'Pages', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_pages' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'page'    => array( 'type' => 'object' ),
						'message' => array( 'type' => 'string' ),
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
		$id   = (int) ( $input['id'] ?? 0 );
		$page = $id > 0 ? get_post( $id, ARRAY_A ) : null;

		if ( ! $page || 'page' !== $page['post_type'] ) {
			return array(
				'success' => false,
				'message' => __( 'Page not found.', 'acrossai-core-abilities' ),
			);
		}

		return array(
			'success' => true,
			'page'    => $page,
		);
	}
}
