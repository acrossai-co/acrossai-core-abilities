<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Block_Info;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a single registered block by name. Returns every section the registry
 * can expose — settings, supports, attributes, example, variations, styles,
 * transforms — or just one when "section" is provided.
 *
 * Scenarios:
 *  - 6: full details for an exact name
 *  - 7: name not found → clear error, suggests block-info-list
 *  - 8–14: per-section reads via "section" input
 *  - 15: registry not loaded → clear error
 *  - 17: invalid section name → reject with allowed list
 *  - 18: namespace collision is surfaced as a warning (last-registered wins
 *    in WordPress and we report whoever currently holds the slot)
 *  - 19: deprecation flag passed through when present in the registered args
 *  - 20–23: missing example/variations/styles/transforms surface as
 *    available=false with an empty payload, never silent
 */
class Block_Info_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-info-read',
			'args' => array(
				'label'               => __( 'Read Block', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns full details for a single registered block. Pass "section" (settings, supports, attributes, example, variations, styles, transforms) to fetch one slice instead of the whole record. Block name must be in namespace/name form (e.g. core/paragraph).', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'sub_group'           => 'block-info',
				'sub_group_label'     => __( 'Block Info', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'    => array(
							'type'        => 'string',
							'description' => __( 'Block name (namespace/block-name, e.g. "core/paragraph").', 'acrossai-core-abilities' ),
						),
						'section' => array(
							'type'        => 'string',
							'enum'        => array_merge( array( '' ), Block_Info::SECTIONS ),
							'default'     => '',
							'description' => __( 'Return only this section.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'block'    => array( 'type' => 'object' ),
						'section'  => array( 'type' => 'string' ),
						'data'     => array(),
						'warnings' => array( 'type' => 'array' ),
						'message'  => array( 'type' => 'string' ),
					),
					'required'             => array( 'success' ),
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
		if ( ! Block_Info::registry_available() ) {
			return array(
				'success' => false,
				'message' => __( 'WP_Block_Type_Registry is not available yet. Call this ability after the WordPress init hook has fired.', 'acrossai-core-abilities' ),
			);
		}

		$name = trim( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return array(
				'success' => false,
				'message' => __( 'Block name is required (namespace/block-name format).', 'acrossai-core-abilities' ),
			);
		}

		$section_input = sanitize_text_field( $input['section'] ?? '' );
		if ( '' !== $section_input && ! Block_Info::valid_section( $section_input ) ) {
			return array(
				'success' => false,
				/* translators: %s: list of valid sections */
				'message' => sprintf( __( 'Invalid section. Allowed: %s.', 'acrossai-core-abilities' ), implode( ', ', Block_Info::SECTIONS ) ),
			);
		}

		$block = Block_Info::get_block( $name );
		if ( null === $block ) {
			return array(
				'success' => false,
				/* translators: %s: block name */
				'message' => sprintf( __( 'Block "%s" is not registered. Check the spelling, or use block-info-list to find the correct name.', 'acrossai-core-abilities' ), $name ),
			);
		}

		$warnings = $this->collect_warnings( $block );
		$summary  = Block_Info::summary( $block );

		if ( '' !== $section_input ) {
			$slice = Block_Info::section( $block, $section_input );
			return array(
				'success'  => true,
				'block'    => $summary,
				'section'  => (string) $slice['section'],
				'data'     => $slice['data'],
				'warnings' => $this->section_availability_warning( (string) $slice['section'], (bool) $slice['available'], $warnings ),
				'message'  => $slice['available']
					? ''
					/* translators: 1: section, 2: block name */
					: sprintf( __( 'No %1$s registered for block "%2$s".', 'acrossai-core-abilities' ), (string) $slice['section'], $name ),
			);
		}

		$full = Block_Info::full( $block );

		// Bake the "no X registered" hints into warnings so callers know which
		// sections are intentionally empty (Scenarios 20–23).
		$warnings = $this->compose_section_warnings( $full, $warnings );

		return array(
			'success'  => true,
			'block'    => $summary,
			'section'  => '',
			'data'     => $full,
			'warnings' => $warnings,
		);
	}

	/**
	 * Warnings about the block itself (deprecation + namespace conflict).
	 */
	private function collect_warnings( \WP_Block_Type $block ): array {
		$warnings = array();

		// Scenario 19 — deprecated marker if the registered args carry one.
		if ( property_exists( $block, 'deprecated' ) && ! empty( $block->deprecated ) ) {
			/* translators: %s: block name */
			$warnings[] = sprintf( __( 'Block "%s" is marked deprecated. Check the block\'s deprecated metadata for replacement guidance.', 'acrossai-core-abilities' ), (string) $block->name );
		}

		// Scenario 18 — heuristic namespace conflict: a core-named block whose
		// source resolves to non-core means a plugin/theme overrode it.
		if ( 0 === strpos( (string) $block->name, 'core/' ) ) {
			$source = Block_Info::classify_source( $block );
			if ( 'core' !== $source ) {
				/* translators: 1: block name, 2: source */
				$warnings[] = sprintf( __( 'Block "%1$s" uses the core/* namespace but the currently registered copy was last registered by %2$s. The last registration wins in WordPress.', 'acrossai-core-abilities' ), (string) $block->name, $source );
			}
		}

		return $warnings;
	}

	private function section_availability_warning( string $section, bool $available, array $warnings ): array {
		if ( $available ) {
			return $warnings;
		}
		/* translators: %s: section name */
		$warnings[] = sprintf( __( 'No %s registered for this block.', 'acrossai-core-abilities' ), $section );
		return $warnings;
	}

	private function compose_section_warnings( array $full, array $warnings ): array {
		if ( null === ( $full['example'] ?? null ) ) {
			$warnings[] = __( 'No example registered for this block.', 'acrossai-core-abilities' );
		}
		foreach ( array( 'variations', 'styles', 'transforms' ) as $section ) {
			if ( empty( $full[ $section ] ) ) {
				/* translators: %s: section name */
				$warnings[] = sprintf( __( 'No %s registered for this block.', 'acrossai-core-abilities' ), $section );
			}
		}
		return $warnings;
	}
}
