<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_User_Account {

	public function __construct() {
		add_action( 'wp_login', array( $this, 'redirect_subscribers_to_account' ), 10, 2 );
		add_filter( 'show_admin_bar', array( $this, 'disable_admin_bar_for_subscribers' ) );
		add_action( 'admin_init', array( $this, 'redirect_subscribers_from_admin' ) );
		add_action( 'template_redirect', array( $this, 'redirect_non_logged_users_from_account' ) );


		add_shortcode( 'tfc_account', array( $this, 'user_account_shortcode' ) );
	}

	public function redirect_subscribers_to_account( $user_login, $user ) {
		if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
			wp_redirect( home_url( '/account' ) );
			exit;
		}
	}

	public function disable_admin_bar_for_subscribers( $show_admin_bar ) {
		if ( current_user_can( 'subscriber' ) ) {
			return false;
		}
		return $show_admin_bar;
	}

	public function redirect_subscribers_from_admin() {
		if ( current_user_can( 'subscriber' ) && is_admin() ) {
			wp_redirect( home_url( '/account' ) );
			exit;
		}
	}

	public function redirect_non_logged_users_from_account() {
		if ( ! is_user_logged_in() && is_page( 'account' ) ) {
			wp_redirect( home_url( '/' ) );
			exit;
		}
	}











	public function user_account_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'You need to be logged in to update your account.', 'textdomain' ) . '</p>';
		}

		$user = wp_get_current_user();

		error_log( "user\n" . print_r( $user, true ) . "\n" );

		ob_start();
		?>

		<form method="post">
			<?php wp_nonce_field( 'update_user_account', 'user_account_nonce' ); ?>
			<p>
					<label for="first_name"><?php esc_html_e( 'First Name', 'textdomain' ); ?></label>
					<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
			</p>
			<p>
					<label for="last_name"><?php esc_html_e( 'Last Name', 'textdomain' ); ?></label>
					<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
			</p>
			<p>
					<label for="phone"><?php esc_html_e( 'Phone Number', 'textdomain' ); ?></label>
					<input type="text" id="phone" name="phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'phone', true ) ); ?>" />
			</p>
			<p>
					<button type="submit" name="user_account_submit"><?php esc_html_e( 'Update Account', 'textdomain' ); ?></button>
			</p>
		</form>

		<?php
		if ( isset( $_POST['user_account_submit'] ) ) {
			$this->process_user_account_form();
		}

		return ob_get_clean();
	}

	public function process_user_account_form() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( isset( $_POST['user_account_nonce'] ) && wp_verify_nonce( $_POST['user_account_nonce'], 'update_user_account' ) ) {
			$user_id = get_current_user_id();

			if ( isset( $_POST['first_name'] ) && ! empty( $_POST['first_name'] ) ) {
				update_user_meta( $user_id, 'first_name', sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) );
			}

			if ( isset( $_POST['last_name'] ) && ! empty( $_POST['last_name'] ) ) {
				update_user_meta( $user_id, 'last_name', sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) );
			}

			if ( isset( $_POST['phone'] ) && ! empty( $_POST['phone'] ) ) {
				update_user_meta( $user_id, 'phone', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) );
			}

			echo '<p>' . __( 'Your account has been updated.', 'textdomain' ) . '</p>';
		} else {
			echo '<p>' . __( 'There was an error with your submission. Please try again.', 'textdomain' ) . '</p>';
		}
	}

}
new TFC_User_Account();