<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Media;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by all Media Library abilities.
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
			'acrossai-core-abilities-media',
			array(
				'label'       => __( 'Acrossai Core Abilities — Media', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for managing the Media Library: upload, list, read, update, delete, and meta access.', 'acrossai-core-abilities' ),
			)
		);
	}
}
