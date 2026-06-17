<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\SiteHealth;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WP ability category used by all Site Health abilities.
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
			'acrossai-core-abilities-site-health',
			array(
				'label'       => __( 'Acrossai Core Abilities — Site Health', 'acrossai-core-abilities' ),
				'description' => __( 'Abilities for inspecting Site Health: run the direct status checks (good/recommended/critical) and read the full Site Health Info (server, database, WordPress, theme, plugins, media, filesystem, constants).', 'acrossai-core-abilities' ),
			)
		);
	}
}
