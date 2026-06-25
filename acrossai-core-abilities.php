<?php
/**
 * Instantiates the Acrossai Core Abilities plugin
 *
 * @package Acrossai_Core_Abilities
 */

namespace Acrossai_Core_Abilities;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/WPBoilerplate/acrossai-core-abilities
 * @since             0.0.1
 * @package           Acrossai_Core_Abilities
 *
 * @wordpress-plugin
 * Plugin Name:       Acrossai Core Abilities
 * Plugin URI:        https://github.com/WPBoilerplate/acrossai-core-abilities
 * Description:       Acrossai Core Abilities by WPBoilerplate
 * Version:           0.0.9
 * Requires at least: 6.9
 * Requires PHP:	  8.0
 * Author:            WPBoilerplate
 * Requires Plugins:  acrossai-abilities-manager
 * Author URI:        https://github.com/WPBoilerplate/acrossai-core-abilities
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       acrossai-core-abilities
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 0.0.1 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ACROSSAI_CORE_ABILITIES_PLUGIN_FILE', __FILE__ );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/activator.php
 */
function acrossai_core_abilities_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/Activator.php';
	Includes\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/deactivator.php
 */
function acrossai_core_abilities_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/Deactivator.php';
	Includes\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'Acrossai_Core_Abilities\acrossai_core_abilities_activate' );
register_deactivation_hook( __FILE__, 'Acrossai_Core_Abilities\acrossai_core_abilities_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/Main.php';

use Acrossai_Core_Abilities\Includes\Main;

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.1
 */
function acrossai_core_abilities_run() {

	$plugin = Main::instance();

	new \WPBoilerplate_Updater_Checker_Github(
		array(
			'repo'           => 'https://github.com/acrossai-co/acrossai-core-abilities',
			'file_path'      => ACROSSAI_CORE_ABILITIES_PLUGIN_FILE,
			'name_slug'      => 'acrossai-core-abilities',
			'release_branch' => 'main',
			'release-assets' => true,
		)
	);

	/**
	 * Run this plugin on the plugins_loaded functions
	 */
	add_action( 'plugins_loaded', array( $plugin, 'run' ), 0 );
}
acrossai_core_abilities_run();
