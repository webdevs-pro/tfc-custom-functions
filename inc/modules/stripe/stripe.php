<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_Stripe {


	public function __construct() {
		// add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		add_action( 'acf/render_field/name=subscription', array( $this, 'after_subscription_field' ), 20, 1 );
		add_shortcode( 'tfc_subscription', array( $this, 'subscription_shortcode' ) );
		add_shortcode( 'stripe_url', array( $this, 'generate_stripe_url_with_email' ) );

	}

	public function register_rest_route() {
		// https://tomsflightclub.com/wp-json/tfc/v1/stripe
		register_rest_route( 'tfc/v1', 'stripe', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'process_stripe_event' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function process_stripe_event( $request ) {
		$parameters = $request->get_params();

		error_log( "parameters\n" . print_r( $parameters, true ) . "\n" );
	}

	public function after_subscription_field( $field ) {
		$screen = get_current_screen();
		if ( 'user-edit' === $screen->id && isset( $_GET['user_id'] ) ) {
			 $user_id = intval($_GET['user_id']);
		}
		$subscription = get_user_meta( $user_id, 'stripe_event', true );
		$plan_json = get_user_meta( $user_id, 'plan_json', true );
		?>
		<h4>Debug Info</h4>
		<details>
			<summary>Stripe Subscription</summary>
			<pre style="font-size: 12px;"><?php echo print_r( $subscription, true ); ?></pre>
		</details>
		<details>
			<summary>Plan Info</summary>
			<pre style="font-size: 12px;"><?php echo print_r( $plan_json, true ); ?></pre>
		</details>

		<?php

	}


	public function subscription_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>You need to be logged in to update your account details.</p>';
		}

		$current_user = wp_get_current_user();

		$plan_json = get_user_meta( $current_user->ID, 'plan_json', true );
		$subscription = get_user_meta( $current_user->ID, 'stripe_event', true );
		$subscription_status = get_user_meta( $current_user->ID, 'subscription', true );
		$next_payment_label = 'Next Payment';
		$next_payment_timestamp = $subscription['current_period_end'] ?? '';


		$current_plan_name = 'Free';
		if ( is_array( $plan_json ) && isset( $plan_json['name'] ) && $subscription_status == 'active' ) {
			$current_plan_name = $plan_json['name'] ?? '';
			if ( is_array( $subscription ) && isset( $subscription['canceled_at'] ) && ! empty( $subscription['canceled_at'] ) ) {
				$current_plan_name .= ' (Canceled)';
				$next_payment_label = 'Active until';
			}
		}


		if ( ! empty( $next_payment_timestamp ) && is_numeric( $next_payment_timestamp ) ) {
			$next_payment_date = date( 'F j, Y', $subscription['current_period_end'] );
		} else {
			$next_payment_date = 'N/A';
		}







		ob_start();
		?>

		<div id="tfc-membership">

			<h3>Membership</h3>

			<div class="subscription-info">
				<div class="subscription-plan">
					<div class="subscription-info-label">Your Plan</div>
					<div class="subscription-info-value"><?php echo $current_plan_name; ?></div>
				</div>

				<div class="subscription-next-payment">
					<?php if ( $subscription_status == 'active' ) { ?>
						<div class="subscription-info-label"><?php echo $next_payment_label; ?></div>
						<div class="subscription-info-value"><?php echo $next_payment_date; ?></div>
					<?php } ?>
				</div>
			</div>

			<div class="subscription-buttons">
				<?php if ( ! $subscription ) { ?>
					<a href="/subscribe" id="manage-subscription">Become a Premium Member</a>
				<?php } else { ?>
					<?php if ( $subscription_status == 'active' ) { ?>
						<a href="https://billing.stripe.com/p/login/test_14k5la9bf1j40mc8ww?user_email=<?php echo $current_user->user_email ; ?>" id="manage-subscription">Manage Subscription</a>
						<a href="https://billing.stripe.com/p/login/test_14k5la9bf1j40mc8ww?user_email=<?php echo $current_user->user_email ; ?>" id="cancel-subscription">Cancel Plan</a>
					<?php } else if ( $subscription && $subscription_status != 'active' ) { ?>
						<a href="/subscribe" id="manage-subscription">Become a Premium Member</a>
					<?php } ?>
				<?php } ?>
			</div>
		</div>


		<style>
			#tfc-membership h3 {
				font-weight: bold;
				font-size: 20px;
			}
			#tfc-membership .subscription-info {
				background-color: #f5f6fa;
				padding: 16px;
				border-radius: 10px;
				display: grid;
				grid-template-columns: 1fr 1fr;
			}
			#tfc-membership .subscription-info-label {
				font-weight: 500;
  				font-size: 14px;
				margin-bottom: 4px;
			}
			#tfc-membership .subscription-info-value {
				font-weight: bold;
  				font-size: 18px;
			}
			#tfc-membership .subscription-buttons {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: 32px;
			}
			#tfc-membership #manage-subscription {
				border: 1px solid #415BE7;
				border-radius: 5px;
				color: #415BE7;
				font-weight: bold;
				padding: 8px 24px;
			}
			#tfc-membership #cancel-subscription {
				color: #BE0000;
				font-weight: bold;
				border: none;
			}

			@media(max-width: 767px) {
				#tfc-membership .subscription-info {
					gap: 16px;
					grid-template-columns: 1fr;
				}
				#tfc-membership .subscription-buttons {
					flex-direction: column;
					gap: 16px;
					align-items: flex-start;
				}
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode to generate a Stripe URL with prefilled email.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The generated URL with prefilled email.
	 */
	public function generate_stripe_url_with_email( $atts ) {
		// Extract attributes with defaults
		$atts = shortcode_atts(
			array(
				'plan' => '',
			), $atts, 'stripe_url'
		);

		// Map plan attribute to ACF field name
		$plan_to_field = array(
			'3 month' => '3_months_plan_url',
			'6 month' => '6_months_plan_url',
			'yearly'  => 'yearly_plan_url',
		);

		// Get the plan URL from ACF options
		if ( ! array_key_exists( $atts['plan'], $plan_to_field ) ) {
			return ''; // Return empty if plan attribute is invalid
		}

		$field_name = $plan_to_field[ $atts['plan'] ];
		$plan_url = get_field( $field_name, 'option' );

		if ( ! $plan_url ) {
			return ''; // Return empty if no URL is found
		}

		// Check if the user is logged in and get their email
		$current_user = wp_get_current_user();
		$email = '';

		if ( is_user_logged_in() && isset( $current_user->user_email ) ) {
			$email = sanitize_email( $current_user->user_email );
		}

		// Generate the URL with the prefilled email if available
		if ( $email ) {
			$plan_url = add_query_arg( 'prefilled_email', $email, $plan_url );
		}

		// Return the URL
		return esc_url( $plan_url );
	}

}
new TFC_Stripe();