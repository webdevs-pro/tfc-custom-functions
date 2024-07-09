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

	function handle_user_account_form_submission() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( isset( $_POST['user_account_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['user_account_nonce'] ) ), 'update_user_account' ) ) {
			$current_user = wp_get_current_user();

			$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
			$last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
			$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
			$origin_city = isset( $_POST['origin_city'] ) ? sanitize_text_field( wp_unslash( $_POST['origin_city'] ) ) : '';

			wp_update_user( array(
				'ID' => $current_user->ID,
				'first_name' => $first_name,
				'last_name' => $last_name,
			) );

			update_user_meta( $current_user->ID, 'phone', $phone );
			update_user_meta( $current_user->ID, 'origin_city', $origin_city );

			// Redirect to avoid form resubmission
			wp_safe_redirect( add_query_arg( 'account-updated', 'success', get_permalink() ) );
			exit;
		}
	}

	public function user_account_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>You need to be logged in to update your account details.</p>';
		}

		$current_user = wp_get_current_user();
		$message = '';

		if ( isset( $_GET['account-updated'] ) && 'success' === sanitize_text_field( wp_unslash( $_GET['account-updated'] ) ) ) {
			$message = '<p>Account details updated successfully.</p>';
		}

		$selected_origin_city = get_user_meta( $current_user->ID, 'origin_city', true );
		$origin_city_terms    = get_terms( array(
			'taxonomy' => 'origin-city',
			'hide_empty' => false,
		) );

		ob_start();
		?>
		<?php if ( $message ) { ?>
			<div class="notice notice-success">
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

		<form method="post" action="">

			<p>
				<label for="first_name">First Name:</label>
				<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" />
			</p>

			<p>
				<label for="last_name">Last Name:</label>
				<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" />
			</p>

			<p>
				<label for="phone">Phone Number:</label>
				<input type="text" id="phone" name="phone" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'phone', true ) ); ?>" />
			</p>

			<p>
				<label for="origin_city">Origin City:</label>
				<select id="origin_city" name="origin_city">
					<option value="" hidden>Select your origin city</option>
					<?php
					if ( ! empty( $origin_city_terms ) && ! is_wp_error( $origin_city_terms ) ) {
						foreach ( $origin_city_terms as $term ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $term->slug ),
								selected( $selected_origin_city, $term->slug, false ),
								esc_html( $term->name )
							);
						}
					}
					?>
				</select>
			</p>

			<p>
				<?php wp_nonce_field( 'update_user_account', 'user_account_nonce' ); ?>
				<input type="submit" value="Update" />
			</p>

		</form>

		<?php
		return ob_get_clean();
	}

}
new TFC_User_Account();