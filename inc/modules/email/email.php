<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_Email {


	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	public function register_rest_route() {
		// https://tomsflightclub.com/wp-json/tfc/v1/email_free
		// Triggered from Airtable
		register_rest_route( 'tfc/v1', 'email_free', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'generate_free_email_template' ),
			'permission_callback' => '__return_true',
		) );

		// https://tomsflightclub.com/wp-json/tfc/v1/email_paid
		// Triggered from Airtable
		register_rest_route( 'tfc/v1', 'email_paid', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'generate_paid_email_template' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function generate_free_email_template( $request ) {
		// error_log( "generate_free_email_template\n" );
		/*
		// Parameters structure
		$parameters = array(
			'Deals' => [], // Array of deals
			'Origin City' => [], // String origin city
		);
		*/

		$parameters = $request->get_params();

		$list_id = intval( get_field( 'tfc_brevo_campaign_list_id', 'option' ) );
		$origin_city = sanitize_text_field( $parameters['Origin City'] );
		$subscription_type = 2; // Free
		$campaign_name = $origin_city . ' ' . date('jS F') . ' (free) ' . date("Y-m-d H:i:s");
		$subject = $origin_city . ' cheap flights on ' . date('jS F');
		$content = $this->get_email_body( $request, 'free' );
		
		$brevo = new TFC_Brevo_API;
		$brevo->create_brevo_campaign( $list_id, $origin_city, $subscription_type, $campaign_name, $subject, $content );
	}

	public function generate_paid_email_template( $request ) {
		// error_log( "generate_paid_email_template\n" );

		$parameters = $request->get_params();

		$list_id = intval( get_field( 'tfc_brevo_campaign_list_id', 'option' ) );
		$origin_city = sanitize_text_field( $parameters['Origin City'] );
		$subscription_type = 1; // Paid
		$campaign_name = $origin_city . ' ' . date('jS F') . ' (paid) ' . date("Y-m-d H:i:s");
		$subject = $origin_city . ' cheap flights on ' . date('jS F');
		$content = $this->get_email_body( $request, 'paid' );
		
		$brevo = new TFC_Brevo_API;
		$brevo->create_brevo_campaign( $list_id, $origin_city, $subscription_type, $campaign_name, $subject, $content );
	}

	public function get_email_body( $request, $type ) {
		$parameters = $request->get_params();

		ob_start();
		
		// Read and echo content from mail_start.html
		$file_path = plugin_dir_path( __FILE__ ) . 'mail_start.php';
		if ( file_exists( $file_path ) ) {
			include $file_path;
		}

		foreach ( $parameters['Deals'] as $index => $deal_data ) {
			if ( $type == 'paid' ) {
				$this->get_table_row( $index, $deal_data, 'link_to_deal' );
			} else if ( $type == 'free' && $index === 0 ) {
				$this->get_table_row( $index, $deal_data, 'link_to_deal' );
			} else {
				$this->get_table_row( $index, $deal_data, 'subscribe_button' );
			}
		}

		// Read and echo content from mail_start.html
		$file_path = plugin_dir_path( __FILE__ ) . 'mail_end.html';
		if ( file_exists( $file_path ) ) {
			echo file_get_contents( $file_path );
		}

		return ob_get_clean();
	}


	private function get_table_row( $index, $deal_data, $button_type ) {
		?>

		<tr>
			<td width="600" class="twocolpad" align="center" style="padding:0px;"> 

				<table style="border-spacing:0; max-width: 548px; height: auto;" role="presentation">
					<tr>
						<td width="548" class="two-columns darkmode-transparent" style="padding:0;font-size:0;text-align: center;  border-radius: 10px;">

							<!-- left column -->
							<table class="left-column" style="border-spacing:0;vertical-align:top;width:100%;max-width:240px;display:inline-block;" role="presentation">
								<tr>
									<td class="padding" style="padding:10px;">

										<table class="content" style="border-spacing:0;text-align: left;" role="presentation">
											<tr>
												<td style="padding:0px;">
													<?php
														$alt_text = $deal_data['Origin City'] . ' to ' . $deal_data['Destination City'] . ' flight for ' . ( new DateTime( $deal_data['Date'] ) )->format( 'F' );
													?>
													<a href="https://tomsflightclub.com/" target="_blank">
														<img class="imgsize" src="<?php echo esc_url( $deal_data['Deal City Image'] ); ?>" alt="<?php echo sanitize_text_field( $alt_text ); ?>" width="220" style="border:0;width:100%;max-width:220px;height:auto;display:block; border-radius: 10px;">
													</a>
												</td>
											</tr>
										</table>

									</td>
								</tr>
							</table>

							<!-- Right column -->
							<table class="column" style="border-spacing:0; vertical-align:top; width:100%; max-width:300px; display:inline-block;" role="presentation">
								<tr>
									<td class="paddingrightcol" style="padding:0px; text-align: center;">

										<table class="content" style="width:100%; border-spacing:0; text-align: left;" role="presentation">
											
											<!-- Origin to destination (item title) -->
											<tr>
												<td style="width: 100%; padding: 10px 10px 6px 0; background-color: transparent;">
													<h2  style="font-size: 14px; color:#80889E; font-family: 'Inter', Arial, sans-serif; background-color: transparent;">
														<span class="darkmode-bg" style="color:#000000; font-size: 18px; font-weight: bold;">
															<?php echo sanitize_text_field( $deal_data['Origin City'] ); ?>
														</span>
														&nbsp;to&nbsp;
														<span class="darkmode-bg" style="color:#000000; font-size: 18px; font-weight: bold;">
															<?php echo sanitize_text_field( $deal_data['Destination City'] ); ?>
														</span>
														<span class="darkmode-bg" style="color: #FFFFFF; font-size: 11px; line-height: 1em; font-weight: 500; padding: 3px 6px; background-color: #FF0000; border-radius: 5px 5px 5px 5px; vertical-align: middle; display: inline-block; margin-bottom: 4px;">
															<?php echo sanitize_text_field( $deal_data['Destination Country'] ); ?>
														</span>
													</h2>

													<h2 style="color:#80889E; font-family: 'Inter', Arial, sans-serif; font-size: 14px;padding: 4px 0 0 0; font-weight: normal;">
														Found <span><?php echo ( new DateTime( $deal_data['Date'] ) )->format( 'F jS' ); ?></span>
													</h2>                             
												</td>
											</tr>


											<tr>
												<td align="left" style="padding: 8px 0 0 0; margin: 0;background-color: transparent;">

													<table align="left" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; border-spacing:0; background-color:transparent;">
														<tr>
															<td style="padding: 0; font-size: 14px;" >
																<img width="20" style="vertical-align: middle; margin-right: 6px;" src="https://i.postimg.cc/nrFmsKQy/Calendar.png" alt="" /><span style="vertical-align: middle;" ><?php echo sanitize_text_field( $deal_data['Trip Duration'] ); ?> Day Return Trip</span>
															</td>

															<td class="leftpad" style="border-radius:4px; font-size: 14px; padding: 0 0 0 6px; background-color: transparent;">                                           
																<img width="20" style="vertical-align: middle; margin-right: 6px;" src="https://i.postimg.cc/fTgdL9WP/Plane.png"  alt="" />
																<?php if ( $button_type == 'link_to_deal' ) { ?>
																	<span style="vertical-align: middle;" >
																	<?php if ( $button_type == 'link_to_deal' ) {
																	$outbound_date = $deal_data['Outbound Date'];
																	$return_date = $deal_data['Outbound Day'];

																	$outbound_timestamp = strtotime( $outbound_date );
																	$return_timestamp = strtotime( $return_date );

																	$date_format = 'j M';
																	echo date_i18n( $date_format, $outbound_timestamp ) . ' - ' . date_i18n( $date_format, $return_timestamp );
																} ?>
																	</span>
																<?php } else if ( $button_type == 'subscribe_button' ) { ?>
																	<span style="vertical-align: middle;" ><?php echo ( new DateTime( $deal_data['Outbound Date'] ) )->format( 'F Y' ); ?></span>
																<?php } ?>
															</td>
														</tr>

														<tr>
															<td colspan="2" style="font-size: 14px; background-color: transparent; padding-top: 10px">                                           
																<?php 
																$weekend_getaway = $deal_data['Weekend Getaway'];
																$stops = $deal_data['Direct Flight'];
																
														
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
																		echo '<span class="badge" style="">';
																			echo $stops_text;
																		echo '</span>';
																	}
																}
													
																if ( $weekend_getaway == 'yes' ) {
																	echo '<span class="badge" style="padding-left: 10px;">';
																		echo 'Weekend Getaway';
																	echo '</span>';
																}
																?>
															</td>
														</tr>
													</table>
												</td>
											</tr>


											<!-- Price and button -->
											<tr>
												<td class="pricepad" style="padding: 14px 0 10px 0; margin: 0; background-color:transparent;">
													<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-spacing:0;">
														<tr>
															<td style="font-size: 16px; color: #415BE7; font-family: 'Inter', Arial, sans-serif; padding: 0 10px 0 0; font-weight: bold; border: 0;">
																<?php
																$price = $deal_data['Price'];
																$currency = $deal_data['Currency'][0]['name'];
																$formatted_price = tfc_format_price_with_currency( $currency, $price );
																
																echo sanitize_text_field( $formatted_price ); 
																?>
															</td>
															<td align="center" style="border-radius:10px; font-size: 20px;background-color: #415BE7;" bgcolor="#415BE7" >
																<?php if ( $button_type == 'link_to_deal' ) { ?>
																	<a href="<?php echo esc_url( $deal_data['Skyscanner Deal Link'] ); ?>" target="_blank" style="font-size: 14px;font-weight: normal;text-decoration: none;color: #ffffff;background-color: #415BE7;border:1px solid #263EC4;border-radius:10px;padding:10px 18px;display: inline-block; font-family: 'Inter', Arial, sans-serif;">
																		Get Deal
																	</a>
																<?php } else if ( $button_type == 'subscribe_button' ) { ?>
																	<a href="https://tomsflightclub.com/subscribe/" target="_blank" style="font-size: 14px;font-weight: normal;text-decoration: none;color: #ffffff;background-color: #415BE7;border:1px solid #263EC4;border-radius:10px;padding:10px 18px;display: inline-block; font-family: 'Inter', Arial, sans-serif;">
																		Become a Premium Member
																	</a>
																<?php } ?>
															</td> 
														</tr>
													</table>
												</td>
											</tr>


										</table>
									</td>
								</tr>


							</table>

						</td>
					</tr>
				</table>



			</td>
		</tr>

		<tr>
			<td style="padding: 0;">
				<table width="100%" style="border-spacing: 0;" role="presentation">
					<tr>
						<td height="5" ></td>
					</tr>
				</table>
			</td>
		</tr>

		<?php

	}
}
new TFC_Email();