<?php
/**
 * Standalone bootstrap: classifier, streak, store-shimmed tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'MWD_DIR' ) ) {
	define( 'MWD_DIR', dirname( __DIR__ ) );
}
if ( ! defined( 'MWD_VERSION' ) ) {
	define( 'MWD_VERSION', '0.1.0-test' );
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = (string) $text;
	if ( strpos( $text, '<' ) !== false ) {
		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
		$text = strip_tags( $text );
	}
	if ( $remove_breaks ) {
		$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
	}

	return trim( $text );
}

$GLOBALS['__mwd_options'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['__mwd_options'] ) ? $GLOBALS['__mwd_options'][ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['__mwd_options'][ $key ] = $value;

	return true;
}

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'MWD_' ) ) {
			return;
		}
		$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, 4 ) ) );
		$snake = str_replace( '_', '-', $snake );
		$file  = MWD_DIR . '/includes/class-mwd-' . $snake . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);
