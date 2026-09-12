<?php

use PHPUnit\Framework\TestCase;

/**
 * Classifier + source detection + streak.
 */
class ClassifierTest extends TestCase {

	public function test_categories() {
		$cases = array(
			'SMTP connect() failed: Connection refused' => 'smtp_connect',
			'The following From address failed: a@b.c' => 'other',
			'Invalid address: (to): not-an-email'      => 'bad_address',
			'SMTP Error: Could not authenticate.'      => 'auth',
			'blocked by spam filter; rejected'         => 'rejected',
		);
		foreach ( $cases as $raw => $expected ) {
			$out = MWD_Classifier::classify( $raw );
			$this->assertSame( $expected, $out['category'], $raw );
			$this->assertNotSame( '', $out['human'] );
		}
	}

	public function test_classify_strips_tags_on_fallback() {
		$out = MWD_Classifier::classify( 'Weird <script>alert(1)</script> failure' );
		$this->assertSame( 'other', $out['category'] );
		$this->assertStringNotContainsString( '<script>', $out['human'] );
	}

	public function test_source_from_trace() {
		$trace = array(
			array( 'file' => '/var/www/html/wp-includes/pluggable.php' ),
			array( 'file' => '/var/www/html/wp-content/plugins/woocommerce/includes/emails/class-wc-email.php' ),
			array( 'file' => '/var/www/html/wp-content/plugins/mailwatchdog/includes/x.php' ),
		);
		$this->assertSame( 'WooCommerce', MWD_Classifier::source_from_trace( $trace ) );

		$core = array( array( 'file' => '/var/www/html/wp-includes/pluggable.php' ) );
		$this->assertSame( 'WordPress core', MWD_Classifier::source_from_trace( $core ) );

		$this->assertSame( 'Unknown source', MWD_Classifier::source_from_trace( array() ) );
	}

	public function test_streak_alerts_on_third_failure_and_resets_on_success() {
		$streak = new MWD_Streak();
		$this->assertSame( 'quiet', $streak->register( false ) );
		$this->assertSame( 'quiet', $streak->register( false ) );
		$this->assertSame( 'alert', $streak->register( false ) );
		$this->assertSame( 3, $streak->count() );

		$this->assertSame( 'reset', $streak->register( true ) );
		$this->assertSame( 0, $streak->count() );

		// No repeated alerts while failing further.
		$this->assertSame( 'quiet', $streak->register( false ) );
		$this->assertSame( 'quiet', $streak->register( false ) );
		$this->assertSame( 'alert', $streak->register( false ) );
	}

	public function test_settings_defaults_and_save() {
		$GLOBALS['__mwd_options'] = array();
		$clean = MWD_Settings::save( array( 'token' => "12:ab#cd", 'chat' => '@x', 'extra' => 'junk' ) );
		$this->assertSame( '12:abcd', $clean['token'] );
		$this->assertSame( '@x', $clean['chat'] );
		$this->assertSame( MWD_Settings::get(), $clean );
	}
}
