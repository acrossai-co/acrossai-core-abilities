<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_Db;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_Detector;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_File;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a new block template part. source defaults to "db" per spec
 * (nothing mentioned → wp_template_part CPT). Pass source=child_theme,
 * source=theme (parent), or source=plugin to write a .html file instead.
 *
 * Scenarios enforced:
 *  - 18: empty content rejected
 *  - 19: invalid area rejected
 *  - 13: refuses source=child_theme when no child is active (suggests DB)
 *  - 15: refuses to write when the parts dir / file is not writable
 *  - 11: refuses to write to source=theme:parent (offers to write to child)
 */
class Template_Part_Create extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/template-part-create',
			'args' => array(
				'label'               => __( 'Create Block Template Part', 'acrossai-core-abilities' ),
				'description'         => __( 'Creates a block template part. Defaults to the database (wp_template_part). Pass source=child_theme to write to the child theme\'s /parts dir, source=theme (with theme_slug) to write to a specific theme folder, or source=plugin (with plugin_slug) to write to a plugin\'s /parts dir.', 'acrossai-core-abilities' ),
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
						'slug'        => array(
							'type'        => 'string',
							'description' => __( 'Bare slug (lowercase letters, digits, dash, underscore).', 'acrossai-core-abilities' ),
						),
						'title'       => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'content'     => array(
							'type'        => 'string',
							'description' => __( 'Block markup for the template part. Cannot be empty.', 'acrossai-core-abilities' ),
						),
						'area'        => array(
							'type'    => 'string',
							'enum'    => array( 'header', 'footer', 'sidebar', 'uncategorized' ),
							'default' => 'uncategorized',
						),
						'source'      => array(
							'type'    => 'string',
							'enum'    => array( 'db', 'theme', 'child_theme', 'plugin' ),
							'default' => 'db',
						),
						'theme_slug'  => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Theme folder name. Required when source=theme (writes to that theme\'s /parts); ignored otherwise. Defaults to the active stylesheet for source=child_theme.', 'acrossai-core-abilities' ),
						),
						'plugin_slug' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Plugin folder name. Required when source=plugin.', 'acrossai-core-abilities' ),
						),
						'status'      => array(
							'type'    => 'string',
							'enum'    => array( 'publish', 'draft', 'private', 'pending' ),
							'default' => 'publish',
						),
					),
					'required'             => array( 'slug', 'content' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'part'     => array( 'type' => 'object' ),
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
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$slug        = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$content     = (string) ( $input['content'] ?? '' );
		$area        = sanitize_text_field( $input['area'] ?? 'uncategorized' );
		$source      = sanitize_text_field( $input['source'] ?? 'db' );
		$theme_slug  = sanitize_key( $input['theme_slug'] ?? '' );
		$plugin_slug = sanitize_key( $input['plugin_slug'] ?? '' );

		if ( '' === $slug || ! Template_Part_File::is_valid_bare_slug( $slug ) ) {
			return array(
				'success' => false,
				'message' => __( 'Slug is invalid. Use lowercase letters, digits, dashes, or underscores.', 'acrossai-core-abilities' ),
			);
		}

		if ( ! Template_Part_Db::valid_content( $content ) ) {
			return array(
				'success' => false,
				'message' => __( 'Content cannot be empty.', 'acrossai-core-abilities' ),
			);
		}

		if ( ! Template_Part_Db::valid_area( $area ) ) {
			return array(
				'success' => false,
				/* translators: %s: list of valid areas */
				'message' => sprintf( __( 'Area must be one of: %s.', 'acrossai-core-abilities' ), implode( ', ', Template_Part_Db::valid_areas() ) ),
			);
		}

		// Refuse to overwrite an existing copy anywhere — use update instead.
		$existing = Template_Part_Detector::locate( $slug, '' !== $theme_slug ? $theme_slug : (string) get_stylesheet() );
		if ( ! empty( $existing ) ) {
			return array(
				'success'   => false,
				/* translators: %s: slug */
				'message'   => sprintf( __( 'A template part with slug "%s" already exists. Use template-part-update or pick a different slug.', 'acrossai-core-abilities' ), $slug ),
				'locations' => $existing,
			);
		}

		switch ( $source ) {
			case 'db':
				return $this->create_db( $slug, $content, $area, $theme_slug, $input );

			case 'child_theme':
				return $this->create_theme_file( $slug, $content, true, '', $input );

			case 'theme':
				return $this->create_theme_file( $slug, $content, false, $theme_slug, $input );

			case 'plugin':
				return $this->create_plugin_file( $slug, $content, $plugin_slug, $input );
		}

		return array(
			'success' => false,
			'message' => __( 'Unknown source.', 'acrossai-core-abilities' ),
		);
	}

	private function create_db( string $slug, string $content, string $area, string $theme_slug, array $input ): array {
		$theme  = '' !== $theme_slug ? $theme_slug : (string) get_stylesheet();
		$result = Template_Part_Db::create(
			array(
				'slug'        => $slug,
				'title'       => (string) ( $input['title'] ?? $slug ),
				'description' => (string) ( $input['description'] ?? '' ),
				'content'     => $content,
				'area'        => $area,
				'theme'       => $theme,
				'status'      => $input['status'] ?? 'publish',
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		$post     = get_post( (int) $result );
		$warnings = array();
		if ( is_multisite() ) {
			$warnings[] = __( 'On multisite, this DB template part is scoped to the current site only.', 'acrossai-core-abilities' );
		}

		return array(
			'success'  => true,
			/* translators: %s: slug */
			'message'  => sprintf( __( 'Created DB template part "%s".', 'acrossai-core-abilities' ), $slug ),
			'part'     => $post ? Template_Part_Db::to_row( $post ) : array( 'source' => 'db', 'slug' => $slug, 'post_id' => (int) $result ),
			'warnings' => $warnings,
		);
	}

	private function create_theme_file( string $slug, string $content, bool $force_child, string $theme_slug, array $input ): array {
		$warnings = array();

		if ( $force_child ) {
			$dir = Template_Part_File::get_child_theme_dir();
			if ( null === $dir ) {
				return array(
					'success' => false,
					'message' => __( 'No child theme is active. Create a child theme first, or use source=db to store the template part in the database instead.', 'acrossai-core-abilities' ),
				);
			}
		} elseif ( '' !== $theme_slug ) {
			$dir = Template_Part_File::resolve_theme_dir( $theme_slug );
			if ( is_wp_error( $dir ) ) {
				return array(
					'success' => false,
					'message' => $dir->get_error_message(),
				);
			}
			$child = Template_Part_File::get_child_theme_dir();
			if ( null !== $child && $child !== $dir ) {
				$warnings[] = __( 'Writing to the parent theme — your changes will be lost if the theme is updated. Prefer source=child_theme.', 'acrossai-core-abilities' );
			}
		} else {
			$child = Template_Part_File::get_child_theme_dir();
			$dir   = null !== $child ? $child : Template_Part_File::get_parent_theme_dir();
		}

		$parts_dir = Template_Part_File::ensure_parts_dir( $dir );
		if ( is_wp_error( $parts_dir ) ) {
			return array(
				'success' => false,
				'message' => $parts_dir->get_error_message(),
			);
		}

		$abs = Template_Part_File::resolve_part_path( $dir, $slug );
		if ( is_wp_error( $abs ) ) {
			return array(
				'success' => false,
				'message' => $abs->get_error_message(),
			);
		}

		$bytes = Template_Part_File::write_file( $abs, $content );
		if ( is_wp_error( $bytes ) ) {
			return array(
				'success' => false,
				'message' => $bytes->get_error_message(),
			);
		}

		$theme_name = basename( $dir );
		$child      = Template_Part_File::get_child_theme_dir();
		$is_child   = ( null !== $child && $dir === $child );

		$warnings[] = __( 'Site Editor saves create a DB record that will override this file copy on the next save.', 'acrossai-core-abilities' );

		return array(
			'success'  => true,
			/* translators: 1: slug, 2: file path */
			'message'  => sprintf( __( 'Wrote template part "%1$s" to %2$s.', 'acrossai-core-abilities' ), $slug, $abs ),
			'part'     => array(
				'source'     => 'theme',
				'theme_type' => $is_child ? 'child' : ( null !== $child ? 'parent' : 'theme' ),
				'theme'      => $theme_name,
				'slug'       => $slug,
				'full_slug'  => $theme_name . '//' . $slug,
				'path'       => $abs,
				'bytes'      => (int) $bytes,
			),
			'warnings' => $warnings,
		);
	}

	private function create_plugin_file( string $slug, string $content, string $plugin_slug, array $input ): array {
		if ( '' === $plugin_slug ) {
			return array(
				'success' => false,
				'message' => __( 'plugin_slug is required when source=plugin.', 'acrossai-core-abilities' ),
			);
		}

		$plugin = Template_Part_File::resolve_plugin_dir( $plugin_slug );
		if ( is_wp_error( $plugin ) ) {
			return array(
				'success' => false,
				'message' => $plugin->get_error_message(),
			);
		}

		$warnings = array();
		if ( ! $plugin['active'] ) {
			/* translators: %s: plugin slug */
			$warnings[] = sprintf( __( 'Plugin "%s" is inactive — the template part will not register until the plugin is activated.', 'acrossai-core-abilities' ), $plugin_slug );
		}

		$parts_dir = Template_Part_File::ensure_parts_dir( $plugin['path'] );
		if ( is_wp_error( $parts_dir ) ) {
			return array(
				'success' => false,
				'message' => $parts_dir->get_error_message(),
			);
		}

		$abs = Template_Part_File::resolve_part_path( $plugin['path'], $slug );
		if ( is_wp_error( $abs ) ) {
			return array(
				'success' => false,
				'message' => $abs->get_error_message(),
			);
		}

		$bytes = Template_Part_File::write_file( $abs, $content );
		if ( is_wp_error( $bytes ) ) {
			return array(
				'success' => false,
				'message' => $bytes->get_error_message(),
			);
		}

		return array(
			'success'  => true,
			/* translators: 1: slug, 2: file path */
			'message'  => sprintf( __( 'Wrote template part "%1$s" to %2$s.', 'acrossai-core-abilities' ), $slug, $abs ),
			'part'     => array(
				'source'        => 'plugin',
				'plugin'        => $plugin_slug,
				'plugin_active' => (bool) $plugin['active'],
				'slug'          => $slug,
				'path'          => $abs,
				'bytes'         => (int) $bytes,
			),
			'warnings' => $warnings,
		);
	}
}
