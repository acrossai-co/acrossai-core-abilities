<?php
namespace Acrossai_Core_Abilities\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Define the internationalization functionality
 *
 * @package    Acrossai_Core_Abilities
 * @subpackage Acrossai_Core_Abilities/includes
 */
class I18n {

	/**
	 * Actually load the plugin textdomain on `init`
	 */
	public function do_load_textdomain() {
		load_plugin_textdomain(
			'acrossai-core-abilities',
			false,
			plugin_basename( dirname( \ACROSSAI_CORE_ABILITIES_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
