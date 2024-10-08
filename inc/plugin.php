<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require_once ( TFC_PLUGIN_DIR . '/inc/modules/elementor/elementor.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/logger/logger.php' );
require_once ( TFC_PLUGIN_DIR . '/inc/modules/admin/admin.php' );
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
 * Shortcode to return from city text from the URL.
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




// Hook into the post meta updates for the 'deal' CPT.
add_action( 'updated_post_meta', 'check_origin_update', 10, 4 );
add_action( 'added_post_meta', 'check_origin_update', 10, 4 );

/**
 * Function to act upon updating the 'origin' meta field.
 *
 * @param int    $meta_id     ID of the updated metadata entry.
 * @param int    $post_id     Post ID.
 * @param string $meta_key    Meta key.
 * @param mixed  $meta_value  Meta value.
 */
function check_origin_update( $meta_id, $post_id, $meta_key, $meta_value ) {
	// Check if the updated meta is 'origin' and belongs to a 'deal' post type.
	if ( 'origin' === $meta_key && 'deal' === get_post_type( $post_id ) ) {
		tfc_maybe_add_new_origin_city_term( $meta_value );
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




/**
 * Tracking scripts.
 */
add_action( 'wp_head', function() {
	if ( current_user_can( 'subscriber' ) ) { // Check if the user has the 'subscriber' role
		$current_user = wp_get_current_user();
		$subscription_status = get_user_meta( $current_user->ID, 'subscription', true );

		if ( $subscription_status == 'active' ) {
			$subscription_type = 'paid';
		} else {
			$subscription_type = 'free';
		}

		?>
		<script>
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({
				user_id: '<?php echo esc_js( $current_user->ID ); ?>',
				subscription_type: '<?php echo esc_js( $subscription_type ); ?>'
			});
		</script>
		<?php
	}
}, 1 );

add_action( 'wp_body_open', function() {
	if ( is_page( 'newsletter-signup-thank-you' ) && isset( $_GET['signup'] ) && isset( $_GET['email'] ) ) {
		?>
		<script>
			window.dataLayer.push({
				event: 'free_subscription',
				user_email: '<?php echo esc_js( $_GET['email'] ); ?>',
				user_id: '<?php echo esc_js( $_GET['signup'] ); ?>'
			})
		</script>
		<?php 
	}
}, 99 );





/**
 * Add GTM tracking code to the wp_body_open hook.
 */
add_action( 'wp_body_open', 'tfc_add_gtm_tracking_code' );
function tfc_add_gtm_tracking_code() {
	// Get the Stripe secret API key from the ACF option page
	$stripe_secret_key = get_field('stripe_secret_api_key', 'option');
	
	// Set your secret API key
	\Stripe\Stripe::setApiKey( $stripe_secret_key );

	// Assuming session_id is passed as a query parameter in the URL
	if ( isset($_GET['session_id']) ) {
		$session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );

		// Retrieve the Checkout Session
		$session = \Stripe\Checkout\Session::retrieve( $session_id );

		// Retrieve the Subscription
		$subscription_id = $session->subscription;
		$subscription = \Stripe\Subscription::retrieve($subscription_id);

		// Get the price ID from the subscription
		$price_id = $subscription->items->data[0]->price->id;

		// Retrieve the Price to get product details
		$price = \Stripe\Price::retrieve( $price_id );

		// Retrieve the Product
		$product = \Stripe\Product::retrieve( $price->product );

		// Prepare data for GTM tracking code
		$currency = strtoupper( $price->currency );
		$value = $price->unit_amount / 100;
		$transaction_id = $session->id;
		$item_name = $product->name;
		$item_id = $price_id; // Or any specific ID you wish to use
		$quantity = 1; // Adjust if needed

		?>
		<script>
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({
					event: 'purchase',
					ecommerce: {
						currency: '<?php echo esc_js( $currency ); ?>',
						value: <?php echo esc_js( $value ); ?>,
						transaction_id: '<?php echo esc_js( $transaction_id ); ?>',
						items: [{
							item_name: '<?php echo esc_js( $item_name ); ?>',
							item_id: '<?php echo esc_js( $item_id ); ?>',
							price: '<?php echo esc_js( $value ); ?>',
							quantity: '<?php echo esc_js( $quantity ); ?>'
						}]
					}
			});
		</script>
		<?php
	}
}















/**
 * Handle user registration form submission.
 *
 * This function processes the form submission, creates a new user with the role
 * of 'subscriber', sends a custom welcome email with an auto-login link, saves the
 * selected city to user meta, and redirects the user if necessary. If the email already
 * exists or the selected city is invalid, the user is redirected back to the form with 
 * appropriate error messages.
 */
add_action( 'admin_post_nopriv_tfc_register_user', 'tfc_handle_user_registration' );
add_action( 'admin_post_tfc_register_user', 'tfc_handle_user_registration' );

function tfc_handle_user_registration() {
	if ( isset( $_POST['email'] ) && wp_verify_nonce( $_POST['tfc_nonce'], 'tfc_register_user' ) ) {
		$email = sanitize_email( $_POST['email'] );
		$origin_city = isset( $_POST['origin_city'] ) ? sanitize_text_field( $_POST['origin_city'] ) : '';

		if ( is_email( $email ) && ! email_exists( $email ) ) {
			$city_exists = term_exists( $origin_city, 'origin-city' );

			if ( $city_exists ) {
				$random_password = wp_generate_password();
				$user_id = wp_create_user( $email, $random_password, $email );

				// Set the user role to 'subscriber'.
				wp_update_user(
					array(
						'ID'   => $user_id,
						'role' => 'subscriber',
					)
				);

				// Save the selected city to user meta.
				update_user_meta( $user_id, 'origin_city', $origin_city );

				// Add new contact to Brevo
				$data = array();
				$data['attributes']['SUBSCRIPTION'] = 2; // Free
				$data['attributes']['CITY'] = sanitize_text_field( $origin_city );
				$data['listIds'] = [2];

				$brevo = new TFC_Brevo_API;
				$brevo->create_contact( $email, $data );

				// Generate an auto-login link.
				$token = wp_generate_password( 20, false ); // Generate a unique token.
				update_user_meta( $user_id, 'tfc_auto_login_token', $token ); // Save token to user meta.

				// Create the auto-login URL with the token.
				$login_url = add_query_arg(
					array(
						'user_id' => $user_id,
						'token'   => $token,
					),
					site_url( '/auto-login' )
				);

				$subject = "Tom's Flight Club - you're almost in...";

				// Define the message in HTML format.
				$message = "<p>Ahoy Mate!</p>";
				$message .= "<p>Just one more step before you can access the amazing deals on Tom's Flight Club.</p>";
				$message .= "<p>I need you to click the link below (it's a magic login link - so you'll login to your Tom's Flight Club account with your email automatically):</p>";
				$message .= "<p><a href='{$login_url}'>CLICK HERE TO LOGIN</a></p>";
				$message .= "<p>You must do this, otherwise your account will not be created, and you will not get the best flight deals.</p>";
				$message .= "<p>Cheers mate,<br>Tom</p>";

				// Set the email headers to send as HTML.
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				// Send the email with the custom HTML message.
				wp_mail( $email, $subject, $message, $headers );

				// Redirect user to a custom thank-you page.
				$redirect_page_slug = isset( $_POST['redirect_page_slug'] ) ? sanitize_text_field( $_POST['redirect_page_slug'] ) : 'thank-you';
				$redirect_url = add_query_arg(
					array(
						'signup' => $user_id,
						'email'  => $email,
					),
					site_url( '/' . $redirect_page_slug )
				);
				wp_redirect( $redirect_url );
				exit;
			} else {
				$redirect_url = add_query_arg( 'city-not-exists', 'true', wp_get_referer() );
				wp_redirect( $redirect_url );
				exit;
			}
		} else {
			$redirect_url = add_query_arg( 'email-exists', 'true', wp_get_referer() );
			wp_redirect( $redirect_url );
			exit;
		}
	} else {
		$redirect_url = add_query_arg( 'registration', 'failed', wp_get_referer() );
		wp_redirect( $redirect_url );
		exit;
	}
}

/**
 * Handle auto-login via token.
 */
add_action( 'template_redirect', 'tfc_handle_auto_login' );
function tfc_handle_auto_login() {
	if ( isset( $_GET['user_id'] ) && isset( $_GET['token'] ) ) {
		$user_id = absint( $_GET['user_id'] );
		$token = sanitize_text_field( $_GET['token'] );

		// Check if the user exists and verify the token.
		$user = get_user_by( 'ID', $user_id );
		if ( $user && get_user_meta( $user_id, 'tfc_auto_login_token', true ) === $token ) {
			// Log the user in.
			wp_set_auth_cookie( $user_id );
			// Delete the token after use for security reasons.
			delete_user_meta( $user_id, 'tfc_auto_login_token' );

			// Redirect to the /account page.
			wp_redirect( site_url( '/account' ) );
			exit;
		}
	}
}









/**
 * Shortcode to display the user registration form.
 *
 * This shortcode outputs a form with an email field and handles
 * the redirection to a specified page after successful registration.
 * If the email already exists or the selected city is invalid,
 * it displays appropriate error messages.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML form output.
 */
function tfc_register_user_form_shortcode( $atts ) {
	// Extract shortcode attributes with a default redirect page slug of 'thank-you'.
	$atts = shortcode_atts(
		array(
				'redirect-page-slug' => 'thank-you',
		),
		$atts,
		'tfc_register_form'
	);

	// Store the redirect page slug in a hidden input field.
	$redirect_page_slug = esc_attr( $atts['redirect-page-slug'] );

	ob_start();
	?>

	<form class="tfc-signup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="email" name="email" required placeholder="Enter Your Email" />

		<select name="origin_city" required>
			<?php
			$args = array(
				'taxonomy'   => 'origin-city',
				'hide_empty' => false,
			);

			$city_terms = get_terms( $args );

			$selected_city = isset( $_GET['signup-city'] ) ? $_GET['signup-city'] : '';
			$city_found = false;
			
			foreach ( $city_terms as $city_term ) {
				if ( $selected_city === $city_term->name ) {
					$city_found = true;
					break;
				}
			}
			
			if ( ! $city_found ) {
				echo '<option value="" hidden selected>Select City</option>';
			}
			
			foreach ( $city_terms as $city_term ) {
				$selected = selected( $selected_city, $city_term->name, false );
				echo '<option value="' . esc_attr( $city_term->name ) . '"' . $selected . '>' . esc_html( $city_term->name ) . '</option>';
			}
			?>
		</select>

		<?php wp_nonce_field( 'tfc_register_user', 'tfc_nonce' ); ?>
		<input type="hidden" name="action" value="tfc_register_user">
		<input type="hidden" name="redirect_page_slug" value="<?php echo esc_attr( $redirect_page_slug ); ?>">
		<input type="submit" value="Sign Up" />
	</form>
	
	<div class="error-messages">
		<?php
		// Check if the 'email-exists' parameter is set and display an error message.
		if ( isset( $_GET['email-exists'] ) && 'true' === $_GET['email-exists'] ) {
				echo '<p class="error-message">This email is already registered. Please use a different email or <a href="/login">Sign in here</a> if you already have an account.</p>';
		}

		// Check if the 'city-not-exists' parameter is set and display an error message.
		if ( isset( $_GET['city-not-exists'] ) && 'true' === $_GET['city-not-exists'] ) {
				echo '<p class="error-message">The selected city does not exist. Please select a valid city.</p>';
		}

		// Check if there was a general failure in the registration process.
		if ( isset( $_GET['registration'] ) && 'failed' === $_GET['registration'] ) {
			echo '<p class="error-message">Registration failed. Please try again.</p>';
		}
		?>
	</div>

	<script>
		// JavaScript to remove error URL parameters after page load
		window.addEventListener('load', function() {
			var url = new URL(window.location.href);
			url.searchParams.delete('email-exists');
			url.searchParams.delete('city-not-exists');
			url.searchParams.delete('registration');
			window.history.replaceState({}, document.title, url.pathname + url.search);
		});
	</script>

	<style>
		.tfc-signup-form {
			background-color: #fff;
			padding: 4px;
			border-radius: 5px;
			width: 100%;
		}
		.tfc-signup-form input:not([type="submit"]),
		.tfc-signup-form select {
			border: none;
			background-color: transparent;
			font-size: 16px;
			min-height: 47px;
			padding: 6px 16px;
		}
		.tfc-signup-form input[type="submit"] {
			background-color: #000000;
			color: #ffffff;
			font-family: "Open Sans", Sans-serif;
			font-size: 16px;
			font-weight: 700;
			border-radius: 6px;
			padding: 16px 0;
			width: 100%;
			line-height: 1;
			border: none;
			transition: opacity 250ms;
		}
		.tfc-signup-form input[type="submit"]:hover {
			opacity: 0.8;
		}
		.error-messages {
			margin-top: 10px;
			padding: 4px;
		}
		.error-message {
			color: red;
			font-size: 14px;
		}
		.error-message a {
			color: inherit;
			text-decoration: underline;
		}
	</style>

	<?php
	return ob_get_clean();
}
add_shortcode( 'tfc_register_form', 'tfc_register_user_form_shortcode' );



function tfc_maybe_add_new_origin_city_term( $origin_city ) {
	global $tfc_logger;

	// Check if the term exists in the 'origin-city' taxonomy
	$term_exists = term_exists( $origin_city, 'origin-city' );

	if ( $term_exists === null ) {
		// The term does not exist, so let's add it
		$new_term = wp_insert_term( $origin_city, 'origin-city' );

		if ( is_wp_error( $new_term ) ) {
			// Handle the error if the term could not be added
			$tfc_logger->log( "Failed to add origin city term: " . $new_term->get_error_message() . "\n" );
		} else {
			$tfc_logger->log( "Added origin city term: " . $new_term['name'] . "\n" );
		}
	}
}







function add_custom_fields_to_reset_password_form() {
	// error_log( "_GET\n" . print_r( $_GET, true ) . "\n" );

	if ( ! isset( $_GET['wellcome'] ) || ! $_GET['wellcome'] ) {
		return;
	}

	?>
	<p>
		 <label for="first_name"><?php esc_html_e('First Name'); ?></label>
		 <input type="text" name="first_name" id="first_name" class="input" required/>
	</p>
	<?php
}
add_action('resetpass_form', 'add_custom_fields_to_reset_password_form');


function save_custom_fields_on_password_reset( $user, $new_pass ) {
	if ( isset( $_POST['first_name'] ) ) {
		update_user_meta( $user->ID, 'first_name', sanitize_text_field( $_POST['first_name'] ) );

		$data['attributes']['FIRSTNAME'] = sanitize_text_field( $_POST['first_name'] );
		
		$brevo = new TFC_Brevo_API;
		$brevo->update_contact( $user->user_email, $data );
	}
}
add_action('password_reset', 'save_custom_fields_on_password_reset', 10, 2);



// Register the cleanup action with Action Scheduler
add_action( 'tfc_cleanup_brevo_list_and_campaign', function( $list_id, $campaign_id ) {
	$brevo_api = new TFC_Brevo_API();
	$brevo_api->cleanup_brevo_list_and_campaign( $list_id, $campaign_id );
}, 10, 2 );

// $brevo_api = new TFC_Brevo_API();
// $brevo_api->delete_temp_lists();



/**
 * Formats a price with a currency symbol.
 *
 * This function takes a currency code and a price, and returns a string
 * formatted with the appropriate currency symbol.
 *
 * @param string $currency The currency code (e.g., 'USD', 'GBP', 'EUR', 'CAD').
 * @param float|int $price The price amount to be formatted.
 *
 * @return string The formatted price string with the currency symbol.
 */
function tfc_format_price_with_currency( $currency, $price ) {
	switch ( $currency ) {
		case 'USD':
			$price_string = '$' . $price;
			break;

		case 'GBP':
			$price_string = '£' . $price;
			break;

		case 'EUR':
			$price_string = '€' . $price;
			break;

		case 'CAD':
			$price_string = 'C$' . $price;
			break;

		case 'CHF':
			$price_string = '₣' . $price;
			break;
		
		default:
			$price_string = '$' . $price;
			break;
	}

	return $price_string;
}


// add_filter( 'wp_new_user_notification_email', 'tfc_customize_welcome_email', 10, 3 );
// function tfc_customize_welcome_email( $wp_new_user_notification_email, $user, $blogname ) {
// 	// Generate the password reset URL with an additional parameter
// 	$key = get_password_reset_key( $user );

// 	$user_login = $user->user_login;
// 	$reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' );

// 	// Add a custom parameter to the URL
// 	$reset_url = add_query_arg( 'wellcome', 'true', $reset_url );

// 	// Customize the email message
// 	$message = "Thank you for registering at " . $blogname . ".\r\n\r\n";
// 	$message .= "Username: " . $user->user_login . ",\r\n\r\n";
// 	$message .= "To set your password, click the following link:\r\n";
// 	$message .= $reset_url . "\r\n\r\n";

// 	$wp_new_user_notification_email['message'] = $message;

// 	return $wp_new_user_notification_email;
// }



