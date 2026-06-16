<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Multilang_Helpers;

defined( 'ABSPATH' ) || exit;

class Get_Post_Translations extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'ewpa/get-post-translations',
			'args' => array(
				'label'               => __( 'Get Post Translations', 'acrossai-core-abilities' ),
				'description'         => __( 'Return the translations of a post (language code → post ID). Detects Polylang first, then WPML; errors if neither is active.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'multilanguage',
				'sub_group_label'     => __( 'Multilanguage', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'driver'       => array( 'type' => 'string' ),
						'translations' => array( 'type' => 'object' ),
						'message'      => array( 'type' => 'string' ),
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
		$post_id = (int) ( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Post not found.', 'acrossai-core-abilities' ),
			);
		}

		$result = Multilang_Helpers::get_translations( $post_id );
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success'      => true,
			'driver'       => Multilang_Helpers::detect(),
			'translations' => $result,
		);
	}
}
