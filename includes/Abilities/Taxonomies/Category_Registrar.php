<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by all Taxonomy + Term abilities.
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
			'acrossai-core-abilities-taxonomies',
			array(
				'label'       => __( 'Acrossai Core Abilities — Taxonomies', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for inspecting taxonomies and managing terms across any taxonomy.', 'acrossai-core-abilities' ),
			)
		);
	}
}
