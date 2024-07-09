<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_User_Registration {


	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	public function register_rest_route() {
		// https://tomsflightclub.com/wp-json/tfc/v1/register-new-user
		register_rest_route( 'tfc/v1', 'register-new-user', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'new_user_registration' ),
			'permission_callback' => function() { return true; }
		) );
	}

	public function new_user_registration( $request ) {
		$parameters = $request->get_params();

		error_log( "parameters\n" . print_r( $parameters, true ) . "\n" );
	}
}
new TFC_User_Registration();