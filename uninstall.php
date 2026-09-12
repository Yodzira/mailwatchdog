<?php
/**
 * Uninstall cleanup.
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

hgd_style_cleanup();

/**
 * Remove every trace of the plugin.
 *
 * @global wpdb $wpdb
 * @return void
 */
function hgd_style_cleanup() {
	global $wpdb;

	delete_option( 'mwd_settings' );
	delete_option( 'mwd_streak' );
	wp_clear_scheduled_hook( 'mwd_prune' );

	$table = $wpdb->prefix . 'mwd_mail';
	// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix only.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}
