<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Lists template parts by merging the active theme's /parts/*.html files
 * with overrides stored in the wp_template_part CPT by the Site Editor.
 *
 * Backed by get_block_templates() so the "source" column tells you whether
 * a part is the raw theme file or a Site Editor override in the database.
 */
class Template_Parts_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/template-parts-read',
			'args' => array(
				'label'               => __( 'List Block Template Parts', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns all block template parts (wp_template_part) merged across sources: the theme\'s /parts/*.html files and the wp_template_part CPT overrides stored in the database by the Site Editor. Each row exposes its origin and area (header / footer / sidebar / uncategorized).', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_theme_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme_slug'      => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Restrict to one theme (folder name). Defaults to the active theme.', 'acrossai-core-abilities' ),
						),
						'source'          => array(
							'type'        => 'string',
							'enum'        => array( 'all', 'theme', 'custom' ),
							'default'     => 'all',
						),
						'area'            => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Filter by template-part area: "header", "footer", "sidebar", "uncategorized", or empty for all.', 'acrossai-core-abilities' ),
						),
						'slug'            => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Optional exact slug to look up.', 'acrossai-core-abilities' ),
						),
						'include_content' => array(
							'type'    => 'boolean',
							'default' => true,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'theme'   => array( 'type' => 'string' ),
						'source'  => array( 'type' => 'string' ),
						'area'    => array( 'type' => 'string' ),
						'parts'   => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success' ),
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
		if ( ! function_exists( 'get_block_templates' ) ) {
			return array( 'success' => false, 'message' => __( 'Block templates require WordPress 5.9 or later.', 'acrossai-core-abilities' ) );
		}

		$theme_slug      = sanitize_text_field( $input['theme_slug'] ?? '' );
		$source          = sanitize_text_field( $input['source'] ?? 'all' );
		$area            = sanitize_text_field( $input['area'] ?? '' );
		$slug            = sanitize_text_field( $input['slug'] ?? '' );
		$include_content = ! isset( $input['include_content'] ) || (bool) $input['include_content'];

		$query = array();
		if ( '' !== $theme_slug ) {
			$query['theme'] = $theme_slug;
		}
		if ( '' !== $slug ) {
			$query['slug__in'] = array( $slug );
		}
		if ( '' !== $area ) {
			$query['area'] = $area;
		}

		$parts = get_block_templates( $query, 'wp_template_part' );

		if ( 'all' !== $source ) {
			$parts = array_values(
				array_filter(
					$parts,
					static function ( $part ) use ( $source ): bool {
						return isset( $part->source ) && $part->source === $source;
					}
				)
			);
		}

		$rows = array();
		foreach ( $parts as $part ) {
			$row = array(
				'id'             => $part->id ?? '',
				'slug'           => $part->slug ?? '',
				'title'          => is_object( $part->title ?? null ) ? ( $part->title->rendered ?? '' ) : (string) ( $part->title ?? '' ),
				'description'    => (string) ( $part->description ?? '' ),
				'area'           => (string) ( $part->area ?? '' ),
				'source'         => (string) ( $part->source ?? '' ),
				'origin'         => (string) ( $part->origin ?? '' ),
				'theme'          => (string) ( $part->theme ?? '' ),
				'status'         => (string) ( $part->status ?? '' ),
				'has_theme_file' => (bool) ( $part->has_theme_file ?? false ),
				'is_custom'      => (bool) ( $part->is_custom ?? false ),
				'wp_id'          => isset( $part->wp_id ) ? (int) $part->wp_id : 0,
			);

			if ( $include_content ) {
				$row['content'] = (string) ( $part->content ?? '' );
			}

			$rows[] = $row;
		}

		return array(
			'success' => true,
			'theme'   => '' !== $theme_slug ? $theme_slug : (string) get_stylesheet(),
			'source'  => $source,
			'area'    => $area,
			'parts'   => $rows,
			'total'   => count( $rows ),
		);
	}
}
