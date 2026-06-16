<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Menus;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by Menu + Menu Item abilities.
 */
final class Category_Registrar {

	/** @var self|null */
	protected static $_instance = null;

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function register(): void {
		wp_register_ability_category(
			'acrossai-core-abilities-menus',
			array(
				'label'       => __( 'Acrossai Core Abilities — Menus', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for managing nav menus and menu items via the core REST endpoints.', 'acrossai-core-abilities' ),
			)
		);
	}
}
