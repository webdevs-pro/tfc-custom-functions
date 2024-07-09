<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_User_Account {

	public function __construct() {
		add_action( 'wp_login', array( $this, 'redirect_subscribers_to_account' ), 10, 2 );
      add_shortcode( 'tfc_account', array( $this, 'user_account_shortcode' ) );
	}

	public function redirect_subscribers_to_account( $user_login, $user ) {
		if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
			wp_redirect( home_url( '/account' ) );
			exit;
		}
	}

	public function user_account_shortcode() {
      ob_start();



      return ob_get_clean();
	}

}