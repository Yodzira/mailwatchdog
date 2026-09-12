<?php
/**
 * Plugin Name:       Mail Watchdog
 * Plugin URI:        https://github.com/Yodzira/mailwatchdog
 * Description:       Track every WordPress email: who sent it, did it leave, why it failed. Alert when mail breaks, resend failed messages. wp_mail fails silently — Mail Watchdog doesn't.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yodzira
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mailwatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MWD_VERSION', '0.1.0' );
define( 'MWD_FILE', __FILE__ );
define( 'MWD_DIR', __DIR__ );

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

add_action( 'plugins_loaded', array( 'MWD_Plugin', 'boot' ), 20 );

register_activation_hook(
	__FILE__,
	static function () {
		require_once MWD_DIR . '/includes/class-mwd-store.php';
		MWD_Store::activate();
		if ( ! wp_next_scheduled( 'mwd_prune' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mwd_prune' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'mwd_prune' );
	}
);
