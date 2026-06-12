<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Sessions;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category for session/security abilities.
 *
 * Must run on wp_abilities_api_categories_init — before the Library Processor
 * calls wp_register_ability() at wp_abilities_api_init P5.
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
			'acrossai-core-abilities-sessions',
			array(
				'label'       => __( 'Acrossai Core Abilities — Sessions & Security', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for inspecting and revoking active user sessions.', 'acrossai-core-abilities' ),
			)
		);
	}
}
