<?php
namespace Acrossai_Core_Abilities\Admin\Partials;

/**
 * Acrossai_Core_Abilities_Main_Menu Main Menu Class.
 *
 * @since Acrossai_Core_Abilities_Main_Menu 0.0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Fired during plugin licences.
 *
 * This class defines all code necessary to run during the plugin's licences and update.
 *
 * @since      0.0.1
 * @package    Acrossai_Core_Abilities\Admin\Partials\Menu
 * @subpackage Acrossai_Core_Abilities\Admin\Partials
 */
class Menu {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Adds the plugin license page to the admin menu.
	 *
	 * @return void
	 */
	public function main_menu() {
		add_menu_page(
			__( 'Acrossai Core Abilities', 'acrossai-core-abilities' ),
			__( 'Acrossai Core Abilities', 'acrossai-core-abilities' ),
			'manage_options',
			'acrossai-core-abilities',
			array( $this, 'about' )
		);
	}

	/**
	 * About us for the plugins
	 */
	public function about() {
		?>
		<div class="acrossai-core-abilities-container">
			<div class="acrossai-core-abilities-content">
				<h2>Acrossai Core Abilities</h2>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Settings link to plugins area.
	 *
	 * @since    0.0.1
	 *
	 * @param array  $links Links array in which we would prepend our link.
	 * @param string $file  Current plugin basename.
	 * @return array Processed links.
	 */
	public function plugin_action_links( $links, $file ) {

		// Return normal links if not BuddyPress.
		if ( \ACROSSAI_CORE_ABILITIES_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		// Add a few links to the existing links array.
		return array_merge(
			$links,
			array(
				'about' => sprintf( '<a href="%sadmin.php?page=%s">%s</a>', admin_url(), 'acrossai-core-abilities', esc_html__( 'About', 'acrossai-core-abilities' ) ),
			)
		);
	}
}
