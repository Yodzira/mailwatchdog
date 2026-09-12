<?php
/**
 * Error classification + failure streak (pure).
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps raw PHPMailer/WP_Error messages to human categories.
 */
class MWD_Classifier {

	/**
	 * Category of a failure message.
	 *
	 * @param string $message Raw error message.
	 * @return array {category, human}
	 */
	public static function classify( $message ) {
		$message = (string) $message;
		$map     = array(
			'smtp connect'     => array( 'smtp_connect', 'Could not connect to the mail server (SMTP).' ),
			'connection failed' => array( 'smtp_connect', 'Could not connect to the mail server.' ),
			'authenticate'     => array( 'auth', 'Mail server rejected the login/password.' ),
			'auth'             => array( 'auth', 'Mail server rejected the login/password.' ),
			'invalid address'  => array( 'bad_address', 'The recipient address is invalid.' ),
			'invalid_addr'     => array( 'bad_address', 'The recipient address is invalid.' ),
			'timed out'        => array( 'timeout', 'The mail server did not answer in time.' ),
			'timeout'          => array( 'timeout', 'The mail server did not answer in time.' ),
			'rejected'         => array( 'rejected', 'The mail server refused the message (spam rules?).' ),
			'failed to send'   => array( 'send_failed', 'PHPMailer could not hand the message to the server.' ),
		);
		$lower = strtolower( $message );
		foreach ( $map as $needle => $out ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return array( 'category' => $out[0], 'human' => $out[1] );
			}
		}

		return array( 'category' => 'other', 'human' => $message !== '' ? wp_strip_all_tags( $message ) : 'Unknown mail error.' );
	}

	/**
	 * Sanitize a caller stack into "who sent this mail" (pure).
	 *
	 * @param array $trace debug_backtrace result (no args).
	 * @return string e.g. "WooCommerce (includes/emails/class-wc-email.php)"
	 */
	public static function source_from_trace( array $trace ) {
		foreach ( $trace as $frame ) {
			$file = isset( $frame['file'] ) ? (string) $frame['file'] : '';
			if ( '' === $file ) {
				continue;
			}
			if ( false !== strpos( $file, '/plugins/' ) ) {
				$rel  = substr( $file, strpos( $file, '/plugins/' ) + strlen( '/plugins/' ) );
				$slug = false !== strpos( $rel, '/' ) ? substr( $rel, 0, strpos( $rel, '/' ) ) : $rel;
				if ( '' !== $slug && 'mailwatchdog' !== strtolower( $slug ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure core.
					return self::pretty_slug( $slug );
				}
			}
		}
		foreach ( $trace as $frame ) {
			$file = isset( $frame['file'] ) ? (string) $frame['file'] : '';
			if ( '' !== $file && false !== strpos( $file, '/wp-includes/' ) ) {
				return 'WordPress core';
			}
		}

		return 'Unknown source';
	}

	/**
	 * Human plugin name from its slug (brand-aware).
	 *
	 * @param string $slug Slug.
	 * @return string
	 */
	public static function pretty_slug( $slug ) {
		$brands = array(
			'woocommerce'        => 'WooCommerce',
			'wp-mail-smtp'       => 'WP Mail SMTP',
			'contact-form-7'     => 'Contact Form 7',
			'wpforms-lite'       => 'WPForms',
			'jetpack'            => 'Jetpack',
		);
		$lower = strtolower( $slug );
		if ( isset( $brands[ $lower ] ) ) {
			return $brands[ $lower ];
		}

		return str_replace( array( '_', '-' ), ' ', ucwords( strtolower( $slug ), '_-' ) );
	}

}

/**
 * Consecutive-failure streak with an alert threshold (pure).
 */
class MWD_Streak {

	const ALERT_AFTER = 3;

	/** @var int */
	private $count;

	public function __construct( $count = 0 ) {
		$this->count = (int) $count;
	}

	/**
	 * Register one outcome.
	 *
	 * @param bool $success Whether wp_mail succeeded.
	 * @return string 'quiet' | 'alert' | 'reset'
	 */
	public function register( $success ) {
		if ( $success ) {
			$was         = $this->count;
			$this->count = 0;

			return $was >= self::ALERT_AFTER ? 'reset' : 'quiet';
		}
		$this->count++;
		if ( self::ALERT_AFTER === $this->count ) {
			return 'alert';
		}

		return 'quiet';
	}

	public function count() {
		return $this->count;
	}
}
