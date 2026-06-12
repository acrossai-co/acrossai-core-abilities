<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category for role-management abilities.
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
			'acrossai-core-abilities-roles',
			array(
				'label'       => __( 'Acrossai Core Abilities — Roles & Capabilities', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for assigning roles to users, inspecting role capabilities, and managing access control.', 'acrossai-core-abilities' ),
			)
		);
	}
}
