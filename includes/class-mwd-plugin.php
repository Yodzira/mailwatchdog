<?php
/**
 * Plugin boot + admin pages.
 *
 * @package MailWatchdog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MWD_Plugin {

	public static function boot() {
		MWD_Interceptor::boot();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( 'MWD_Admin', 'menu' ) );
			add_action( 'admin_post_mwd_save', array( 'MWD_Admin', 'handle_save' ) );
			add_action( 'admin_post_mwd_resend', array( 'MWD_Admin', 'handle_resend' ) );
		}
	}
}

class MWD_Admin {

	public static function menu() {
		add_menu_page( 'Mail Watchdog', 'Mail Watchdog', 'manage_options', 'mailwatchdog', array( __CLASS__, 'render' ), 'dashicons-email-alt' );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mailwatchdog' ) );
		}
		check_admin_referer( 'mwd_save' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in Settings::save().
		MWD_Settings::save( isset( $_POST['mwd'] ) ? (array) $_POST['mwd'] : array() );
		wp_safe_redirect( admin_url( 'admin.php?page=mailwatchdog&saved=1' ) );
		exit;
	}

	public static function handle_resend() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mailwatchdog' ) );
		}
		check_admin_referer( 'mwd_resend' );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		MWD_Interceptor::resend( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=mailwatchdog&resent=' . (int) $id ) );
		exit;
	}

	public static function render() {
		$just_saved = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag.
		$rows       = MWD_Store::recent( 100 );
		?>
		<div class="wrap">
			<h1>Mail Watchdog</h1>

			<?php if ( '1' === $just_saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:20px">
				<input type="hidden" name="action" value="mwd_save">
				<?php wp_nonce_field( 'mwd_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th>Telegram (optional)</th>
						<td>
							<input type="text" name="mwd[token]" value="<?php echo esc_attr( MWD_Settings::get()['token'] ); ?>" class="regular-text" placeholder="Bot token">
							<input type="text" name="mwd[chat]" value="<?php echo esc_attr( MWD_Settings::get()['chat'] ); ?>" class="regular-text" placeholder="Chat ID">
							<p class="description">When mail breaks, you get a message here too.</p>
						</td>
					</tr>
				</table>
				<button type="submit" class="button button-primary">Save</button>
			</form>

			<h2>Journal (last 100)</h2>
			<?php if ( ! $rows ) : ?>
				<p><em>No mail recorded yet.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:1150px">
					<thead><tr><th style="width:140px">When</th><th style="width:80px">Status</th><th>To</th><th>Subject</th><th style="width:150px">Source</th><th style="width:80px"></th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['logged_at'] ); ?></td>
							<td><strong style="color:<?php echo esc_attr( 'sent' === $row['status'] ? '#00a32a' : '#b32d2e' ); ?>"><?php echo esc_html( strtoupper( $row['status'] ) ); ?></strong><?php if ( 'failed' === $row['status'] ) : ?><br><span class="description"><?php echo esc_html( $row['reason'] ); ?></span><?php endif; ?></td>
							<td><code style="font-size:11px"><?php echo esc_html( $row['mail_to'] ); ?></code></td>
							<td><?php echo esc_html( $row['subject'] ); ?></td>
							<td><span class="description"><?php echo esc_html( $row['source'] ); ?></span></td>
							<td>
								<?php if ( 'failed' === $row['status'] ) : ?>
									<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mwd_resend&id=' . (int) $row['id'] ), 'mwd_resend' ) ); ?>">Resend</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
