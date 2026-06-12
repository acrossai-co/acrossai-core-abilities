<?php
namespace Acrossai_Core_Abilities\Includes;

use Acrossai_Core_Abilities\Includes\Abilities\Block;
use Acrossai_Core_Abilities\Includes\Abilities\Cache;
use Acrossai_Core_Abilities\Includes\Abilities\Database;
use Acrossai_Core_Abilities\Includes\Abilities\FileManager;
use Acrossai_Core_Abilities\Includes\Abilities\Plugins;
use Acrossai_Core_Abilities\Includes\Abilities\Roles;
use Acrossai_Core_Abilities\Includes\Abilities\Sessions;
use Acrossai_Core_Abilities\Includes\Abilities\Themes;
use Acrossai_Core_Abilities\Includes\Abilities\Users;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/WPBoilerplate/acrossai-core-abilities
 * @since      0.0.1
 *
 * @package    Acrossai_Core_Abilities
 * @subpackage Acrossai_Core_Abilities/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.1
 * @package    Acrossai_Core_Abilities
 * @subpackage Acrossai_Core_Abilities/includes
 * @author     WPBoilerplate <contact@wpboilerplate.com>
 */
final class Main {

	/**
	 * The single instance of the class.
	 *
	 * @var Acrossai_Core_Abilities
	 * @since 0.0.1
	 */
	protected static $_instance = null;

	/**
	 * The autoloader instance.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      Autoloader    $autoloader    The plugin autoloader instance.
	 */
	protected $autoloader;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      Acrossai_Core_Abilities_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The plugin dir path
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_path    The string for plugin dir path
	 */
	protected $plugin_path;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->define_constants();

		$this->plugin_name = 'acrossai-core-abilities';
		$this->plugin_dir = ACROSSAI_CORE_ABILITIES_PLUGIN_PATH;

		$this->load_composer_dependencies();

		$this->load_dependencies();

		$this->set_locale();

		$this->load_hooks();
	}

	/**
	 * Main Acrossai_Core_Abilities Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 0.0.1
	 * @static
	 * @see Acrossai_Core_Abilities()
	 * @return Acrossai_Core_Abilities - Main instance.
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Define WCE Constants
	 */
	private function define_constants() {

		$this->define( 'ACROSSAI_CORE_ABILITIES_PLUGIN_BASENAME', plugin_basename( \ACROSSAI_CORE_ABILITIES_PLUGIN_FILE ) );
		$this->define( 'ACROSSAI_CORE_ABILITIES_PLUGIN_PATH', plugin_dir_path( \ACROSSAI_CORE_ABILITIES_PLUGIN_FILE ) );
		$this->define( 'ACROSSAI_CORE_ABILITIES_PLUGIN_URL', plugin_dir_url( \ACROSSAI_CORE_ABILITIES_PLUGIN_FILE ) );
		$this->define( 'ACROSSAI_CORE_ABILITIES_PLUGIN_NAME_SLUG', $this->plugin_name );
		$this->define( 'ACROSSAI_CORE_ABILITIES_PLUGIN_NAME', 'Acrossai Core Abilities' );
		$this->define( 'ACROSSAI_CORE_ABILITIES_VERSION', '0.0.2' );
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Register all the hook once all the active plugins are loaded
	 *
	 * Uses the plugins_loaded to load all the hooks and filters
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	public function load_hooks() {

		/**
		 * Reserved for future hook registration.
		 *
		 * Use the `acrossai_core_abilities_load` filter to gate plugin loading.
		 *
		 * @since    0.0.1
		 */
		apply_filters( 'acrossai_core_abilities_load', true );

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Plugins\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Themes\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			FileManager\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Cache\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Database\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Users\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Roles\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Sessions\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Block\Category_Registrar::instance(),
			'register'
		);

		add_action(
			'plugins_loaded',
			static function (): void {
				if ( ! class_exists( '\AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition' ) ) {
					return;
				}
				new Plugins\Plugin_Activate();
				new Plugins\Plugin_Deactivate();
				new Plugins\Plugin_Install();
				new Plugins\Plugin_List();
				new Plugins\Update_Check();
				new Themes\Theme_Activate();
				new Themes\Theme_Delete();
				new Themes\Theme_Install();
				new Themes\Theme_List();
				new Users\User_Get();
				new Users\User_List();
				new Users\User_Meta_Get();
				new Users\User_Create();
				new Users\User_Update();
				new Users\User_Delete();
				new Users\User_Meta_Update();
				new Users\User_Password_Reset();
				new Roles\Role_Assign();
				new Roles\Role_Remove();
				new Roles\Role_List();
				new Roles\Role_Capabilities_Get();
				new Sessions\Session_Force_Logout();
				new Sessions\Session_List_Active();
				new Cache\Cache_Flush();
				new Cache\Cache_Transient_Flush();
				new Cache\Cache_Rewrite_Flush();
				new Database\Schema_Extract();
				new Database\Db_Select();
				new Database\Db_Insert();
				new Database\Db_Update();
				new Database\Db_Delete();
				new Database\Tables_List();
				new Database\Db_Explain();
				new Database\Db_Stats();
				new Database\Db_Optimize();
				new FileManager\File_Read();
				new FileManager\File_Create();
				new FileManager\File_Edit();
				new FileManager\File_Delete();
				new FileManager\Plugin_Structure_Read();
				new FileManager\Plugin_Code_Read();
				new FileManager\Plugin_Files_Manage();
				new FileManager\Theme_Structure_Read();
				new FileManager\Theme_Code_Read();
				new FileManager\Theme_Files_Edit();
				new FileManager\Theme_Json_Read();
				new FileManager\Theme_Json_Update();
				new FileManager\Wp_Config_Read();
				new FileManager\Wp_Config_Edit();
				new FileManager\Debug_Log_Read();
				new FileManager\Debug_Log_Clear();
				new Block\Pattern_List();
				new Block\Pattern_Read();
				new Block\Pattern_Create();
				new Block\Pattern_Update();
				new Block\Pattern_Delete();
			},
			20
		);
	}

	/**
	 * Load the required composer dependencies for this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function load_composer_dependencies() {

		/**
		 * Add composer file
		 */
		$plugin_path = ACROSSAI_CORE_ABILITIES_PLUGIN_PATH;

		if ( file_exists( $plugin_path . 'vendor/autoload_packages.php' ) ) {
			require_once $plugin_path . 'vendor/autoload_packages.php';
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function load_dependencies() {

		$this->loader = Loader::instance();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Acrossai_Core_Abilities_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function set_locale() {
		$i18n = new I18n();

		// Now attach it to `init`, not `plugins_loaded`
		$this->loader->add_action( 'init', $i18n, 'do_load_textdomain' );
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.0.1
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.0.1
	 * @return    Acrossai_Core_Abilities_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * The reference to the autoloader instance.
	 *
	 * @since     0.0.1
	 * @return    Autoloader    The plugin autoloader instance.
	 */
	public function get_autoloader() {
		return $this->autoloader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
