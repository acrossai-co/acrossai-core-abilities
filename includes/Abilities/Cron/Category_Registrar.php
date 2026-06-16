<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Cron;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by all WP-Cron abilities.
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
			'acrossai-core-abilities-cron',
			array(
				'label'       => __( 'Acrossai Core Abilities — Cron', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for inspecting and managing WP-Cron: list/get/check scheduled events, list and define schedules, run hooks on demand, and delete events.', 'acrossai-core-abilities' ),
			)
		);
	}
}
