<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Settings;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the WordPress permalink structure. Accepts either a named preset
 * (plain / day-and-name / month-and-name / numeric / post-name) OR a custom
 * structure string built from the standard tags (%year%, %monthnum%,
 * %postname%, %post_id%, etc.).
 *
 * Automatically flushes rewrite rules afterwards unless flush=false. Optionally
 * sets category_base and tag_base in the same call.
 */
class Permalink_Set extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/permalink-set',
			'args' => array(
				'label'               => __( 'Set Permalink Structure', 'acrossai-core-abilities' ),
				'description'         => __( 'Sets permalink_structure. "structure" accepts a preset name (plain, day-and-name, month-and-name, numeric, post-name) or a custom structure string like "/%year%/%postname%/". Rewrite rules are flushed automatically. category_base and tag_base are optional.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-settings',
				'sub_group'           => 'permalinks',
				'sub_group_label'     => __( 'Permalinks', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'structure'     => array(
							'type'        => 'string',
							'description' => __( 'Preset name (plain, day-and-name, month-and-name, numeric, post-name) or a custom structure string.', 'acrossai-core-abilities' ),
						),
						'category_base' => array(
							'type'        => 'string',
							'description' => __( 'Optional category permalink base. Leave empty to keep current value.', 'acrossai-core-abilities' ),
						),
						'tag_base'      => array(
							'type'        => 'string',
							'description' => __( 'Optional tag permalink base. Leave empty to keep current value.', 'acrossai-core-abilities' ),
						),
						'flush'         => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Whether to flush rewrite rules after the update.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'structure' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'           => array( 'type' => 'boolean' ),
						'message'           => array( 'type' => 'string' ),
						'structure'         => array( 'type' => 'string' ),
						'structure_preset'  => array( 'type' => 'string' ),
						'previous_structure' => array( 'type' => 'string' ),
						'category_base'     => array( 'type' => 'string' ),
						'tag_base'          => array( 'type' => 'string' ),
						'flushed'           => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'success' ),
					'additionalProperties' => false,
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true, 'type' => 'tool' ),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		if ( ! array_key_exists( 'structure', $input ) ) {
			return array(
				'success' => false,
				'message' => __( 'A "structure" value is required (preset name or custom string).', 'acrossai-core-abilities' ),
			);
		}

		$raw = trim( (string) $input['structure'] );

		// Resolve preset → structure, or accept a custom string verbatim.
		$structure = Permalink_Presets::resolve( $raw );

		// Validate the structure (empty is fine — that's "plain").
		$valid = Permalink_Presets::validate( $structure );
		if ( is_wp_error( $valid ) ) {
			return array(
				'success' => false,
				'message' => $valid->get_error_message(),
			);
		}

		$previous = (string) get_option( 'permalink_structure', '' );

		global $wp_rewrite;
		if ( $wp_rewrite instanceof \WP_Rewrite ) {
			$wp_rewrite->set_permalink_structure( $structure );
		} else {
			update_option( 'permalink_structure', $structure );
		}

		// category_base / tag_base — only update when explicitly provided.
		if ( array_key_exists( 'category_base', $input ) ) {
			$category_base = sanitize_text_field( (string) $input['category_base'] );
			if ( $wp_rewrite instanceof \WP_Rewrite ) {
				$wp_rewrite->set_category_base( $category_base );
			} else {
				update_option( 'category_base', $category_base );
			}
		}
		if ( array_key_exists( 'tag_base', $input ) ) {
			$tag_base = sanitize_text_field( (string) $input['tag_base'] );
			if ( $wp_rewrite instanceof \WP_Rewrite ) {
				$wp_rewrite->set_tag_base( $tag_base );
			} else {
				update_option( 'tag_base', $tag_base );
			}
		}

		$flush   = ! isset( $input['flush'] ) || (bool) $input['flush'];
		$flushed = false;
		if ( $flush ) {
			flush_rewrite_rules( false );
			$flushed = true;
		}

		return array(
			'success'            => true,
			/* translators: %s: new permalink structure */
			'message'            => '' === $structure
				? __( 'Permalink structure set to "plain".', 'acrossai-core-abilities' )
				: sprintf( __( 'Permalink structure set to "%s".', 'acrossai-core-abilities' ), $structure ),
			'structure'          => $structure,
			'structure_preset'   => Permalink_Presets::match( $structure ),
			'previous_structure' => $previous,
			'category_base'      => (string) get_option( 'category_base', '' ),
			'tag_base'           => (string) get_option( 'tag_base', '' ),
			'flushed'            => $flushed,
		);
	}
}
