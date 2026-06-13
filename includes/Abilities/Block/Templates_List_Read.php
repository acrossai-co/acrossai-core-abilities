<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

/**
 * Lists block templates by merging the active theme's /templates/*.html files
 * with overrides stored in the wp_template CPT by the Site Editor.
 *
 * Backed by get_block_templates() so the "source" column tells you whether
 * a template is the raw theme file or a Site Editor override in the database.
 */
class Templates_List_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/templates-list-read',
			'args' => array(
				'label'               => __( 'List Block Templates', 'acrossai-core-abilities' ),
				'description'         => __( 'Returns all block templates (wp_template) merged across sources: the theme\'s /templates/*.html files and the wp_template CPT overrides stored in the database by the Site Editor. Each row exposes its origin so a caller can tell a Site Editor override from the raw theme file.', 'acrossai-core-abilities' ),
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
							'description' => __( '"theme" = file-based only, "custom" = Site Editor overrides only, "all" = merged.', 'acrossai-core-abilities' ),
						),
						'slug'            => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Optional exact slug to look up (e.g. "single", "page", "index").', 'acrossai-core-abilities' ),
						),
						'include_content' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Include the block markup body in the response.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'   => array( 'type' => 'boolean' ),
						'theme'     => array( 'type' => 'string' ),
						'source'    => array( 'type' => 'string' ),
						'templates' => array( 'type' => 'array' ),
						'total'     => array( 'type' => 'integer' ),
						'message'   => array( 'type' => 'string' ),
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
		$slug            = sanitize_text_field( $input['slug'] ?? '' );
		$include_content = ! isset( $input['include_content'] ) || (bool) $input['include_content'];

		$query = array();
		if ( '' !== $theme_slug ) {
			$query['theme'] = $theme_slug;
		}
		if ( '' !== $slug ) {
			$query['slug__in'] = array( $slug );
		}

		$templates = get_block_templates( $query, 'wp_template' );

		if ( 'all' !== $source ) {
			$templates = array_values(
				array_filter(
					$templates,
					static function ( $tpl ) use ( $source ): bool {
						return isset( $tpl->source ) && $tpl->source === $source;
					}
				)
			);
		}

		$rows = array();
		foreach ( $templates as $tpl ) {
			$row = array(
				'id'             => $tpl->id ?? '',
				'slug'           => $tpl->slug ?? '',
				'title'          => is_object( $tpl->title ?? null ) ? ( $tpl->title->rendered ?? '' ) : (string) ( $tpl->title ?? '' ),
				'description'    => (string) ( $tpl->description ?? '' ),
				'source'         => (string) ( $tpl->source ?? '' ),
				'origin'         => (string) ( $tpl->origin ?? '' ),
				'theme'          => (string) ( $tpl->theme ?? '' ),
				'status'         => (string) ( $tpl->status ?? '' ),
				'has_theme_file' => (bool) ( $tpl->has_theme_file ?? false ),
				'is_custom'      => (bool) ( $tpl->is_custom ?? false ),
				'wp_id'          => isset( $tpl->wp_id ) ? (int) $tpl->wp_id : 0,
			);

			if ( $include_content ) {
				$row['content'] = (string) ( $tpl->content ?? '' );
			}

			$rows[] = $row;
		}

		return array(
			'success'   => true,
			'theme'     => '' !== $theme_slug ? $theme_slug : (string) get_stylesheet(),
			'source'    => $source,
			'templates' => $rows,
			'total'     => count( $rows ),
		);
	}
}
