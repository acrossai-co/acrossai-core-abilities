<?php
namespace Acrossai_Core_Abilities\Includes;

use Acrossai_Core_Abilities\Includes\Abilities\Block;
use Acrossai_Core_Abilities\Includes\Abilities\Cache;
use Acrossai_Core_Abilities\Includes\Abilities\Comments;
use Acrossai_Core_Abilities\Includes\Abilities\Content;
use Acrossai_Core_Abilities\Includes\Abilities\Cron;
use Acrossai_Core_Abilities\Includes\Abilities\Database;
use Acrossai_Core_Abilities\Includes\Abilities\FileManager;
use Acrossai_Core_Abilities\Includes\Abilities\Fonts;
use Acrossai_Core_Abilities\Includes\Abilities\Media;
use Acrossai_Core_Abilities\Includes\Abilities\Menus;
use Acrossai_Core_Abilities\Includes\Abilities\Options as Options_Abilities;
use Acrossai_Core_Abilities\Includes\Abilities\Plugins;
use Acrossai_Core_Abilities\Includes\Abilities\Settings;
use Acrossai_Core_Abilities\Includes\Abilities\SiteHealth;
use Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;
use Acrossai_Core_Abilities\Includes\Abilities\Themes;
use Acrossai_Core_Abilities\Includes\Abilities\Users;
use Acrossai_Core_Abilities\Includes\Utilities\Cron_Helpers;

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
		$this->define( 'ACROSSAI_CORE_ABILITIES_VERSION', '0.0.7' );
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
			Block\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Settings\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Fonts\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Content\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Taxonomies\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Media\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Comments\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Menus\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Options_Abilities\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			Cron\Category_Registrar::instance(),
			'register'
		);

		$this->loader->add_action(
			'wp_abilities_api_categories_init',
			SiteHealth\Category_Registrar::instance(),
			'register'
		);

		Cron_Helpers::register_filter();

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
				new Settings\Permalink_Get();
				new Settings\Permalink_Set();
				new Settings\Permalink_Flush();
				new Settings\Site_Title_Get();
				new Settings\Site_Title_Update();
				new Settings\Tagline_Get();
				new Settings\Tagline_Update();
				new Settings\Site_Icon_Get();
				new Settings\Site_Icon_Update();
				new Themes\Theme_Activate();
				new Themes\Theme_Delete();
				new Themes\Theme_Install();
				new Themes\Theme_List();
				new Users\User_Get();
				new Users\User_List();
				new Users\User_Create();
				new Users\User_Update();
				new Users\User_Delete();
				new Users\User_Password_Reset();
				new Users\Roles_List();
				new Users\Role_Capabilities();
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
				new Plugins\Plugin_Structure_Read();
				new Plugins\Plugin_Code_Read();
				new Plugins\Plugin_Files_Manage();
				new Themes\Theme_Structure_Read();
				new Themes\Theme_Code_Read();
				new Themes\Theme_Files_Edit();
				new FileManager\Wp_Config_Read();
				new FileManager\Wp_Config_Edit();
				new FileManager\Debug_Log_Read();
				new FileManager\Debug_Log_Clear();
				new Block\Pattern_List();
				new Block\Pattern_Read();
				new Block\Pattern_Create();
				new Block\Pattern_Update();
				new Block\Pattern_Delete();
				new Block\Template_List();
				new Block\Template_Read();
				new Block\Template_Create();
				new Block\Template_Update();
				new Block\Template_Delete();
				new Block\Global_Styles_List();
				new Block\Global_Styles_Read();
				new Block\Global_Styles_Create();
				new Block\Global_Styles_Update();
				new Block\Global_Styles_Delete();
				new Block\Theme_Json_Read();
				new Block\Theme_Json_Update();
				new Block\Block_Style_Variations_List();
				new Block\Block_Style_Variations_Read();
				new Block\Block_Style_Variations_Create();
				new Block\Block_Style_Variations_Update();
				new Block\Block_Style_Variations_Delete();
				new Block\Block_Info_List();
				new Block\Block_Info_Read();
				new Block\Template_Part_List();
				new Block\Template_Part_Read();
				new Block\Template_Part_Create();
				new Block\Template_Part_Update();
				new Block\Template_Part_Delete();
				new Fonts\Font_Family_List();
				new Fonts\Font_Family_Get();
				new Fonts\Font_Family_Create();
				new Fonts\Font_Family_Delete();
				new Fonts\Font_Face_List();
				new Fonts\Font_Face_Get();
				new Fonts\Font_Face_Create();
				new Fonts\Font_Face_Delete();
				new Content\Create_Post();
				new Content\Get_Post();
				new Content\Get_Posts();
				new Content\Update_Post();
				new Content\Delete_Post();
				new Content\Get_Post_Meta();
				new Content\Update_Post_Meta();
				new Content\Create_Page();
				new Content\Get_Page();
				new Content\Get_Pages();
				new Content\Update_Page();
				new Content\List_Post_Types();
				new Content\Create_Cpt_Item();
				new Content\Get_Cpt_Item();
				new Content\Get_Cpt_Items();
				new Content\Update_Cpt_Item();
				new Content\Delete_Cpt_Item();
				new Content\Get_Post_Translations();
				new Content\Set_Post_Language();
				new Content\Link_Post_Translation();
				new Content\Je_List_Options_Pages();
				new Content\Je_Get_Options_Page();
				new Content\Je_Update_Options_Page_Field();
				new Taxonomies\List_Taxonomies();
				new Taxonomies\Get_Taxonomy();
				new Taxonomies\Get_Cpt_Taxonomies();
				new Taxonomies\List_Terms();
				new Taxonomies\Get_Term();
				new Taxonomies\Create_Term();
				new Taxonomies\Update_Term();
				new Taxonomies\Delete_Term();
				new Taxonomies\Assign_Cpt_Terms();
				new Media\Upload_Media();
				new Media\Get_Media();
				new Media\List_Media();
				new Media\Update_Media();
				new Media\Delete_Media();
				new Media\Get_Media_Meta();
				new Media\Update_Media_Meta();
				new Comments\Create_Comment();
				new Comments\Get_Comment();
				new Comments\List_Comments();
				new Comments\Update_Comment();
				new Comments\Delete_Comment();
				new Comments\Approve_Comment();
				new Comments\Unapprove_Comment();
				new Comments\Mark_As_Spam();
				new Comments\Get_Comment_Meta();
				new Comments\Update_Comment_Meta();
				new Menus\List_Menus();
				new Menus\Get_Menu();
				new Menus\Create_Menu();
				new Menus\Update_Menu();
				new Menus\Delete_Menu();
				new Menus\List_Menu_Items();
				new Menus\Get_Menu_Item();
				new Menus\Create_Menu_Item();
				new Menus\Update_Menu_Item();
				new Menus\Delete_Menu_Item();
				new Options_Abilities\Get_Option();
				new Options_Abilities\Update_Option();
				new Options_Abilities\Delete_Option();
				new Options_Abilities\List_Options();
				new Options_Abilities\Search_Options();
				new Cron\Cron_List();
				new Cron\Cron_Get();
				new Cron\Cron_Next_Run();
				new Cron\Cron_Exists();
				new Cron\Cron_List_Schedules();
				new Cron\Cron_Get_Schedule();
				new Cron\Cron_Status();
				new Cron\Cron_Overdue();
				new Cron\Cron_Create();
				new Cron\Cron_Update();
				new Cron\Cron_Run_Now();
				new Cron\Cron_Create_Schedule();
				new Cron\Cron_Delete();
				new Cron\Cron_Delete_All_By_Hook();
				new Cron\Cron_Delete_Schedule();
				new SiteHealth\Site_Health_Status();
				new SiteHealth\Site_Health_Info();
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
