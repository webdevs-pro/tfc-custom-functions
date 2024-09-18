<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ElementorPro\Modules\LoopBuilder\Files\Css\Loop_Dynamic_CSS;

$post_counter;

class TFC_Deals_Listing {

	public function __construct() {
		// add_shortcode( 'tfc_deals_listing', array( $this, 'deals_listing_shortcode' ) );
		add_shortcode( 'tfc_deals_listing_item_button', array( $this, 'deals_listing_item_button' ) );
		add_shortcode( 'tfc_deals_listing_item_image', array( $this, 'deals_listing_item_image' ) );
		add_shortcode( 'tfc_deal_city_and_country', array( $this, 'deal_city_and_country' ) );
		add_shortcode( 'tfc_deal_tags', array( $this, 'deal_tags' ) );
		add_shortcode( 'tfc_deal_display_price', array( $this, 'deal_price' ) );
		add_action( 'elementor/query/query_results', array( $this, 'loop_grid_deals_results_filter' ), 10, 2);
		add_action( 'elementor/query/deals_query', array( $this, 'loop_grid_deals_query_filter' ), 10, 2);
	}


	public function deals_listing_item_button() {
		global $post_counter;

		$current_user_id = get_current_user_id();
		$post_id = get_the_ID();

		ob_start();

			if ( $current_user_id ) {
				$subscription_status = get_user_meta( $current_user_id, 'subscription', true );
				$deal_type = get_post_meta( $post_id, 'tier', true );

				if ( $subscription_status == 'active' ) {
					$this->render_get_deal_button( $post_id );
				} else if ( $deal_type == 'free' ) {
					$this->render_get_deal_button( $post_id );
				} else {
					$this->render_become_a_premium_member_button( $post_id );
				}

			} else {
				$this->render_become_a_member_button( $post_id );
			}

			?>
			<style>
				.tfc-loop-get-deal {
					font-family: "Open Sans", Sans-serif;
					font-size: 16px;
					font-weight: 700;
					border-radius: 8px 8px 8px 8px;
					background-color: var( --e-global-color-accent );
					color: #fff;
					padding: 8px 24px;
					transition: opacity 250ms;
					display: inline-block;
				}
				.tfc-loop-get-deal:hover {
					opacity: 0.8;
					color: #fff;
				}
				.tfc-loop-subscribe {
					font-family: "Open Sans", Sans-serif;
					font-size: 16px;
					font-weight: 700;
					border-radius: 8px 8px 8px 8px;
					background-color: #fff;
					color: var( --e-global-color-accent );
					padding: 8px 24px;
					transition: opacity 250ms;
					border: 1px solid var( --e-global-color-accent );
					display: inline-block;
				}
				.tfc-loop-subscribe:hover {
					opacity: 0.8;
					color: var( --e-global-color-accent );
				}
				.tfc-loop-deal-dates {
					font-family: "Open Sans", Sans-serif;
					font-size: 15px;
					font-weight: 600;
				}
			</style>
			<?php

		return ob_get_clean();
	}


	public function render_get_deal_button( $post_id ) {
		$this->render_deal_dates( $post_id );

		$deal_url = get_post_meta( $post_id, 'skyscanner_deal_url', true );
		echo '<a class="tfc-loop-get-deal" href="' . $deal_url . '" target="_blank" role="button">Get Deal</a>';
	}


	private function render_become_a_member_button( $post_id ) {
		echo '<a class="tfc-loop-subscribe" href="/signup" role="button">Become a Free Member</a>';
	}


	private function render_become_a_premium_member_button( $post_id ) {
		echo '<a class="tfc-loop-subscribe" href="/subscribe" role="button">Become a Premium Member</a>';
	}

	private function render_deal_dates( $post_id ) {
		$outbound_date = get_post_meta( $post_id, 'outbound_date', true );
		$return_date = get_post_meta( $post_id, 'return_date', true );

		$outbound_timestamp = strtotime( $outbound_date );
		$return_timestamp = strtotime( $return_date );

		$date_format = 'j M';
		echo '<p class="tfc-loop-deal-dates">' . date_i18n( $date_format, $outbound_timestamp ) . ' - ' . date_i18n( $date_format, $return_timestamp ) . '</p>';
	}

	public function deals_listing_item_image() {
		$post_id = get_the_ID();

		$image_id = get_post_thumbnail_id( $post_id );

		if ( ! $image_id ) {

			$destination_city = get_post_meta( $post_id, 'destination', true );

			if ( function_exists( 'get_field' ) ) {
				$thumbnails_repeater = get_field( 'deal_thumbnails', 'option' );
			} else {
				return '';
			}


			if ( is_array( $thumbnails_repeater ) && ! empty( $thumbnails_repeater ) ) {
				foreach ( $thumbnails_repeater as $item ) {
					if ( $item['destination_city'] == $destination_city ) {
						$image_id = $item['thumbnail'];
						break;
					}
				}
			}
		}

		// Maybe use fallback image
		if ( ! $image_id ) {
			$image_id = get_field( 'default_thumbnail', 'option' );
		}

		
		ob_start();

			echo '<a href="#" class="deal-thumbnail">';
				echo wp_get_attachment_image( $image_id, 'large' );
			echo '</a>';

			?>
			<style>
				.deal-thumbnail {
					height: 250px;
					display: flex;
				}
				.deal-thumbnail img {
					object-fit: cover;
					min-width: 100%;
					min-height: 100%;
					border-radius: 12px;
				}
			</style>
			<?php

		return ob_get_clean();
	}



	public function deal_city_and_country() {
		$post_id = get_the_ID();

		$city = get_post_meta( $post_id, 'destination', true );
		$country = get_post_meta( $post_id, 'destination_country', true );
		
		ob_start();

			if ( $city ) {
				echo '<span class="deal-destination-city">';
					echo $city;
				echo '</span>';
			}

			if ( $country ) {
				echo '&nbsp;';
				echo '<span class="deal-destination-country">';
					echo $country;
				echo '</span>';
			}

		return ob_get_clean();
	}



	public function deal_tags() {
		$post_id = get_the_ID();

		$weekend_getaway = get_post_meta( $post_id, 'weekend_getaway', true );
		$stops = get_post_meta( $post_id, 'stops', true );
		
		ob_start();

			if ( $stops ) {
				switch ( $stops ) {
					case 'direct':
						$stops_text = 'Direct Flight';
						break;

					case '1stop':
						$stops_text = '1 Stop';
						break;

					case '2stops':
						$stops_text = '2 Stops';
						break;
					
					default:
						$stops_text = '';
						break;
				}

				if ( $stops_text ) {
					echo '<span class="deal-stops-tag">';
						echo $stops_text;
					echo '</span>';
				}
			}

			if ( $weekend_getaway == 'yes' ) {
				echo '<span class="deal-weekend-getaway-tag">';
					echo 'Weekend Getaway';
				echo '</span>';
			}

		return ob_get_clean();
	}



	public function deal_price() {
		$post_id = get_the_ID();
		$price = (string) get_post_meta( $post_id, 'price', true );
		$price = preg_replace( '/\D/', '', $price );
		$currency = get_post_meta( $post_id, 'currency', true );
		$formatted_price = tfc_format_price_with_currency( $currency, $price );
		
		return $formatted_price;
	}







	public function loop_grid_deals_query_filter( $query, $widget ) {
		$user_id = get_current_user_id();
		$user_origin_city = get_user_meta( $user_id, 'origin_city', true );

		if ( is_user_logged_in() && $user_origin_city ) {
			// Get the current user's 'origin_city' meta field

			// Modify the existing meta query or add a new one
			$meta_query = $query->get( 'meta_query' );

			if ( ! is_array( $meta_query ) ) {
				$meta_query = array();
			}

			// Add our custom meta query
			$meta_query[] = array(
				'key'     => 'origin',
				'value'   => $user_origin_city,
				'compare' => '='
			);

			// Update the query's meta query
			$query->set( 'meta_query', $meta_query );

		} else {
			// For non-logged-in users, show random posts with the current publish date
			$query->set( 'orderby', 'rand' );

			$query->set( 'date_query', array(
				'relation' => 'OR',
				array(
					'year'  => date('Y'),
					'month' => date('m'),
					'day'   => date('d'),
				),
				array(
					'year'  => date('Y', strtotime('-1 day')),
					'month' => date('m', strtotime('-1 day')),
					'day'   => date('d', strtotime('-1 day')),
				)
			));
		}

		return $query;
	}





	public function loop_grid_deals_results_filter( $query, $widget ) {
		$settings = $widget->get_settings();
		$query_id = $settings['post_query_query_id'];

		if ( $query_id != 'deals_query' ) {
			return $query;
		}

		// Function to sort posts by 'tier'
		usort( $query->posts, function( $a, $b ) {
			$tier_a = get_post_meta( $a->ID, 'tier', true );
			$tier_b = get_post_meta( $b->ID, 'tier', true );
		
			if ( $tier_a === $tier_b ) {
				return 0;
			}
		
			return ( $tier_a === 'free' ) ? -1 : 1;
		} );


		return $query;
	}
}  
new TFC_Deals_Listing();