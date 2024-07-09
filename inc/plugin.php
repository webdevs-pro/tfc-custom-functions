<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require_once ( TFC_PLUGIN_DIR . '/inc/modules/elementor/elementor.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/user-registration.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/membership/account.php' );


/**
 * Disable admin password change notification for subscriber user role.
 *
 * @param bool  $send   Whether to send the password change email.
 * @param array $user   User object.
 * @param string $notify Type of notification. Can be 'user', 'admin', or 'both'.
 * @return bool False if the email should not be sent, true otherwise.
 */
add_filter( 'send_password_change_email', 'disable_admin_password_change_notification', 10, 3 );

function disable_admin_password_change_notification( $send, $user, $notify ) {
	// Check if the notification is for admin and the user role is subscriber.
	if ( 'admin' === $notify && in_array( 'subscriber', (array) $user->roles ) ) {
		return false;
	}
	return $send;
}
