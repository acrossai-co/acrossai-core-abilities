<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by all Comment abilities.
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
			'acrossai-core-abilities-comments',
			array(
				'label'       => __( 'Acrossai Core Abilities — Comments', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for managing comments: CRUD, moderation (approve / hold / spam), and meta.', 'acrossai-core-abilities' ),
			)
		);
	}
}
