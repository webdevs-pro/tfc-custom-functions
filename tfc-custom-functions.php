<?php
/**
 * Plugin Name: Toms Flight Club Custom Functions
 * Plugin URI: https://github.com/webdevs-pro/tfc-custom-functions
 * Version: 1.55
 * Description: A place for custom functions for tomsflightclub.com website
 * Author: Alex Ishchenko
 * Author URI: https://website.cv.ua
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

final class TFC_Plugin {

	public function __construct() {
		$this->define_constants();
		$this->include_files();
		$this->init_plugin_update_checker();
	}

	function define_constants() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ( ABSPATH . 'wp-admin/includes/file.php' );
		}
		define( 'TFC_PLUGIN_VERSION', get_plugin_data( __FILE__ )['Version'] );
		// define( 'TFC_HOME_PATH', get_home_path() );
		define( 'TFC_HOME_PATH', ABSPATH );
		define( 'TFC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'TFC_PLUGIN_DIR', dirname( __FILE__ ) );
		define( 'TFC_PLUGIN_FILE', __FILE__ );
		define( 'TFC_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
	}

	function include_files() {
		require_once ( TFC_PLUGIN_DIR . '/inc/vendor/autoload.php' );
		require_once ( TFC_PLUGIN_DIR . '/inc/plugin.php' );
	}

	function init_plugin_update_checker() {
		$UpdateChecker = PucFactory::buildUpdateChecker(
			'https://github.com/webdevs-pro/tfc-custom-functions',
			__FILE__,
			'tfc-custom-functions'
		);
		
		//Set the branch that contains the stable release.
		$UpdateChecker->setBranch( 'main' );
	}

}

new TFC_Plugin();



add_filter( 'hello_elementor_viewport_content', function( $viewport_content ) {
	return 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
} );

add_filter( 'elementor/template/viewport_tag', function( $meta_tag, $context ) {
	return '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">';
}, 10, 2 );



/**
 * Delete 'deal' posts older than a week along with their attachments.
 */
function tfc_delete_old_deal_posts() {
	// Calculate the date a week ago from today.
	$date_query = date( 'Y-m-d H:i:s', strtotime( '-1 week' ) );

	// Query for posts older than a week.
	$query = new WP_Query(
		array(
			'post_type'      => 'deal',
			'posts_per_page' => -1,
			'date_query'     => array(
					array(
						'before' => $date_query,
					),
			),
			'fields'         => 'ids', // Only get post IDs.
		)
	);

	// Loop through the post IDs and delete them along with their attachments.
	foreach ( $query->posts as $post_id ) {
		// Get the attachments for the post.
		$attachments = get_attached_media( '', $post_id );

		// Delete each attachment.
		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}

		// Delete the post.
		wp_delete_post( $post_id, true ); // true to force delete without moving to trash.
	}
}

/**
* Schedule the daily event to delete old deal posts if not already scheduled.
*/
function tfc_schedule_daily_delete_old_deal_posts() {
	if ( ! wp_next_scheduled( 'tfc_daily_delete_old_deal_posts' ) ) {
		wp_schedule_event( time(), 'daily', 'tfc_daily_delete_old_deal_posts' );
	}
}
add_action( 'wp', 'tfc_schedule_daily_delete_old_deal_posts' );

// Hook the delete function to the scheduled event.
add_action( 'tfc_daily_delete_old_deal_posts', 'tfc_delete_old_deal_posts' );

/**
* Clear the scheduled event upon deactivation.
*/
function tfc_clear_scheduled_delete_old_deal_posts() {
	$timestamp = wp_next_scheduled( 'tfc_daily_delete_old_deal_posts' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'tfc_daily_delete_old_deal_posts' );
	}
}
register_deactivation_hook( __FILE__, 'tfc_clear_scheduled_delete_old_deal_posts' );