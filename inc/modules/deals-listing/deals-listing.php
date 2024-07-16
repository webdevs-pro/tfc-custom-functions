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
			'meta_query' => array(
				array(
					'key' => 'deal_found_on',
					'value' => $current_date,
					'compare' => '='
				)
			)
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

		ob_start();

		
			if ( $query->have_posts() ) {
				echo '<div class="deals-grid">';

					while ( $query->have_posts() ) {
						$query->the_post();

						if ( $post_counter === 1 ) {
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
		

		ob_start();

			echo $post_counter;

		return ob_get_clean();
	}



	public function deals_listing_item_image() {
		$post_id = get_the_id();
		$destination_city = get_post_meta( $post_id, 'destination', true );

		if ( function_exists( 'get_field' ) ) {
			$thumbnails_repeater = get_field( 'deal_thumbnails', 'option' );
		} else {
			return '';
		}

		$image_id = null;

		if ( is_array( $thumbnails_repeater ) && ! empty( $thumbnails_repeater ) ) {
			foreach ( $thumbnails_repeater as $item ) {
				if ( $item['destination_city'] == $destination_city ) {
					$image_id = $item['thumbnail'];
					break;
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