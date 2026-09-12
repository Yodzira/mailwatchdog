<?php
/**
 * Mail log storage.
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MWD_Store {

	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'mwd_mail';
	}

	public static function activate() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			logged_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			mail_to varchar(500) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			status varchar(10) NOT NULL DEFAULT '',
			category varchar(20) NOT NULL DEFAULT '',
			reason varchar(255) NOT NULL DEFAULT '',
			source varchar(100) NOT NULL DEFAULT '',
			resend_payload longtext NULL,
			PRIMARY KEY  (id),
			KEY logged_at (logged_at),
			KEY status (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Record one mail outcome.
	 *
	 * @param array $args {to, subject, status, category, reason, source, resend_payload}
	 * @return int Row id.
	 */
	public static function record( array $args ) {
		global $wpdb;

		$wpdb->insert(
			self::table_name(),
			array(
				'logged_at'      => current_time( 'mysql' ),
				'mail_to'        => substr( (string) ( isset( $args['to'] ) ? $args['to'] : '' ), 0, 500 ),
				'subject'        => substr( wp_strip_all_tags( (string) ( isset( $args['subject'] ) ? $args['subject'] : '' ) ), 0, 255 ),
				'status'         => in_array( isset( $args['status'] ) ? $args['status'] : '', array( 'sent', 'failed' ), true ) ? $args['status'] : 'failed',
				'category'       => substr( (string) ( isset( $args['category'] ) ? $args['category'] : '' ), 0, 20 ),
				'reason'         => substr( (string) ( isset( $args['reason'] ) ? $args['reason'] : '' ), 0, 255 ),
				'source'         => substr( (string) ( isset( $args['source'] ) ? $args['source'] : '' ), 0, 100 ),
				'resend_payload' => isset( $args['resend_payload'] ) ? wp_json_encode( $args['resend_payload'] ) : null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Resend payload of a failed mail.
	 *
	 * @param int $id Row.
	 * @return array|null
	 */
	public static function resend_payload( $id ) {
		global $wpdb;
		$table = self::table_name();

		$raw = $wpdb->get_var( $wpdb->prepare( "SELECT resend_payload FROM {$table} WHERE id = %d AND status = 'failed'", (int) $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL -- table from prefix.
		if ( ! $raw ) {
			return null;
		}

		return json_decode( $raw, true );
	}

	public static function recent( $limit = 100 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results( $wpdb->prepare( "SELECT id, logged_at, mail_to, subject, status, category, reason, source FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL -- table from prefix.
	}

	public static function prune( $days = 30 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE logged_at < DATE_SUB(%s, INTERVAL %d DAY)", current_time( 'mysql' ), (int) $days ) );
	}

	public static function erase_all() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.
	}

	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
