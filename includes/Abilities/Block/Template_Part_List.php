<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_Db;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_File;

defined( 'ABSPATH' ) || exit;

/**
 * Lists block template parts across every storage layer:
 *   - source=db      → wp_template_part CPT rows (Site Editor / DB-stored parts)
 *   - source=theme   → /parts/*.html files in the active theme (or theme_slug)
 *     theme_type=child  → only child theme files
 *     theme_type=parent → only parent theme files
 *     theme_type=theme  → only the single active theme (when no child is set)
 *   - source=plugin  → /parts/*.html files in installed plugins; optional plugin_slug
 *   - source=all     → union of all sources (default)
 *
 * Each row carries a "source" field plus an "effective" flag indicating which
 * location WordPress will actually serve at runtime (DB → child → parent → plugin).
 */
class Template_Part_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/template-part-list',
			'args' => array(
				'label'               => __( 'List Block Template Parts', 'acrossai-core-abilities' ),
				'description'         => __( 'Lists block template parts across the database (wp_template_part), the active theme\'s /parts/*.html, the parent theme, and installed plugin /parts dirs. Filter by source, theme_type, plugin_slug, area, or exact slug.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'sub_group'           => 'template-parts',
				'sub_group_label'     => __( 'Template Parts', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_theme_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'source'          => array(
							'type'    => 'string',
							'enum'    => array( 'all', 'db', 'theme', 'plugin' ),
							'default' => 'all',
						),
						'theme_type'      => array(
							'type'    => 'string',
							'enum'    => array( '', 'child', 'parent', 'theme' ),
							'default' => '',
						),
						'theme_slug'      => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Restrict file scan to a specific theme folder. Defaults to active theme.', 'acrossai-core-abilities' ),
						),
						'plugin_slug'     => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Restrict plugin scan to one plugin folder name.', 'acrossai-core-abilities' ),
						),
						'area'            => array(
							'type'        => 'string',
							'enum'        => array( '', 'header', 'footer', 'sidebar', 'uncategorized' ),
							'default'     => '',
							'description' => __( 'Filter by template-part area. Only applies to DB rows; file rows are returned regardless.', 'acrossai-core-abilities' ),
						),
						'slug'            => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Optional exact slug to look up.', 'acrossai-core-abilities' ),
						),
						'include_content' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'parts'   => array( 'type' => 'array' ),
						'total'   => array( 'type' => 'integer' ),
						'theme'   => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
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
		$source          = sanitize_text_field( $input['source'] ?? 'all' );
		$theme_type      = sanitize_text_field( $input['theme_type'] ?? '' );
		$theme_slug      = sanitize_key( $input['theme_slug'] ?? '' );
		$plugin_slug     = sanitize_key( $input['plugin_slug'] ?? '' );
		$area            = sanitize_text_field( $input['area'] ?? '' );
		$slug_filter     = sanitize_title( $input['slug'] ?? '' );
		$include_content = ! empty( $input['include_content'] );

		$active_theme = '' !== $theme_slug ? $theme_slug : (string) get_stylesheet();

		$rows = array();

		if ( 'all' === $source || 'db' === $source ) {
			$rows = array_merge( $rows, $this->collect_db( $active_theme, $area, $slug_filter, $include_content ) );
		}

		if ( 'all' === $source || 'theme' === $source ) {
			$rows = array_merge( $rows, $this->collect_theme( $theme_type, $theme_slug, $slug_filter, $include_content ) );
		}

		if ( 'all' === $source || 'plugin' === $source ) {
			$rows = array_merge( $rows, $this->collect_plugins( $plugin_slug, $slug_filter, $include_content ) );
		}

		$rows = $this->mark_effective( $rows );

		return array(
			'success' => true,
			'parts'   => $rows,
			'total'   => count( $rows ),
			'theme'   => $active_theme,
		);
	}

	private function collect_db( string $theme, string $area, string $slug_filter, bool $include_content ): array {
		$posts = Template_Part_Db::list_all( $theme, $area, 500 );
		$rows  = array();
		foreach ( $posts as $post ) {
			if ( '' !== $slug_filter && (string) $post->post_name !== $slug_filter ) {
				continue;
			}
			$row = Template_Part_Db::to_row( $post );
			if ( ! $include_content ) {
				unset( $row['content'] );
			}
			$rows[] = $row;
		}
		return $rows;
	}

	private function collect_theme( string $theme_type, string $theme_slug, string $slug_filter, bool $include_content ): array {
		$rows = array();

		$child_dir = Template_Part_File::get_child_theme_dir();
		$is_child  = null !== $child_dir;

		if ( '' === $theme_type || 'child' === $theme_type ) {
			if ( $is_child ) {
				$rows = array_merge( $rows, $this->scan_dir( $child_dir, 'child', basename( $child_dir ), $slug_filter, $include_content ) );
			}
		}

		if ( '' === $theme_type || 'parent' === $theme_type || 'theme' === $theme_type ) {
			$parent_dir = '' !== $theme_slug ? get_theme_root() . '/' . $theme_slug : Template_Part_File::get_parent_theme_dir();
			$tt         = $is_child ? 'parent' : 'theme';
			$rows       = array_merge( $rows, $this->scan_dir( $parent_dir, $tt, basename( $parent_dir ), $slug_filter, $include_content ) );
		}

		return $rows;
	}

	private function collect_plugins( string $plugin_slug, string $slug_filter, bool $include_content ): array {
		$rows = array();
		foreach ( Template_Part_File::scan_plugins_with_parts() as $plugin ) {
			if ( '' !== $plugin_slug && $plugin['slug'] !== $plugin_slug ) {
				continue;
			}
			$files = glob( $plugin['path'] . '/*.html' );
			if ( ! is_array( $files ) ) {
				continue;
			}
			foreach ( $files as $file ) {
				$bare = preg_replace( '/\.html$/i', '', basename( $file ) );
				if ( '' !== $slug_filter && $bare !== $slug_filter ) {
					continue;
				}
				$rows[] = $this->file_row( 'plugin', '', $bare, $file, $plugin['slug'], (bool) $plugin['active'], $include_content );
			}
		}
		return $rows;
	}

	private function scan_dir( string $container, string $theme_type, string $theme, string $slug_filter, bool $include_content ): array {
		$rows  = array();
		$parts = Template_Part_File::parts_dir( $container );
		if ( ! is_dir( $parts ) ) {
			return $rows;
		}
		$files = glob( $parts . '/*.html' );
		if ( ! is_array( $files ) ) {
			return $rows;
		}
		foreach ( $files as $file ) {
			$bare = preg_replace( '/\.html$/i', '', basename( $file ) );
			if ( '' !== $slug_filter && $bare !== $slug_filter ) {
				continue;
			}
			$rows[] = $this->file_row( 'theme', $theme_type, $bare, $file, $theme, true, $include_content );
		}
		return $rows;
	}

	private function file_row( string $source, string $theme_type, string $slug, string $path, string $owner, bool $active, bool $include_content ): array {
		$row = array(
			'source'   => $source,
			'slug'     => (string) $slug,
			'path'     => $path,
			'writable' => is_writable( $path ),
		);
		if ( 'theme' === $source ) {
			$row['theme']      = $owner;
			$row['theme_type'] = $theme_type;
			$row['full_slug']  = $owner . '//' . $slug;
		} else {
			$row['plugin']        = $owner;
			$row['plugin_active'] = $active;
		}
		if ( $include_content ) {
			$contents = Template_Part_File::read_file( $path );
			if ( ! is_wp_error( $contents ) ) {
				$row['content'] = $contents;
			}
		}
		return $row;
	}

	/**
	 * Marks one row per slug as "effective" — the location WordPress actually
	 * serves at runtime (DB → child → parent → plugin).
	 */
	private function mark_effective( array $rows ): array {
		$by_slug = array();
		foreach ( $rows as $row ) {
			$slug = (string) ( $row['slug'] ?? '' );
			if ( '' === $slug ) {
				continue;
			}
			$by_slug[ $slug ][] = $row;
		}

		$priority = static function ( array $row ): int {
			if ( 'db' === ( $row['source'] ?? '' ) ) {
				return 0;
			}
			if ( 'theme' === ( $row['source'] ?? '' ) ) {
				return 'child' === ( $row['theme_type'] ?? '' ) ? 1 : 2;
			}
			if ( 'plugin' === ( $row['source'] ?? '' ) ) {
				return 3;
			}
			return 4;
		};

		$out = array();
		foreach ( $by_slug as $candidates ) {
			usort(
				$candidates,
				static function ( $a, $b ) use ( $priority ): int {
					return $priority( $a ) <=> $priority( $b );
				}
			);
			$winner = $candidates[0];
			foreach ( $candidates as $i => $candidate ) {
				$candidate['effective'] = ( $i === 0 );
				if ( false === $candidate['effective'] ) {
					$candidate['overridden_by'] = ( $winner['source'] ?? '' ) . ( isset( $winner['theme_type'] ) ? ':' . $winner['theme_type'] : '' );
				}
				$out[] = $candidate;
			}
		}
		return $out;
	}
}
