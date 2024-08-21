<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ( TFC_PLUGIN_DIR . '/inc/modules/elementor/elementor.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/user-registration.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/account.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/deals-listing/deals-listing.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/brevo/class-brevo-api.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/stripe/stripe.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/email/email.php' );


/**
 * Register frontend styles
 */
add_action( 'wp_enqueue_scripts', 'tfc_register_frontend_styles' );
function tfc_register_frontend_styles() {
	wp_enqueue_style( 'tfc-frontend', TFC_PLUGIN_DIR_URL . 'inc/assets/frontend.css', array(), TFC_PLUGIN_VERSION ); 
}

/**
 * Register critical frontend styles
 */
add_action( 'wp_enqueue_scripts', 'tfc_enqueue_critical_css' );
function tfc_enqueue_critical_css() {
	wp_enqueue_style( 'tfc-critical', TFC_PLUGIN_DIR_URL . 'inc/assets/critical.css', array(), TFC_PLUGIN_VERSION, false ); 
}

/**
 * Disable admin password change notification for subscriber user role.
 */
if ( ! function_exists( 'wp_password_change_notification' ) ) {
	function wp_password_change_notification( $user ) {
		// Get the user object to check the role.
		$user_obj = get_userdata( $user->ID );
		
		// Check if the user's role is subscriber.
		if ( in_array( 'subscriber', ( array ) $user_obj->roles ) ) {
			return false;
		}
	}
}


/**
 * Shortcode to return the page/post title, with a personalized greeting on the Account page.
 *
 * @return string The page/post title or personalized greeting.
 */
add_shortcode( 'tfc-dynamic-page-title', 'tfc_dynamic_page_title' );
function tfc_dynamic_page_title() {
	// Get the current post/page title.
	$title = get_the_title();

	// Check if the user is logged in and if we are on the "Account" page.
	if ( is_user_logged_in() && is_page( 'Account' ) ) {
		$current_user = wp_get_current_user();
		
		// Check if the user has a first name set, otherwise use the display name.
		if ( ! empty( $current_user->first_name ) ) {
			$title = 'Hi, ' . esc_html( $current_user->first_name ) . '!';
		} else {
			$title = 'Hi, ' . esc_html( $current_user->display_name ) . '!';
		}
	}

	return $title;
}


/**
 * Shortcode to return the page/post title, with a personalized greeting on the Account page.
 *
 * @return string The page/post title or personalized greeting.
 */
add_shortcode( 'tfc-from-city', 'tfc_from_city_text' );
function tfc_from_city_text() {
	// Get the current post/page title.
	$text = '';

	if ( isset( $_GET['signup-city'] ) && $_GET['signup-city'] ) {
		$text = 'from ' . $_GET['signup-city'];
	}

	return $text;
}


/**
 * Function to perform actions after user meta is updated, with debugging for array values.
 *
 * @param int    $meta_id     ID of the metadata entry.
 * @param int    $object_id   ID of the user object.
 * @param string $meta_key    Meta key.
 * @param mixed  $meta_value  Meta value.
 */
add_action( 'update_user_meta', 'tfc_on_update_user_meta', 10, 4 );
function tfc_on_update_user_meta( $meta_id, $object_id, $meta_key, $meta_value ) {

	// Debug
	if ( is_array( $meta_value ) ) {
		ob_start();
			print_r( $meta_value );
		$output = ob_get_clean();

		error_log( "Updated user meta for user {$object_id}: {$meta_key} = {$output}" );
	} else {
		error_log( "Updated user meta for user {$object_id}: {$meta_key} = {$meta_value}" );
	}


	if ( $meta_key == 'stripe_username' ) {
		$user = get_userdata( $object_id );

		$email = $user->user_email;
		$nickname = $user->nickname;

		if ( $meta_value && $email == $nickname ) {
			$user_data = array(
				'ID'       => $object_id,
				'nickname' => $meta_value,
			);
	 
			// Update the user.
			wp_update_user( $user_data );
		}
	}

	if ( $meta_key == 'subscription' || $meta_key == 'origin_city' ) {
		$user = get_userdata( $object_id );
		$user_email = $user->user_email;
		$data = array();

		if ( $meta_key == 'subscription' ) {
			if ( $meta_value == 'active' ) {
				$data['attributes']['SUBSCRIPTION'] = 1; // Paid
			} else {
				$data['attributes']['SUBSCRIPTION'] = 2; // Free
			}
		}

		if ( $meta_key == 'origin_city' ) {
			$data['attributes']['CITY'] = sanitize_text_field( $meta_value );
		}

		$brevo = new TFC_Brevo_API;
		$brevo->update_contact( $user_email, $data );
	}
	

}






/**
 * Shortcode to display social icon based on the social network meta field.
 *
 * @return string The HTML content for the social icon.
 */
add_shortcode( 'tfc-review-social-icon', 'tfc_review_social_icon' );
function tfc_review_social_icon() {
	ob_start();

	$network = get_post_meta( get_the_ID(), 'social_network', true );

	if ( $network == 'facebook' ) {
		?>
		<div class="elementor-icon-wrapper">
			<div class="elementor-icon" style="font-size: 20px;">
				<svg aria-hidden="true" class="e-font-icon-svg e-fab-facebook" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path fill="#0570E5" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z"></path></svg>
			</div>
		</div>
		<?php
	} else if ( $network == 'x' ) {
		?>
		<div class="elementor-icon-wrapper">
			<div class="elementor-icon" style="font-size: 20px;">
				<svg aria-hidden="true" class="e-font-icon-svg e-fab-x-twitter" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path fill="#000" d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path></svg>
			</div>
		</div>
		<?php
	}

	return ob_get_clean();
}
