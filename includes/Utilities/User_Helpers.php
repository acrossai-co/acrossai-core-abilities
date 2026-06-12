<?php
namespace Acrossai_Core_Abilities\Includes\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers for resolving and formatting users.
 */
class User_Helpers {

	/**
	 * Resolve a user identifier (ID, login, email, or slug) to a WP_User.
	 *
	 * Tries in order: numeric ID, email (if contains @), login, slug.
	 *
	 * @param mixed $identifier User ID (int/string), login, email, or slug.
	 * @return \WP_User|null
	 */
	public static function resolve_user( $identifier ): ?\WP_User {
		if ( is_numeric( $identifier ) ) {
			$user = get_user_by( 'id', (int) $identifier );
			if ( $user ) {
				return $user;
			}
		}

		if ( ! is_string( $identifier ) ) {
			return null;
		}

		$identifier = trim( $identifier );
		if ( '' === $identifier ) {
			return null;
		}

		if ( str_contains( $identifier, '@' ) ) {
			$user = get_user_by( 'email', $identifier );
			if ( $user ) {
				return $user;
			}
		}

		$user = get_user_by( 'login', $identifier );
		if ( $user ) {
			return $user;
		}

		$user = get_user_by( 'slug', $identifier );
		if ( $user ) {
			return $user;
		}

		return null;
	}

	/**
	 * Format a WP_User as an array of safe public fields.
	 *
	 * @param \WP_User $user
	 * @return array
	 */
	public static function format_user( \WP_User $user ): array {
		return array(
			'id'           => (int) $user->ID,
			'login'        => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'first_name'   => (string) $user->first_name,
			'last_name'    => (string) $user->last_name,
			'slug'         => $user->user_nicename,
			'url'          => $user->user_url,
			'registered'   => $user->user_registered,
			'roles'        => array_values( (array) $user->roles ),
		);
	}

	/**
	 * Decode a meta value if it looks like JSON, otherwise return as-is.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function maybe_decode_json( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$trimmed = ltrim( $value );
		if ( '' === $trimmed || ( '{' !== $trimmed[0] && '[' !== $trimmed[0] ) ) {
			return $value;
		}

		$decoded = json_decode( $value, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return $value;
		}

		return $decoded;
	}
}
