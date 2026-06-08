<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Plugins;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\Plugin_Helpers;

defined( 'ABSPATH' ) || exit;

class Plugin_Install extends Ability_Definition {

	protected function main_key(): string {
		return 'acrossai-core-plugins';
	}

	protected function main_key_label(): string {
		return __( 'Acrossai Core Plugins', 'acrossai-core-abilities' );
	}

	protected function sub_key(): string {
		return 'plugin-install';
	}

	protected function sub_key_label(): string {
		return __( 'Install Plugin', 'acrossai-core-abilities' );
	}

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/plugin-install',
			'args' => array(
				'label'               => __( 'Install Plugin', 'acrossai-core-abilities' ),
				'description'         => __( 'Install a plugin from the WordPress.org plugin directory by name or slug.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-plugins',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'plugin'   => array(
							'type'        => 'string',
							'description' => __( 'The plugin name or slug to install from WordPress.org.', 'acrossai-core-abilities' ),
						),
						'activate' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to activate the plugin after installing.', 'acrossai-core-abilities' ),
							'default'     => false,
						),
					),
					'required'             => array( 'plugin' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'message'     => array( 'type' => 'string' ),
						'plugin_name' => array( 'type' => 'string' ),
						'plugin_slug' => array( 'type' => 'string' ),
						'activated'   => array( 'type' => 'boolean' ),
					),
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
		if ( empty( $input['plugin'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No plugin specified.', 'acrossai-core-abilities' ),
			);
		}

		$plugin_slug = sanitize_text_field( $input['plugin'] );
		$activate    = ! empty( $input['activate'] );

		$plugin_slug = sanitize_title( $plugin_slug );

		if ( '' === $plugin_slug ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid plugin slug.', 'acrossai-core-abilities' ),
			);
		}

		// Check if plugin is already installed.
		$resolved = Plugin_Helpers::resolve_plugin( $plugin_slug );
		if ( null !== $resolved['plugin_file'] && $resolved['certainty'] >= 8.0 ) {
			$plugin_data = Plugin_Helpers::get_plugin_by_slug( $resolved['plugin_file'] );
			$status      = $plugin_data && $plugin_data['active'] ? __( 'active', 'acrossai-core-abilities' ) : __( 'inactive', 'acrossai-core-abilities' );

			return array(
				'success'           => true,
				/* translators: 1: plugin name, 2: plugin status */
				'message'           => sprintf( __( 'Plugin "%1$s" is already installed (%2$s).', 'acrossai-core-abilities' ), $resolved['plugin_name'], $status ),
				'already_installed' => true,
				'plugin_name'       => $resolved['plugin_name'],
				'plugin_slug'       => $resolved['plugin_file'],
				'active'            => $plugin_data && $plugin_data['active'],
			);
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'short_description' => true,
					'sections'          => false,
					'requires'          => true,
					'tested'            => true,
					'rating'            => false,
					'downloaded'        => false,
					'download_link'     => true,
					'last_updated'      => false,
					'homepage'          => false,
					'tags'              => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return array(
				'success' => false,
				/* translators: 1: plugin slug, 2: error message */
				'message' => sprintf( __( 'Could not find plugin "%1$s" on WordPress.org: %2$s', 'acrossai-core-abilities' ), $plugin_slug, $api->get_error_message() ),
			);
		}

		if ( empty( $api->download_link ) ) {
			return array(
				'success' => false,
				/* translators: %s: plugin name */
				'message' => sprintf( __( 'No download link available for "%s".', 'acrossai-core-abilities' ), $api->name ?? $plugin_slug ),
			);
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				/* translators: 1: plugin name, 2: error message */
				'message' => sprintf( __( 'Failed to install "%1$s": %2$s', 'acrossai-core-abilities' ), $api->name, $result->get_error_message() ),
			);
		}

		if ( true !== $result ) {
			$errors    = $skin->get_errors();
			$feedback  = $skin->get_upgrade_messages();
			$error_msg = '';

			if ( is_wp_error( $errors ) && $errors->has_errors() ) {
				$error_msg = $errors->get_error_message();
			} elseif ( ! empty( $feedback ) ) {
				$error_msg = implode( ' ', $feedback );
			} else {
				$error_msg = __( 'Unknown error during installation.', 'acrossai-core-abilities' );
			}

			return array(
				'success' => false,
				/* translators: 1: plugin name, 2: error message */
				'message' => sprintf( __( 'Failed to install "%1$s": %2$s', 'acrossai-core-abilities' ), $api->name, $error_msg ),
			);
		}

		$activated = false;

		if ( $activate ) {
			wp_clean_plugins_cache();
			$installed = Plugin_Helpers::resolve_plugin( $plugin_slug );
			if ( null !== $installed['plugin_file'] ) {
				$activate_result = activate_plugin( $installed['plugin_file'] );
				$activated       = ! is_wp_error( $activate_result );
			}
		}

		if ( $activate && $activated ) {
			/* translators: %s: plugin name */
			$message = sprintf( __( 'Plugin "%s" has been installed and activated successfully.', 'acrossai-core-abilities' ), $api->name );
		} elseif ( $activate && ! $activated ) {
			/* translators: %s: plugin name */
			$message = sprintf( __( 'Plugin "%s" was installed but could not be activated.', 'acrossai-core-abilities' ), $api->name );
		} else {
			/* translators: %s: plugin name */
			$message = sprintf( __( 'Plugin "%s" has been installed successfully.', 'acrossai-core-abilities' ), $api->name );
		}

		return array(
			'success'     => true,
			'message'     => $message,
			'plugin_name' => $api->name,
			'plugin_slug' => $plugin_slug,
			'activated'   => $activated,
		);
	}
}
