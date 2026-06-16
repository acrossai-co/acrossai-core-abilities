<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by Options abilities
 * (wp_options table — get/update/delete/list/search).
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
			'acrossai-core-abilities-options',
			array(
				'label'       => __( 'Acrossai Core Abilities — Options', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for reading, writing, and searching the wp_options table.', 'acrossai-core-abilities' ),
			)
		);
	}
}
