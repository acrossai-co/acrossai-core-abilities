<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_Db;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_Detector;
use Acrossai_Core_Abilities\Includes\Utilities\Template_Part\Template_Part_File;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a single block template part from whichever storage layer holds it.
 * Auto-resolves the source when there's one obvious location; surfaces a
 * "multiple_locations" error with the candidate list when there are several
 * (the caller picks one with source / theme_type / plugin_slug).
 *
 * Always reports all known locations in "locations" so the caller can see
 * which copy WordPress will actually serve (DB → child → parent → plugin).
 */
class Template_Part_Read extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/template-part-read',
			'args' => array(
				'label'               => __( 'Read Block Template Part', 'acrossai-core-abilities' ),
				'description'         => __( 'Reads a single block template part by slug from the database, theme, or plugin. When the slug exists in multiple locations, returns "multiple_locations" with the candidate list — pick one with source / theme_type / plugin_slug.', 'acrossai-core-abilities' ),
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
							'description' => __( 'Template part slug (bare, e.g. "header").', 'acrossai-core-abilities' ),
						),
						'source'      => array(
							'type'    => 'string',
							'enum'    => array( '', 'db', 'theme', 'child_theme', 'plugin' ),
							'default' => '',
						),
						'theme_type'  => array(
							'type'    => 'string',
							'enum'    => array( '', 'child', 'parent', 'theme' ),
							'default' => '',
						),
						'plugin_slug' => array(
							'type'    => 'string',
							'default' => '',
						),
						'theme'       => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Theme hint used when looking up DB rows. Defaults to the active stylesheet.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'slug' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'   => array( 'type' => 'boolean' ),
						'part'      => array( 'type' => 'object' ),
						'locations' => array( 'type' => 'array' ),
						'warnings'  => array( 'type' => 'array' ),
						'message'   => array( 'type' => 'string' ),
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
		$slug        = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$source      = sanitize_text_field( $input['source'] ?? '' );
		$theme_type  = sanitize_text_field( $input['theme_type'] ?? '' );
		$plugin_slug = sanitize_key( $input['plugin_slug'] ?? '' );
		$theme_hint  = sanitize_key( $input['theme'] ?? '' );

		if ( '' === $slug ) {
			return array(
				'success' => false,
				'message' => __( 'Slug is required.', 'acrossai-core-abilities' ),
			);
		}

		$locations = Template_Part_Detector::locate( $slug, $theme_hint );

		if ( empty( $locations ) ) {
			return array(
				'success'   => false,
				/* translators: %s: slug */
				'message'   => sprintf( __( 'No template part with slug "%s" was found. Use template-part-create to add one.', 'acrossai-core-abilities' ), $slug ),
				'locations' => array(),
			);
		}

		$selected = Template_Part_Detector::select( $locations, $source, $theme_type, $plugin_slug );

		if ( is_wp_error( $selected ) ) {
			$data = $selected->get_error_data();
			return array(
				'success'   => false,
				'message'   => $selected->get_error_message(),
				'locations' => $locations,
				'candidates' => is_array( $data ) ? ( $data['locations'] ?? array() ) : array(),
			);
		}

		$warnings = $this->collect_warnings( $locations, $selected );
		$part     = $this->materialise( $selected );

		if ( is_wp_error( $part ) ) {
			return array(
				'success'   => false,
				'message'   => $part->get_error_message(),
				'locations' => $locations,
			);
		}

		return array(
			'success'   => true,
			'part'      => $part,
			'locations' => $locations,
			'warnings'  => $warnings,
		);
	}

	/**
	 * Surfaces the scenarios in the spec as warning strings so the caller can
	 * relay them to the user without ambiguity.
	 */
	private function collect_warnings( array $locations, array $selected ): array {
		$warnings = array();
		$effective = Template_Part_Detector::effective( $locations );

		if ( count( $locations ) > 1 ) {
			$warnings[] = __( 'This slug exists in multiple locations. WordPress always serves the highest-priority copy: DB → child theme → parent theme → plugin.', 'acrossai-core-abilities' );
		}

		if ( $effective && ( $selected['source'] ?? '' ) !== ( $effective['source'] ?? '' ) ) {
			/* translators: %s: effective source */
			$warnings[] = sprintf(
				__( 'You are reading the %1$s copy, but WordPress is currently serving the %2$s copy.', 'acrossai-core-abilities' ),
				$this->describe( $selected ),
				$this->describe( $effective )
			);
		}

		foreach ( $locations as $loc ) {
			if ( ( $loc['source'] ?? '' ) === 'plugin' && false === ( $loc['plugin_active'] ?? true ) ) {
				/* translators: %s: plugin slug */
				$warnings[] = sprintf( __( 'Plugin "%s" is inactive — its template part will not register until the plugin is activated.', 'acrossai-core-abilities' ), $loc['plugin'] ?? '' );
			}
		}

		return $warnings;
	}

	private function describe( array $loc ): string {
		$src = (string) ( $loc['source'] ?? '' );
		if ( 'theme' === $src ) {
			return 'theme:' . ( $loc['theme_type'] ?? 'theme' );
		}
		if ( 'plugin' === $src ) {
			return 'plugin:' . ( $loc['plugin'] ?? '' );
		}
		return $src;
	}

	/**
	 * Loads the actual content/metadata at the selected location.
	 *
	 * @return array|\WP_Error
	 */
	private function materialise( array $loc ) {
		$src = (string) ( $loc['source'] ?? '' );

		if ( 'db' === $src ) {
			$post = get_post( (int) ( $loc['post_id'] ?? 0 ) );
			if ( ! $post ) {
				return new \WP_Error( 'db_post_missing', __( 'Template part post not found in the database.', 'acrossai-core-abilities' ) );
			}
			return Template_Part_Db::to_row( $post );
		}

		$path     = (string) ( $loc['path'] ?? '' );
		$contents = Template_Part_File::read_file( $path );
		if ( is_wp_error( $contents ) ) {
			return $contents;
		}

		$row = array(
			'source'   => $src,
			'slug'     => (string) ( $loc['slug'] ?? '' ),
			'path'     => $path,
			'content'  => $contents,
			'writable' => (bool) ( $loc['writable'] ?? false ),
		);

		if ( 'theme' === $src ) {
			$row['theme']      = (string) ( $loc['theme'] ?? '' );
			$row['theme_type'] = (string) ( $loc['theme_type'] ?? '' );
			$row['full_slug']  = ( $row['theme'] ) . '//' . $row['slug'];
		} else {
			$row['plugin']        = (string) ( $loc['plugin'] ?? '' );
			$row['plugin_active'] = (bool) ( $loc['plugin_active'] ?? false );
		}

		return $row;
	}
}
