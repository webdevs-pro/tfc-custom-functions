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
      ob_start();



      return ob_get_clean();
	}

}
new TFC_User_Account();