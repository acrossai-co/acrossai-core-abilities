<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Content;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Multilang_Helpers;

defined( 'ABSPATH' ) || exit;

class Link_Post_Translation extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/link-post-translation',
			'args' => array(
				'label'               => __( 'Link Post Translations', 'acrossai-core-abilities' ),
				'description'         => __( 'Group two or more posts as translations of each other. Pass a map of language code → post ID. Polylang uses pll_save_post_translations(); WPML links each post to the same trid.', 'acrossai-core-abilities' ),
				'tab_group'           => 'core',
				'category'            => 'acrossai-core-abilities-content',
				'sub_group'           => 'multilanguage',
				'sub_group_label'     => __( 'Multilanguage', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'translations' => array(
							'type'        => 'object',
							'description' => __( 'Map of language code → post ID, e.g. { "en": 5, "fr": 9 }.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'translations' ),
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
					'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$raw = $input['translations'] ?? array();
		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return array(
				'success' => false,
				'message' => __( 'translations must be a non-empty language→ID map.', 'acrossai-core-abilities' ),
			);
		}

		$clean = array();
		foreach ( $raw as $lang => $id ) {
			$slug = sanitize_key( (string) $lang );
			$pid  = (int) $id;
			if ( '' === $slug || $pid <= 0 || ! get_post( $pid ) ) {
				return array(
					'success' => false,
					/* translators: 1: language slug, 2: post ID */
					'message' => sprintf( __( 'Invalid entry "%1$s" → %2$d.', 'acrossai-core-abilities' ), $lang, $pid ),
				);
			}
			$clean[ $slug ] = $pid;
		}

		$result = Multilang_Helpers::link_translations( $clean );
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success'      => true,
			'driver'       => Multilang_Helpers::detect(),
			'translations' => $clean,
			'message'      => __( 'Translations linked.', 'acrossai-core-abilities' ),
		);
	}
}
