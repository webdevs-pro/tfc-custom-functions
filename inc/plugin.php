<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require_once ( TFC_PLUGIN_DIR . '/inc/modules/elementor/elementor.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/user-registration.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/account.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/deals-listing/deals-listing.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/stripe/stripe.php' );


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
 * Function to perform actions after user meta is updated, with debugging for array values.
 *
 * @param int    $meta_id     ID of the metadata entry.
 * @param int    $object_id   ID of the user object.
 * @param string $meta_key    Meta key.
 * @param mixed  $meta_value  Meta value.
 */
function tfc_listen_update_user_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
	// Check if the meta value is an array.
	if ( is_array( $meta_value ) ) {
		ob_start(); // Start buffering to capture output
		print_r( $meta_value );
		$output = ob_get_clean(); // Get the output and clean buffer

		// You can log this output to a file or handle it as needed
		error_log( "Updated user meta for user {$object_id}: {$meta_key} = {$output}" );
	} else {
		// Handle non-array meta values normally.
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
}

// Hook into update_user_meta.
add_action( 'update_user_meta', 'tfc_listen_update_user_meta', 10, 4 );
