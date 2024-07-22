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

		add_action( 'template_redirect', array( $this, 'handle_user_account_form_submission' ) );

		add_filter( 'nav_menu_item_title', array( $this, 'change_specific_menu_item_title' ), 10, 4 );

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

	public function change_specific_menu_item_title( $title, $item, $args, $depth ) {
		if ( is_user_logged_in() && $item->ID == 409 ) {
			$current_user = wp_get_current_user();
			
			if ( ! empty( $current_user->first_name ) ) {
				$title = esc_html( $current_user->first_name );
			} else {
				$title = esc_html( $current_user->display_name );
			}

			return $title;
		}

		return $title;
	}









	function handle_user_account_form_submission() {
		if ( ! is_user_logged_in() ) {
			return;
		}
	
		if ( isset( $_POST['user_account_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['user_account_nonce'] ) ), 'update_user_account' ) ) {
			$current_user = wp_get_current_user();
			$errors = [];
	
			$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
			$last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	
			wp_update_user( array(
				'ID' => $current_user->ID,
				'first_name' => $first_name,
				'last_name' => $last_name,
			) );

	
			// Update user password
			if ( ! empty( $_POST['current_password'] ) && ! empty( $_POST['new_password'] ) && ! empty( $_POST['confirm_password'] ) ) {
				$current_password = sanitize_text_field( wp_unslash( $_POST['current_password'] ) );
				$new_password = sanitize_text_field( wp_unslash( $_POST['new_password'] ) );
				$confirm_password = sanitize_text_field( wp_unslash( $_POST['confirm_password'] ) );
	
				if ( wp_check_password( $current_password, $current_user->user_pass, $current_user->ID ) ) {
					if ( $new_password === $confirm_password ) {
						wp_set_password( $new_password, $current_user->ID );
					} else {
						$errors[] = 'New password and confirm password do not match.';
					}
				} else {
					$errors[] = 'Current password is incorrect.';
				}
			}
	
			if ( empty( $errors ) ) {
				wp_safe_redirect( add_query_arg( 'account-updated', 'success', get_permalink() ) );
				exit;
			} else {
				$message = '';
				foreach ( $errors as $error ) {
					$message .= '<p class="notice-error">' . esc_html( $error ) . '</p>';
				}
				// Store errors in a transient to display on the form
				set_transient( 'user_account_errors_' . $current_user->ID, $message, 30 );
				wp_safe_redirect( get_permalink() );
				exit;
			}
		}
	}
	
	
	
	
	

	public function user_account_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>You need to be logged in to update your account details.</p>';
		}

		$current_user = wp_get_current_user();
		$message = '';

		if ( isset( $_GET['account-updated'] ) && 'success' === sanitize_text_field( wp_unslash( $_GET['account-updated'] ) ) ) {
			$message = '<p class="notice-success">Account details updated successfully.</p>';
		}

		// Retrieve errors from the transient
		$transient_message = get_transient( 'user_account_errors_' . $current_user->ID );

		if ( $transient_message ) {
			$message .= $transient_message;
			delete_transient( 'user_account_errors_' . $current_user->ID );
		}

		ob_start();
		?>

		<div id="tfc-account">
			<?php if ( $message ) { ?>
				<div class="notice">
					<?php echo $message; ?>
					<script>
						if (history.replaceState) {
							const url = new URL(window.location);
							url.searchParams.delete('account-updated');
							window.history.replaceState({}, document.title, url.toString());
						}
					</script>
				</div>
			<?php } ?>

			<script>
				jQuery(document).ready(function($) {
					$('.password-filesds-toggle').on('click', function() {
						$('.password-fields').slideToggle();
					})
				});
			</script>

			<form method="post" action="">

				<h3>Edit Profile</h3>

				<div class="profile-fields">
					<p style="grid-column: 1 / -1">
						<label for="first_name">Email</label>
						<input type="text" id="email" name="email" disabled value="<?php echo esc_attr( $current_user->user_email ); ?>" />
					</p>

					<p>
						<label for="first_name">First Name</label>
						<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" />
					</p>

					<p>
						<label for="last_name">Last Name</label>
						<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" />
					</p>
				</div>

				<div class="password-filesds-toggle">Change password</div>
				<div class="password-fields">
					<p>
						<label for="current_password">Current Password</label>
						<input type="password" id="current_password" name="current_password" />
					</p>

					<p>
						<label for="new_password">New Password</label>
						<input type="password" id="new_password" name="new_password" />
					</p>

					<p>
						<label for="confirm_password">Confirm New Password</label>
						<input type="password" id="confirm_password" name="confirm_password" />
					</p>
				</div>

				<p>
					<?php wp_nonce_field( 'update_user_account', 'user_account_nonce' ); ?>
					<input type="submit" id="update-account" value="Save Changes" />
				</p>
			</form>
		</div>


		<style>
			#tfc-account h3 {
				font-weight: bold;
				font-size: 20px;
			}
			#tfc-account .profile-fields {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px 24px;
			}
			#tfc-account input {
				border: 1px solid #ECEDF2;
				background-color: #FAFBFF;
				border-radius: 5px;
				padding: 10px;
			}
			#tfc-account label {
				font-weight: 500;
				font-size: 14px;
				margin-bottom: 4px;
			}


			#tfc-account .password-filesds-toggle {
				text-align: right;
				text-decoration: underline;
				font-size: 14px;
				font-weight: 600;
				color: #415BE7;
				margin: 20px 0;
				cursor: pointer;
			}
			#tfc-account .password-fields {
				display: none;
				padding-bottom: 20px;
			}


			#tfc-account #update-account {
				background-color: #415BE7;
				border-radius: 5px;
				border: none;
				color: #fff;
				font-weight: bold;
				padding: 8px 24px;
			}

			#tfc-account .notice {
				font-weight: 600;
			}
			
			#tfc-account .notice-success {
				color: #3f953f;
			}
			#tfc-account .notice-error {
				color: #c65f5f;
			}
			@media(max-width: 767px) {
				#tfc-account .profile-fields {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<?php
		return ob_get_clean();
	}

	
	
	

	
  

}
new TFC_User_Account();