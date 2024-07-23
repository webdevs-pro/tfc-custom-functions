<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_Stripe {


	public function __construct() {
		// add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		add_action( 'acf/render_field/name=subscription', array( $this, 'after_subscription_field' ), 20, 1 );
		add_shortcode( 'tfc_subscription', array( $this, 'subscription_shortcode' ) );
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

		$current_plan_name = 'Unsubscribed';
		if ( is_array( $plan_json ) && isset( $plan_json['name'] ) ) {
			$current_plan_name = $plan_json['name'] ?? '';
		}

		$next_payment_label = 'Next Payment';
		$next_payment_timestamp = $subscription['current_period_end'] ?? '';

		if ( ! empty( $next_payment_timestamp ) && is_numeric( $next_payment_timestamp ) ) {
			$next_payment_date = date( 'F j, Y', $subscription['current_period_end'] );
		} else {
			$next_payment_date = 'N/A';
		}

		if ( is_array( $subscription ) && isset( $subscription['canceled_at'] ) && ! empty( $subscription['canceled_at'] ) ) {
			$current_plan_name .= ' (Canceled)';
			$next_payment_label = 'Active until';
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
					<div class="subscription-info-label"><?php echo $next_payment_label; ?></div>
					<div class="subscription-info-value"><?php echo $next_payment_date; ?></div>
				</div>
			</div>

			<div class="subscription-buttons">
				<a href="https://billing.stripe.com/p/login/test_14k5la9bf1j40mc8ww" id="manage-subscription">Manage Sunscription</a>
				<a href="https://billing.stripe.com/p/login/test_14k5la9bf1j40mc8ww" id="cancel-subscription">Cancel Plan</a>
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

}
new TFC_Stripe();