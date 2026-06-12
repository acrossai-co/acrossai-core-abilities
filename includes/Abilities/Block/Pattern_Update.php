<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Pattern_Update extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/block-pattern-update',
			'args' => array(
				'label'               => __( 'Update Theme Block Pattern', 'acrossai-core-abilities' ),
				'description'         => __( 'Overwrites an existing block-pattern PHP file in a theme\'s /patterns directory. Any supplied headers replace the current values; unspecified headers are preserved. If "content" is omitted, the existing body is kept.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-block',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_themes' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'theme_slug'     => array(
							'type'    => 'string',
							'default' => '',
						),
						'filename'       => array(
							'type'        => 'string',
							'description' => __( 'Pattern filename to update.', 'acrossai-core-abilities' ),
						),
						'title'          => array( 'type' => 'string' ),
						'slug'           => array(
							'type'        => 'string',
							'description' => __( 'New pattern slug as "theme-slug/pattern-slug".', 'acrossai-core-abilities' ),
						),
						'description'    => array( 'type' => 'string' ),
						'viewport_width' => array(
							'type'    => 'integer',
							'minimum' => 0,
						),
						'inserter'       => array(
							'type' => 'string',
							'enum' => array( 'yes', 'no' ),
						),
						'categories'     => array( 'type' => 'string' ),
						'keywords'       => array( 'type' => 'string' ),
						'block_types'    => array( 'type' => 'string' ),
						'post_types'     => array( 'type' => 'string' ),
						'template_types' => array( 'type' => 'string' ),
						'content'        => array(
							'type'        => 'string',
							'description' => __( 'Replacement block markup. Omit to keep the existing body.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'filename' ),
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
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$theme_slug = sanitize_text_field( $input['theme_slug'] ?? '' );
		$theme_dir  = Pattern_Helper::resolve_theme_dir( $theme_slug );

		if ( is_wp_error( $theme_dir ) ) {
			return array( 'success' => false, 'message' => $theme_dir->get_error_message() );
		}

		$abs_path = Pattern_Helper::resolve_pattern_path( $theme_dir, sanitize_text_field( $input['filename'] ?? '' ) );
		if ( is_wp_error( $abs_path ) ) {
			return array( 'success' => false, 'message' => $abs_path->get_error_message() );
		}

		if ( ! is_file( $abs_path ) ) {
			return array( 'success' => false, 'message' => __( 'Pattern file not found. Use block-pattern-create to add a new one.', 'acrossai-core-abilities' ) );
		}

		if ( isset( $input['slug'] ) && '' !== $input['slug'] && ! Pattern_Helper::is_valid_slug( sanitize_text_field( $input['slug'] ) ) ) {
			return array(
				'success' => false,
				'message' => __( 'Slug must be in the form "theme-slug/pattern-slug".', 'acrossai-core-abilities' ),
			);
		}

		$existing = file_get_contents( $abs_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $existing ) {
			return array( 'success' => false, 'message' => __( 'Could not read existing pattern file.', 'acrossai-core-abilities' ) );
		}

		$parsed       = Pattern_Helper::parse_file( $existing );
		$headers      = $parsed['headers'];
		$header_input = array(
			'Title'          => 'title',
			'Slug'           => 'slug',
			'Description'    => 'description',
			'Viewport Width' => 'viewport_width',
			'Inserter'       => 'inserter',
			'Categories'     => 'categories',
			'Keywords'       => 'keywords',
			'Block Types'    => 'block_types',
			'Post Types'     => 'post_types',
			'Template Types' => 'template_types',
		);
		foreach ( $header_input as $field => $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$value             = is_int( $input[ $key ] ) ? (string) $input[ $key ] : sanitize_text_field( (string) $input[ $key ] );
				$headers[ $field ] = $value;
			}
		}

		$body = array_key_exists( 'content', $input ) ? (string) $input['content'] : $parsed['body'];

		$new_contents = Pattern_Helper::build_file( $headers, $body );

		$written = file_put_contents( $abs_path, $new_contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $written ) {
			return array( 'success' => false, 'message' => __( 'Could not write pattern file.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			'path'    => $abs_path,
			'message' => __( 'Pattern updated.', 'acrossai-core-abilities' ),
		);
	}
}
