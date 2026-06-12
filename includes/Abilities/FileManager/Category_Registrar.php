<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\FileManager;

defined( 'ABSPATH' ) || exit;

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
			'acrossai-core-abilities-file-manager',
			array(
				'label'       => __( 'Acrossai Core Abilities — File Manager', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for reading, creating, editing, and deleting files across WordPress plugins, themes, configuration, and logs.', 'acrossai-core-abilities' ),
			)
		);
	}
}
