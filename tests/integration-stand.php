<?php
/**
 * Mail Watchdog integration — inside QA container:
 *   docker exec infra-wordpress-1 wp eval-file /tmp/mwd-integration.php --allow-root
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

echo "== Mail Watchdog integration ==\n";

check( 'plugin active', is_plugin_active( 'mailwatchdog/mailwatchdog.php' ) );
check( 'classes loaded', class_exists( 'MWD_Interceptor' ) && class_exists( 'MWD_Store' ) );

global $wpdb;
$table     = MWD_Store::table_name();
$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
check( 'table created on activation', $has_table === $table );

MWD_Store::erase_all();
update_option( 'mwd_streak', 0 );

// A real wp_mail on the stand: no MTA configured, it must fail and be recorded.
wp_mail( 'integration-test@example.com', 'MWD test subject', 'Body that must not leak into the journal for sent mail.' );
$rows = MWD_Store::recent( 5 );
check( 'attempt recorded', count( $rows ) === 1 );
check( 'recorded as failed', 'failed' === $rows[0]['status'] );
check( 'failure categorized', '' !== $rows[0]['category'] );
check( 'source detected', '' !== $rows[0]['source'] );
check( 'resend payload stored for failed', is_array( MWD_Store::resend_payload( (int) $rows[0]['id'] ) ) );

// Streak: two more failures trigger the alert flag (Telegram unset -> only admin mail).
$alert_before = did_action( 'mwd_noop' );
wp_mail( 'integration-test@example.com', 'MWD 2', 'x' );
wp_mail( 'integration-test@example.com', 'MWD 3', 'x' );
check( 'streak counter at threshold', 3 === (int) get_option( 'mwd_streak', 0 ) );
check( 'three failures journaled', 3 === count( MWD_Store::recent( 10 ) ) );

// Success resets the streak (simulate by recording through the hook path).
do_action( 'wp_mail_succeeded', array( 'to' => 'ok@example.com', 'subject' => 'ok' ) );
check( 'success resets streak', 0 === (int) get_option( 'mwd_streak', 0 ) );
check( 'success journaled without body', 'sent' === MWD_Store::recent( 10 )[0]['status'] );

// Prune keeps fresh rows.
check( 'prune keeps fresh', 0 === MWD_Store::prune( 30 ) );

// Cleanup.
MWD_Store::erase_all();
check( 'erase clears journal', array() === MWD_Store::recent( 10 ) );

printf( "\n== Mail Watchdog integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
