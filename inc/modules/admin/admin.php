<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_deal_log_submenu' ) );
		add_action( 'wp_ajax_tfc_get_log_file', array( $this, 'get_log_file' ) );
	}

	public function add_deal_log_submenu() {
		add_submenu_page(
			'edit.php?post_type=deal', // Parent slug (CPT menu)
			'Log',  // Page title
			'Log', // Menu title
			'manage_options', // Capability
			'tfc-log', // Menu slug
			array( $this, 'deal_log_page_callback' ) // Callback function
		);
	}

	public function deal_log_page_callback() {

		echo '<h1>Log</h1>';

		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Log</th>
			</tr>
			<tr valign="top">
				<td style="padding: 0;">
						<select id="tfc-select-log" style="width: 200px;" autocomplete="off">
					<?php
					$log_files = $this->get_log_files();
					foreach ( $log_files as $index => $log_file ) {

						$file_name = wp_basename( $log_file );
						if ( $index == 0 ) {
							echo "<option selected>{$file_name}</option>";
						} else {
							echo "<option>{$file_name}</option>";
						}
					}
					?>
						</select>
						<button id="tfc-load-log" class="button action">Apply</button>
				</td>
			</tr>
		</table>

		<div id="tfc-admin-log">
			<pre id="tfc-log-output">Loading ...</pre>
			<div id="tfc-scroll-to-bottom">New Updates ...</div>
		</div>


		<label>
			<input type="checkbox" id="tfc-autorefresh" checked>
			Auto update
		</label>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('#tfc-check-now').click(function (e) {
						e.preventDefault();

						var fetch_xml_data = {
							action: 'tfc_check_new',
						};

						// FETCHING XML
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: fetch_xml_data,
							async: false,
							beforeSend: function (xhr) {
								$('#tfc-check-now').addClass('disabled');
								$('#tfc_ajax_result').html('&nbsp;');
							},
							success: function (response) {
								$('#tfc-check-now').removeClass('disabled');
								$('#tfc_ajax_result').html(response);
							}
						});
				});

				// display log file
				load_log_file();
				$('#tfc-autorefresh').change(function () {
						update_log();
				});
				update_log();

				var refresh_interval;

				function update_log() {
						var autorefresh = $('#tfc-autorefresh').is(':checked');
						if (autorefresh) {
							load_log_file();
							refresh_interval = setInterval(function () {
								load_log_file();
							}, 5000);
						} else {
							clearInterval(refresh_interval);
						}
				}

				$('#tfc-select-log').change(function () {
						// $( '#tfc-load-log' ).text( 'Load' );
				});

				$('#tfc-load-log').click(function (e) {
						e.preventDefault();

						// ajax load log file
						load_log_file();

						// get scrolable element
						var element = document.getElementById("tfc-log-output");

						// scroll log to bottom
						element.scrollTop = element.scrollTopMax;
				});

				function load_log_file() {
					var fetch_xml_data = {
						action: 'tfc_get_log_file',
						filename: $("#tfc-select-log option:selected").text()
					};
					var original_log_lines_count = $('#tfc-log-output').html().split(/\n/).length;


					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: fetch_xml_data,
						success: function (response) {
								// get scrolable element
								var element = $('#tfc-log-output')[0];
								var scrollTop = element.scrollTop;
								var scrollTopMax = element.scrollHeight - element.clientHeight;

								$('#tfc-log-output').html(response);

								// scroll to bottom if not scrolled by user
								if (scrollTop == scrollTopMax) {
									element.scrollTop = element.scrollHeight;
									$('#tfc-scroll-to-bottom').removeClass('active');
								} else {
									var new_log_lines_count = $('#tfc-log-output').html().split(/\n/).length;
									if (original_log_lines_count != new_log_lines_count) {
										$('#tfc-scroll-to-bottom').addClass('active');
									}
								}
						}
					});
				}
						

				// hide scroll to bottom button when scrolled down
				$('#tfc-log-output').scroll(function (e) {
						var element = e.target;
						var scrollTop = element.scrollTop;
						var scrollTopMax = element.scrollHeight - element.clientHeight;
						if (scrollTop == scrollTopMax) {
							$('#tfc-scroll-to-bottom').removeClass('active');
						}
				});

				// scroll to bottom click
				$('#tfc-scroll-to-bottom').click(function () {
						var element = $('#tfc-log-output')[0];
						var scrollTopMax = element.scrollHeight - element.clientHeight;

						$('#tfc-log-output').animate({
							scrollTop: scrollTopMax
						}, 400);
				});
			});
		</script>

		<style>
			#tfc-admin-log {
				position: relative;
				margin-right: 20px;
			}

			#tfc-log-output {
				overflow: auto;
				height: 280px;
				border: 1px solid #999999;
				padding: 10px 10px 20px 10px;
				background-color: #F5F5F5;
			}

			#tfc-scroll-to-bottom {
				display: none;
				position: absolute;
				bottom: 20px;
				left: 50%;
				transform: translate(-50%, -10px);
				background-color: #676767;
				color: #FFFFFF;
				padding: 8px 20px 10px;
				border-radius: 100px;
				font-weight: bold;
				cursor: pointer;
			}

			#tfc-scroll-to-bottom.active {
				display: block;
			}
		</style>
		<?php
	}

	public function get_log_files() {
		// ensure we have today`s log file created
		global $tfc_logger;
		$tfc_logger->log();

		// get log files list
		$log_folder_path = wp_upload_dir()['basedir'] . '/tfc/logs/';
		$log_files = glob( $log_folder_path . "/tfc-*.log" );

		// return reversed array
		return array_reverse( $log_files );
	}

	public function get_log_file() {

		$file_name = $_POST['filename'];
		$file = wp_upload_dir()['basedir'] . '/tfc/logs/' . $file_name;

		if ( file_exists( $file ) ) {
			echo esc_html( file_get_contents( $file ) );
		}

		// do not forget to fire wp_die() on ajax call
		wp_die();
	}


}

new TFC_Admin();