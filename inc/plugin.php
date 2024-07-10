<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require_once ( TFC_PLUGIN_DIR . '/inc/modules/elementor/elementor.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/user-registration.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/account.php' );


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