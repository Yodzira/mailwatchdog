<?php
/**
 * Split classes.
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MWD_Telegram {

	/** @var callable|null */
	private $transport;

	public function __construct( $transport = null ) {
		$this->transport = $transport;
	}

	/**
	 * Send a message; true when Telegram confirmed.
	 *
	 * @param string $token Token.
	 * @param string $chat  Chat.
	 * @param string $text  Text.
	 * @return bool
	 */
	public function send( $token, $chat, $text ) {
		if ( '' === trim( (string) $token ) || '' === trim( (string) $chat ) || '' === trim( (string) $text ) ) {
			return false;
		}
		$url      = 'https://api.telegram.org/bot' . rawurlencode( (string) $token ) . '/sendMessage';
		$response = wp_remote_post( $url, array( 'timeout' => 3, 'body' => array( 'chat_id' => (string) $chat, 'text' => (string) $text ) ) );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $payload ) && ! empty( $payload['ok'] );
	}
}
