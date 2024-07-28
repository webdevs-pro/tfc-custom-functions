<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ElementorPro\Modules\LoopBuilder\Files\Css\Loop_Dynamic_CSS;

$post_counter;

class TFC_Deals_Listing {

	public function __construct() {
		add_shortcode( 'tfc_deals_listing', array( $this, 'deals_listing_shortcode' ) );
		add_shortcode( 'tfc_deals_listing_item_button', array( $this, 'deals_listing_item_button' ) );
		add_shortcode( 'tfc_deals_listing_item_image', array( $this, 'deals_listing_item_image' ) );
	}


	public function deals_listing_shortcode() {

		global $post_counter;

		// Ensure to include necessary WordPress functions
		if ( ! function_exists( 'wp_date' ) ) {
			require_once( ABSPATH . 'wp-includes/functions.php' );
		}

		// Get the current date in the required format
		$current_date = wp_date('Ymd');

		// Define the query arguments
		$args = array(
			'post_type' => 'deal',
			'posts_per_page' => 9,
			// 'meta_query' => array(
			// 	array(
			// 		'key' => 'deal_found_on',
			// 		'value' => $current_date,
			// 		'compare' => '='
			// 	)
			// )
		);

		
		if ( function_exists( 'get_field' ) ) {
			$template_id = get_field( 'deals_loop_item_template', 'option' );
		} else {
			return 'ACF plugin not activated';
		}

		if ( ! $template_id ) {
			return 'Please select loop item template.';
		}

		$post_counter = 1;


		// Execute the query
		$query = new WP_Query( $args );

		$posts = $query->posts;

		// Function to sort posts by 'tier'
		usort( $query->posts, function( $a, $b ) {
			$tier_a = get_post_meta( $a->ID, 'tier', true );
			$tier_b = get_post_meta( $b->ID, 'tier', true );
		
			if ( $tier_a === $tier_b ) {
				return 0;
			}
		
			return ( $tier_a === 'free' ) ? -1 : 1;
		} );

		ob_start();

		
			if ( $query->have_posts() ) {
				echo '<div class="deals-grid">';

					while ( $query->have_posts() ) {
						$query->the_post();

						if ( $post_counter === 1 ) {
							// Output lopp CSS only once on page
							echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, true );
						} else {
							echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, false );
						}

						$post_counter++;
					}
					// Restore original post data
					wp_reset_postdata();

				echo '</div>';
			} else {
				// No posts found
				echo '<p>No deals found for today.</p>';
			}

				
			?>
			<style>
				.deals-grid {
					display: grid;
					grid-template-columns: 1fr 1fr 1fr;
					gap: 24px;
				}
				@media(max-width: 1024px) and (min-width:768px) {
					.deals-grid {
						grid-template-columns: 1fr 1fr;
					}
				}
				@media(max-width:767px) {
					.deals-grid {
						grid-template-columns: 1fr;
					}
				}
			</style>

			<?php

		return ob_get_clean();
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

		// Render dates first
		$outbound_date = get_post_meta( $post_id, 'outbound_date', true );
		$return_date = get_post_meta( $post_id, 'return_date', true );

		$outbound_timestamp = strtotime( $outbound_date );
		$return_timestamp = strtotime( $return_date );

		$date_format = 'j M';

		echo '<p class="tfc-loop-deal-dates">' . date_i18n( $date_format, $outbound_timestamp ) . ' - ' . date_i18n( $date_format, $return_timestamp ) . '</p>';

		$deal_url = get_post_meta( $post_id, 'skyscanner_deal_url', true );
		echo '<a class="tfc-loop-get-deal" href="' . $deal_url . '" target="_blank" role="button">Get Deal</a>';
	}


	private function render_become_a_member_button( $post_id ) {
		echo '<a class="tfc-loop-subscribe" href="/subscribe-london" role="button">Become a Free Member</a>';
	}


	private function render_become_a_premium_member_button( $post_id ) {
		echo '<a class="tfc-loop-subscribe" href="/subscribe" role="button">Become a Premium Member</a>';
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

			echo '<div class="deal-thumbnail">';
				echo wp_get_attachment_image( $image_id, 'large' );
			echo '</div>';

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

}
new TFC_Deals_Listing();