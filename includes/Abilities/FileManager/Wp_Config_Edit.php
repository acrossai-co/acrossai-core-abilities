<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Wp_Config_Edit extends Ability_Definition {

	/**
	 * Constants that may not be modified via this ability.
	 */
	private const PROTECTED = array(
		'DB_NAME',
		'DB_USER',
		'DB_PASSWORD',
		'DB_HOST',
		'AUTH_KEY',
		'SECURE_AUTH_KEY',
		'LOGGED_IN_KEY',
		'NONCE_KEY',
		'AUTH_SALT',
		'SECURE_AUTH_SALT',
		'LOGGED_IN_SALT',
		'NONCE_SALT',
		'SECRET_KEY',
	);

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/wp-config-edit',
			'args' => array(
				'label'               => __( 'Edit wp-config.php', 'acrossai-core-abilities' ),
				'description'         => __( 'Updates the value of an existing non-sensitive constant in wp-config.php. Protected credential and secret constants cannot be modified.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-file-manager',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'constant_name' => array(
							'type'        => 'string',
							'description' => __( 'Name of the constant to update (e.g. WP_DEBUG).', 'acrossai-core-abilities' ),
						),
						'value'         => array(
							'type'        => 'string',
							'description' => __( 'New string value for the constant.', 'acrossai-core-abilities' ),
						),
					),
					'required'             => array( 'constant_name', 'value' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'            => array( 'success', 'message' ),
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
		$name  = strtoupper( sanitize_text_field( $input['constant_name'] ?? '' ) );
		$value = $input['value'] ?? '';

		if ( '' === $name || ! preg_match( '/^[A-Z_][A-Z0-9_]*$/', $name ) ) {
			return array( 'success' => false, 'message' => __( 'Invalid constant name.', 'acrossai-core-abilities' ) );
		}

		if ( in_array( $name, self::PROTECTED, true ) ) {
			return array( 'success' => false, 'message' => __( 'This constant is protected and cannot be modified.', 'acrossai-core-abilities' ) );
		}

		$config_path = $this->locate_wp_config();

		if ( null === $config_path ) {
			return array( 'success' => false, 'message' => __( 'wp-config.php not found.', 'acrossai-core-abilities' ) );
		}

		$raw     = file_get_contents( $config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$escaped = addslashes( $value );
		$pattern = "/define\(\s*(['\"])" . preg_quote( $name, '/' ) . "\\1\s*,\s*(?:'[^']*'|\"[^\"]*\"|[^)]+)\s*\)/";
		$updated = preg_replace( $pattern, "define( '{$name}', '{$escaped}' )", $raw, -1, $count );

		if ( 0 === $count ) {
			return array( 'success' => false, 'message' => __( 'Constant not found in wp-config.php.', 'acrossai-core-abilities' ) );
		}

		if ( false === file_put_contents( $config_path, $updated ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return array( 'success' => false, 'message' => __( 'Could not write wp-config.php.', 'acrossai-core-abilities' ) );
		}

		return array(
			'success' => true,
			/* translators: constant name */
			'message' => sprintf( __( 'Constant %s updated.', 'acrossai-core-abilities' ), $name ),
		);
	}

	private function locate_wp_config(): ?string {
		$candidates = array(
			ABSPATH . 'wp-config.php',
			dirname( rtrim( ABSPATH, '/' ) ) . '/wp-config.php',
		);
		foreach ( $candidates as $path ) {
			if ( is_file( $path ) ) {
				return $path;
			}
		}
		return null;
	}
}
