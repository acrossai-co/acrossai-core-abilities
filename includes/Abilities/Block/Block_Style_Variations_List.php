<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Block_Style_Variations\Variation_Db;
use Acrossai_Core_Abilities\Includes\Utilities\Block_Style_Variations\Variation_File;

defined( 'ABSPATH' ) || exit;

/**
 * Lists Block Style Variations across every storage layer (Scenario 27).
 *   - source=db     → wp_global_styles variation rows (excludes the main
 *                     Global Styles record)
 *   - source=theme  → <slug>.json in /styles for the named theme (active
 *                     stylesheet by default)
 *   - source=plugin → <slug>.json in /styles for installed plugins
 *   - source=all    → union (default)
 *
 * Per slug, one row is marked "effective" — the copy WordPress would serve
 * at runtime (DB → child → parent → plugin).
 */
class Block_Style_Variations_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-style-variations-list',
			'args' => array(
				'label'               => __( 'List Block Style Variations', 'acrossai-core-abilities' ),
				'description'         => __( 'Lists Block Style Variations across the database (wp_global_styles) and theme/plugin /styles directories. Each variation reports its theme, slug, customised sections, and whether it is the active variation.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'sub_group'           => 'block-style-variations',
				'sub_group_label'     => __( 'Block Style Variations', 'acrossai-core-abilities' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
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
							'type'    => 'string',
							'default' => '',
						),
						'plugin_slug'     => array(
							'type'    => 'string',
							'default' => '',
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
						'success'      => array( 'type' => 'boolean' ),
						'variations'   => array( 'type' => 'array' ),
						'total'        => array( 'type' => 'integer' ),
						'active_theme' => array( 'type' => 'string' ),
						'warnings'     => array( 'type' => 'array' ),
						'message'      => array( 'type' => 'string' ),
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
		$slug_filter     = sanitize_title( $input['slug'] ?? '' );
		$include_content = ! empty( $input['include_content'] );

		$rows = array();

		if ( 'all' === $source || 'db' === $source ) {
			$rows = array_merge( $rows, $this->collect_db( $theme_slug, $slug_filter, $include_content ) );
		}

		if ( 'all' === $source || 'theme' === $source ) {
			$rows = array_merge( $rows, $this->collect_theme( $theme_type, $theme_slug, $slug_filter, $include_content ) );
		}

		if ( 'all' === $source || 'plugin' === $source ) {
			$rows = array_merge( $rows, $this->collect_plugins( $plugin_slug, $slug_filter, $include_content ) );
		}

		$rows = $this->mark_effective( $rows );

		$warnings = array();
		if ( is_multisite() ) {
			$warnings[] = __( 'On multisite, DB variation rows are scoped to the current site only; /styles files are shared across all sites.', 'acrossai-core-abilities' );
		}

		return array(
			'success'      => true,
			'variations'   => $rows,
			'total'        => count( $rows ),
			'active_theme' => (string) get_stylesheet(),
			'warnings'     => $warnings,
		);
	}

	private function collect_db( string $theme, string $slug_filter, bool $include_content ): array {
		$posts = Variation_Db::list_all( $theme, 500 );
		$rows  = array();
		foreach ( $posts as $post ) {
			if ( '' !== $slug_filter && (string) $post->post_name !== $slug_filter ) {
				continue;
			}
			$rows[] = Variation_Db::to_row( $post, $include_content );
		}
		return $rows;
	}

	private function collect_theme( string $theme_type, string $theme_slug, string $slug_filter, bool $include_content ): array {
		$rows      = array();
		$child_dir = Variation_File::get_child_theme_dir();
		$is_child  = null !== $child_dir;

		if ( '' === $theme_type || 'child' === $theme_type ) {
			if ( $is_child ) {
				$rows = array_merge( $rows, $this->scan_container( $child_dir, 'theme', 'child', basename( $child_dir ), $slug_filter, $include_content ) );
			}
		}

		if ( '' === $theme_type || 'parent' === $theme_type || 'theme' === $theme_type ) {
			$parent_dir = '' !== $theme_slug ? get_theme_root() . '/' . $theme_slug : Variation_File::get_parent_theme_dir();
			$tt         = $is_child ? 'parent' : 'theme';
			$rows       = array_merge( $rows, $this->scan_container( $parent_dir, 'theme', $tt, basename( $parent_dir ), $slug_filter, $include_content ) );
		}

		return $rows;
	}

	private function collect_plugins( string $plugin_slug, string $slug_filter, bool $include_content ): array {
		$rows = array();
		foreach ( Variation_File::scan_plugins_with_styles() as $plugin ) {
			if ( '' !== $plugin_slug && $plugin['slug'] !== $plugin_slug ) {
				continue;
			}
			foreach ( Variation_File::scan_variations_in_dir( dirname( $plugin['path'] ) ) as $file_row ) {
				if ( '' !== $slug_filter && $file_row['slug'] !== $slug_filter ) {
					continue;
				}
				$row = array(
					'source'        => 'plugin',
					'plugin'        => $plugin['slug'],
					'plugin_active' => (bool) $plugin['active'],
					'slug'          => $file_row['slug'],
					'path'          => $file_row['path'],
					'writable'      => $file_row['writable'],
				);
				if ( $include_content ) {
					$data = Variation_File::read_json( $file_row['path'] );
					if ( ! is_wp_error( $data ) ) {
						$row['data'] = $data;
					}
				}
				$rows[] = $row;
			}
		}
		return $rows;
	}

	private function scan_container( string $container, string $source, string $theme_type, string $theme_name, string $slug_filter, bool $include_content ): array {
		$rows  = array();
		$files = Variation_File::scan_variations_in_dir( $container );
		foreach ( $files as $file_row ) {
			if ( '' !== $slug_filter && $file_row['slug'] !== $slug_filter ) {
				continue;
			}
			$row = array(
				'source'     => $source,
				'theme_type' => $theme_type,
				'theme'      => $theme_name,
				'slug'       => $file_row['slug'],
				'path'       => $file_row['path'],
				'writable'   => $file_row['writable'],
			);
			if ( $include_content ) {
				$data = Variation_File::read_json( $file_row['path'] );
				if ( ! is_wp_error( $data ) ) {
					$row['data'] = $data;
				}
			}
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * For each slug, marks the highest-priority location effective.
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
			return 3;
		};

		$out = array();
		foreach ( $by_slug as $group ) {
			usort(
				$group,
				static function ( $a, $b ) use ( $priority ): int {
					return $priority( $a ) <=> $priority( $b );
				}
			);
			foreach ( $group as $i => $row ) {
				$row['effective'] = ( 0 === $i );
				if ( false === $row['effective'] ) {
					$winner               = $group[0];
					$row['overridden_by'] = ( $winner['source'] ?? '' ) . ( isset( $winner['theme_type'] ) ? ':' . $winner['theme_type'] : '' );
				}
				$out[] = $row;
			}
		}
		return $out;
	}
}
