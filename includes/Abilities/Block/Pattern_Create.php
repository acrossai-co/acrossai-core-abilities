<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Pattern_Create extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-pattern-create',
			'args' => array(
				'label'               => __( 'Create Theme Block Pattern', 'acrossai-core-abilities' ),
				'description'         => __( 'Creates a new block-pattern PHP file inside a theme\'s /patterns directory. Generates the required Title + Slug doc-block headers and writes the supplied block markup as the body. Fails if the file already exists.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_themes' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme_slug'     => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Theme folder name. Defaults to the active theme.', 'acrossai-core-abilities' ),
						),
						'filename'       => array(
							'type'        => 'string',
							'description' => __( 'Pattern filename, e.g. "hero". The .php extension is added if missing.', 'acrossai-core-abilities' ),
						),
						'title'          => array(
							'type'        => 'string',
							'description' => __( 'Human-readable pattern title (required header).', 'acrossai-core-abilities' ),
						),
						'slug'           => array(
							'type'        => 'string',
							'description' => __( 'Pattern slug as "theme-slug/pattern-slug" (required header).', 'acrossai-core-abilities' ),
						),
						'description'    => array(
							'type'    => 'string',
							'default' => '',
						),
						'viewport_width' => array(
							'type'        => 'integer',
							'minimum'     => 0,
							'description' => __( 'Editor preview viewport width in pixels. Omit for the inserter default.', 'acrossai-core-abilities' ),
						),
						'inserter'       => array(
							'type'    => 'string',
							'enum'    => array( 'yes', 'no' ),
							'default' => 'yes',
						),
						'categories'     => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Comma-separated pattern category slugs.', 'acrossai-core-abilities' ),
						),
						'keywords'       => array(
							'type'    => 'string',
							'default' => '',
						),
						'block_types'    => array(
							'type'    => 'string',
							'default' => '',
						),
						'post_types'     => array(
							'type'    => 'string',
							'default' => '',
						),
						'template_types' => array(
							'type'    => 'string',
							'default' => '',
						),
						'content'        => array(
							'type'        => 'string',
							'description' => __( 'Raw block markup (HTML with <!-- wp:* --> comments) used as the file body.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'filename', 'title', 'slug', 'content' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'path'    => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message' ),
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
		$slug_input = sanitize_text_field( $input['slug'] ?? '' );
		if ( ! Pattern_Helper::is_valid_slug( $slug_input ) ) {
			return array(
				'success' => false,
				'message' => __( 'Slug must be in the form "theme-slug/pattern-slug" using lowercase letters, digits, dashes, or underscores.', 'acrossai-core-abilities' ),
			);
		}

		$theme_slug = sanitize_text_field( $input['theme_slug'] ?? '' );
		$theme_dir  = Pattern_Helper::resolve_theme_dir( $theme_slug );

		if ( is_wp_error( $theme_dir ) ) {
			return array( 'success' => false, 'message' => $theme_dir->get_error_message() );
		}

		$abs_path = Pattern_Helper::resolve_pattern_path( $theme_dir, sanitize_text_field( $input['filename'] ?? '' ) );
		if ( is_wp_error( $abs_path ) ) {
			return array( 'success' => false, 'message' => $abs_path->get_error_message() );
		}

		$patterns_dir = dirname( $abs_path );
		if ( ! is_dir( $patterns_dir ) ) {
			if ( ! wp_mkdir_p( $patterns_dir ) ) {
				return array( 'success' => false, 'message' => __( 'Could not create /patterns directory in theme.', 'acrossai-core-abilities' ) );
			}
		}

		if ( file_exists( $abs_path ) ) {
			return array( 'success' => false, 'message' => __( 'Pattern file already exists. Use block-pattern-update to overwrite.', 'acrossai-core-abilities' ) );
		}

		$headers = array(
			'Title'          => sanitize_text_field( $input['title'] ?? '' ),
			'Slug'           => $slug_input,
			'Description'    => sanitize_text_field( $input['description'] ?? '' ),
			'Viewport Width' => isset( $input['viewport_width'] ) ? (string) (int) $input['viewport_width'] : '',
			'Inserter'       => sanitize_text_field( $input['inserter'] ?? 'yes' ),
			'Categories'     => sanitize_text_field( $input['categories'] ?? '' ),
			'Keywords'       => sanitize_text_field( $input['keywords'] ?? '' ),
			'Block Types'    => sanitize_text_field( $input['block_types'] ?? '' ),
			'Post Types'     => sanitize_text_field( $input['post_types'] ?? '' ),
			'Template Types' => sanitize_text_field( $input['template_types'] ?? '' ),
		);

		$file_contents = Pattern_Helper::build_file( $headers, (string) ( $input['content'] ?? '' ) );

		$written = file_put_contents( $abs_path, $file_contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $written ) {
			return array( 'success' => false, 'message' => __( 'Could not write pattern file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $abs_path,
			'message' => __( 'Pattern created.', 'acrossai-core-abilities' ),
		);
	}
}
