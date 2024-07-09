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
			'permission_callback' => '__return_true',
		) );
	}

	public function new_user_registration( $request ) {
		$parameters = $request->get_params();

		if ( isset( $parameters['email'] ) && filter_var( $parameters['email'], FILTER_VALIDATE_EMAIL ) ) {
			$email = sanitize_email( $parameters['email'] );
			
			// Generate a random password
			$password = wp_generate_password();

			// Create the new user
			$user_id = wp_create_user( $email, $password, $email );

			if ( is_wp_error( $user_id ) ) {
				// Log error in user creation
				error_log( "User creation error: " . $user_id->get_error_message() );
			} else {
				// Set the user's role to 'subscriber'
				wp_update_user( array(
					'ID' => $user_id,
					'role' => 'subscriber'
				) );

				// Optionally, send an email to the new user with their login details
				wp_mail(
					$email,
					'Welcome to Our Website',
					"Your account has been created. You can log in using the following credentials:\nUsername: $email\nPassword: $password"
				);

				// Log successful user creation
				error_log( "User created successfully with ID: $user_id" );
			}
		} else {
			// Log invalid email attempt
			error_log( "Invalid or missing email address: " . print_r( $parameters, true ) );
		}

		error_log( "parameters\n" . print_r( $parameters, true ) . "\n" );
	}
}
new TFC_User_Registration();