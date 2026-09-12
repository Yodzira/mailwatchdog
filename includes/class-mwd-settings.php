<?php
/**
 * Split classes.
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MWD_Settings {

	const OPTION = 'mwd_settings';

	public static function defaults() {
		return array(
			'token' => '',
			'chat'  => '',
		);
	}

	public static function get() {
		$stored = get_option( self::OPTION, array() );

		return array_merge(
			self::defaults(),
			is_array( $stored ) ? array_intersect_key( $stored, self::defaults() ) : array()
		);
	}

	public static function save( $in ) {
		$in   = is_array( $in ) ? $in : array();
		$clean = array(
			'token' => substr( preg_replace( '/[^0-9:A-Za-z_\-]/', '', (string) ( isset( $in['token'] ) ? $in['token'] : '' ) ), 0, 64 ),
			'chat'  => substr( preg_replace( '/[^0-9@A-Za-z_\-]/', '', (string) ( isset( $in['chat'] ) ? $in['chat'] : '' ) ), 0, 64 ),
		);
		update_option( self::OPTION, $clean );

		return $clean;
	}
}
