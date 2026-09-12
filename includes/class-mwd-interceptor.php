<?php
/**
 * Intercepts wp_mail: records outcomes, alerts on a broken streak,
 * resends failed mails.
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MWD_Interceptor {

	/** @var array Envelope of the wp_mail call in flight. */
	private static $current = array();

	/** @var bool Inside our own alert mail — do not watch it. */
	private static $skip = false;

	public static function boot() {
		// Record the envelope, let the call proceed (return null).
		add_filter( 'pre_wp_mail', array( __CLASS__, 'capture_envelope' ), 9999, 2 );
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'on_success' ) );
		add_action( 'wp_mail_failed', array( __CLASS__, 'on_failed' ) );
		add_action( 'mwd_prune', array( __CLASS__, 'run_prune' ) );
	}

	/**
	 * Capture envelope; never short-circuits (returns null).
	 *
	 * @param null  $null Always null.
	 * @param array $atts {to, subject, message, headers, attachments}.
	 * @return null
	 */
	public static function capture_envelope( $null, $atts ) {
		if ( self::$skip ) {
			return $null;
		}
		$to      = isset( $atts['to'] ) ? $atts['to'] : '';
		$trace   = version_compare( PHP_VERSION, '8.0', '>=' ) ? debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 ) : debug_backtrace( false, 10 ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters -- PHP 8 only.
		self::$current = array(
			'to'      => is_array( $to ) ? implode( ', ', $to ) : (string) $to,
			'subject' => isset( $atts['subject'] ) ? (string) $atts['subject'] : '',
			'source'  => MWD_Classifier::source_from_trace( $trace ),
			'message' => isset( $atts['message'] ) ? (string) $atts['message'] : '',
			'headers' => isset( $atts['headers'] ) ? (array) $atts['headers'] : array(),
		);

		return $null;
	}

	/**
	 * Success path.
	 *
	 * @param array $mail_data {to, subject, ...}.
	 * @return void
	 */
	public static function on_success( $mail_data = array() ) {
		if ( self::$skip ) {
			return;
		}
		$streak = new MWD_Streak( (int) get_option( 'mwd_streak', 0 ) );
		$streak->register( true );
		update_option( 'mwd_streak', $streak->count(), false );

		MWD_Store::record(
			array(
				'to'      => self::current( 'to', isset( $mail_data['to'] ) ? $mail_data['to'] : '' ),
				'subject' => self::current( 'subject', isset( $mail_data['subject'] ) ? $mail_data['subject'] : '' ),
				'status'  => 'sent',
				'source'  => self::current( 'source' ),
			)
		);
	}

	/**
	 * Failure path: record, count the streak, alert at the threshold.
	 *
	 * @param WP_Error $error Mail error.
	 * @return void
	 */
	public static function on_failed( $error ) {
		if ( self::$skip ) {
			return;
		}
		$message = is_object( $error ) && method_exists( $error, 'get_error_message' ) ? $error->get_error_message() : '';
		$parsed  = MWD_Classifier::classify( $message );

		$settings = MWD_Settings::get();
		$streak   = new MWD_Streak( (int) get_option( 'mwd_streak', 0 ) );
		$verdict  = $streak->register( false );
		update_option( 'mwd_streak', $streak->count(), false );

		MWD_Store::record(
			array(
				'to'             => self::current( 'to' ),
				'subject'        => self::current( 'subject' ),
				'status'         => 'failed',
				'category'       => $parsed['category'],
				'reason'         => $parsed['human'],
				'source'         => self::current( 'source' ),
				'resend_payload' => array(
					'to'      => self::current( 'to' ),
					'subject' => self::current( 'subject' ),
					'message' => substr( self::current( 'message' ), 0, 20000 ),
					'headers' => self::current( 'headers' ),
				),
			)
		);

		if ( 'alert' === $verdict ) {
			self::alert( $streak->count(), $parsed['human'], $settings );
		}
	}

	/**
	 * Broken-mail alert: Telegram when configured, admin email always.
	 *
	 * @param int   $count   Streak count.
	 * @param string $reason  Human reason.
	 * @param array $settings Settings.
	 * @return void
	 */
	private static function alert( $count, $reason, $settings ) {
		$text = '🔴 Mail Watchdog — почта сайта не работает. Последних провалов: ' . (int) $count . '. Причина: ' . $reason;
		if ( '' !== $settings['token'] && '' !== $settings['chat'] ) {
			( new MWD_Telegram() )->send( $settings['token'], $settings['chat'], $text );
		}
		// Our own alert must not feed back into the watchdog (no MTA => it
		// would fail and inflate the streak forever).
		self::$skip = true;
		wp_mail( get_option( 'admin_email' ), '🔴 Site mail is broken', $text );
		self::$skip = false;
	}

	/**
	 * Resend a failed mail.
	 *
	 * @param int $row_id Log row.
	 * @return bool|WP_Error
	 */
	public static function resend( $row_id ) {
		$payload = MWD_Store::resend_payload( (int) $row_id );
		if ( ! $payload || ! isset( $payload['to'] ) ) {
			return false;
		}

		return wp_mail(
			$payload['to'],
			isset( $payload['subject'] ) ? $payload['subject'] : '',
			isset( $payload['message'] ) ? $payload['message'] : '',
			isset( $payload['headers'] ) ? $payload['headers'] : array()
		);
	}

	/**
	 * Daily prune.
	 *
	 * @return void
	 */
	public static function run_prune() {
		MWD_Store::prune( 30 );
	}

	private static function current( $key, $default = '' ) {
		return isset( self::$current[ $key ] ) && '' !== self::$current[ $key ] ? self::$current[ $key ] : $default;
	}
}
