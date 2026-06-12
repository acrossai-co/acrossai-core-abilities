<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for theme block-pattern abilities.
 *
 * Spec: https://developer.wordpress.org/themes/patterns/introduction-to-patterns/
 */
final class Pattern_Helper {

	/**
	 * Headers recognised by core when scanning theme /patterns/*.php files.
	 * Order is preserved when generating new files.
	 *
	 * @var string[]
	 */
	private const HEADERS = array(
		'Title',
		'Slug',
		'Description',
		'Viewport Width',
		'Inserter',
		'Categories',
		'Keywords',
		'Block Types',
		'Post Types',
		'Template Types',
	);

	/**
	 * Resolves and validates a theme directory.
	 *
	 * @param string $slug Theme folder slug. Empty means active theme.
	 * @return string|\WP_Error Absolute theme directory path, or WP_Error on failure.
	 */
	public static function resolve_theme_dir( string $slug ) {
		$themes_dir = rtrim( get_theme_root(), '/' );
		$theme_dir  = '' !== $slug
			? realpath( $themes_dir . '/' . $slug )
			: realpath( get_stylesheet_directory() );

		if ( false === $theme_dir || 0 !== strpos( $theme_dir, $themes_dir ) || ! is_dir( $theme_dir ) ) {
			return new \WP_Error( 'theme_not_found', __( 'Theme directory not found.', 'acrossai-core-abilities' ) );
		}

		return $theme_dir;
	}

	/**
	 * Resolves the absolute path to a pattern file inside a theme's /patterns dir.
	 *
	 * @param string $theme_dir Absolute theme directory.
	 * @param string $filename  Pattern filename (e.g. "hero.php").
	 * @return string|\WP_Error
	 */
	public static function resolve_pattern_path( string $theme_dir, string $filename ) {
		$filename = self::sanitize_filename( $filename );
		if ( '' === $filename ) {
			return new \WP_Error( 'invalid_filename', __( 'Invalid pattern filename.', 'acrossai-core-abilities' ) );
		}

		$patterns_dir = $theme_dir . '/patterns';
		$abs_path     = $patterns_dir . '/' . $filename;

		// Validate the resolved path stays inside the patterns dir, even before the file exists.
		$resolved_dir = realpath( $patterns_dir );
		if ( false !== $resolved_dir ) {
			$candidate = realpath( $abs_path );
			if ( false !== $candidate && 0 !== strpos( $candidate, $resolved_dir ) ) {
				return new \WP_Error( 'path_escape', __( 'Pattern path escapes the theme /patterns directory.', 'acrossai-core-abilities' ) );
			}
		}

		return $abs_path;
	}

	/**
	 * Normalises a pattern filename to a safe `slug.php` form.
	 */
	public static function sanitize_filename( string $filename ): string {
		$filename = basename( $filename );
		if ( ! preg_match( '/\.php$/i', $filename ) ) {
			$filename .= '.php';
		}
		$base = preg_replace( '/\.php$/i', '', $filename );
		$base = sanitize_title( $base );
		if ( '' === $base ) {
			return '';
		}
		return $base . '.php';
	}

	/**
	 * Validates a fully-qualified pattern slug of the form `theme-slug/pattern-slug`.
	 */
	public static function is_valid_slug( string $slug ): bool {
		return (bool) preg_match( '#^[a-z0-9_-]+/[a-z0-9_-]+$#', $slug );
	}

	/**
	 * Builds the PHP header comment block for a new pattern file.
	 *
	 * @param array<string, string> $headers Header name => value.
	 * @return string PHP source including opening tag and closing `?>`.
	 */
	public static function build_file( array $headers, string $body ): string {
		$lines = array( '<?php', '/**' );
		foreach ( self::HEADERS as $name ) {
			if ( isset( $headers[ $name ] ) && '' !== $headers[ $name ] ) {
				$lines[] = ' * ' . $name . ': ' . self::sanitize_header_value( $headers[ $name ] );
			}
		}
		$lines[] = ' */';
		$lines[] = '?>';

		return implode( "\n", $lines ) . "\n" . $body;
	}

	/**
	 * Parses the doc-block headers from an existing pattern file.
	 *
	 * @return array{headers: array<string, string>, body: string}
	 */
	public static function parse_file( string $contents ): array {
		$headers = array();
		$body    = $contents;

		if ( preg_match( '#^\s*<\?php\s*/\*\*(.*?)\*/\s*\?>\s*#s', $contents, $m, PREG_OFFSET_CAPTURE ) ) {
			$header_block = $m[1][0];
			$body         = substr( $contents, $m[0][1] + strlen( $m[0][0] ) );

			foreach ( self::HEADERS as $name ) {
				if ( preg_match( '/^\s*\*?\s*' . preg_quote( $name, '/' ) . '\s*:\s*(.+)$/mi', $header_block, $hm ) ) {
					$headers[ $name ] = trim( $hm[1] );
				}
			}
		}

		return array(
			'headers' => $headers,
			'body'    => ltrim( $body, "\n" ),
		);
	}

	/**
	 * Strips line breaks from a header value so it can't break the doc block.
	 */
	private static function sanitize_header_value( string $value ): string {
		$value = (string) preg_replace( '/\s+/', ' ', $value );
		return trim( $value );
	}

	/**
	 * Returns the list of header field names a caller may set.
	 *
	 * @return string[]
	 */
	public static function header_fields(): array {
		return self::HEADERS;
	}
}
