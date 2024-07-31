<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TFC_Email {


	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	public function register_rest_route() {
		// https://tomsflightclub.com/wp-json/tfc/v1/email
		register_rest_route( 'tfc/v1', 'email', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'generate_email_template' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function generate_email_template( $request ) {
		$parameters = $request->get_params();

		// error_log( "parameters\n" . print_r( $parameters[0]['Array'], true ) . "\n" );

		ob_start();
		
		// Read and echo content from mail_start.html
		$file_path = plugin_dir_path( __FILE__ ) . 'mail_start.php';
		if ( file_exists( $file_path ) ) {
			include $file_path;
		}

		foreach ( $parameters[0]['Array'] as $index => $deal_data ) {
			$this->get_table_row( $index, $deal_data );
		}

		// Read and echo content from mail_start.html
		$file_path = plugin_dir_path( __FILE__ ) . 'mail_end.html';
		if ( file_exists( $file_path ) ) {
			echo file_get_contents( $file_path );
		}

		$html = ob_get_clean();

		error_log( "html\n" . print_r( $html, true ) . "\n" );

		$webhook_url = get_field( 'makecom_webhook_url', 'option' );

		wp_remote_post( 
			$webhook_url,
			array(
				'body' => array(
					'html' => $html,
				),
			)
		);
		
		wp_send_json_success( 
			array(
				'success' => true,
			)
			, 200 
		);
	}


	private function get_table_row( $index, $deal_data ) {
		?>

		<tr>
			<td width="600" class="twocolpad" align="center" style="padding:0px;"> 

				<table style="border-spacing:0; max-width: 548px; height: auto;" role="presentation">
					<tr>
						<td class="darkmode-transparent" width="548" class="two-columns" style="padding:0;font-size:0;text-align: center;  border-radius: 10px;">

							<!-- left column -->
							<table class="left-column" style="border-spacing:0;vertical-align:top;width:100%;max-width:240px;display:inline-block;" role="presentation">
								<tr>
									<td class="padding" style="padding:10px;">

										<table class="content" style="border-spacing:0;text-align: left;" role="presentation">
											<tr>
												<td style="padding:0px;">
													<a  href="https://tomsflightclub.com/" target="_blank">
														<img class="imgsize" src="<?php echo $deal_data['Deal City Image']; ?>" width="220" alt="" style="border:0;width:100%;max-width:220px;height:auto;display:block; border-radius: 10px;">
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
													<h1  style="font-size: 14px; color:#80889E; font-family: 'Inter', Arial, sans-serif; background-color: transparent;">
														<span class="darkmode-bg" style="color:#000000; font-size: 18px; font-weight: bold;">
															<?php echo $deal_data['Origin City']; ?>
														</span>
														&nbsp;to&nbsp;
														<span class="darkmode-bg" style="color:#000000; font-size: 18px; font-weight: bold;">
															<?php echo $deal_data['Destination City']; ?>
														</span>
													</h1>

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
																<img width="20" style="vertical-align: middle; margin-right: 6px;" src="https://i.postimg.cc/nrFmsKQy/Calendar.png" alt="" /><span style="vertical-align: middle;" ><?php echo $deal_data['Trip Duration']; ?> Day Return Trip</span>
															</td>

															<td class="leftpad" style="border-radius:4px; font-size: 14px; padding: 0 0 0 6px; background-color: transparent;">                                           
																<img width="20" style="vertical-align: middle; margin-right: 6px;" src="https://i.postimg.cc/fTgdL9WP/Plane.png"  alt="" /><span style="vertical-align: middle;" ><?php echo ( new DateTime( $deal_data['Outbound Date'] ) )->format( 'F jS' ); ?></span>
															</td>
														</tr>
													</table>
												</td>
											</tr>



											<tr>
												<td class="pricepad" style="padding: 14px 0 0 0; margin: 0; background-color:transparent;">
													<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-spacing:0;">
														<tr>
															<td style="font-size: 16px; color: #415BE7; font-family: 'Inter', Arial, sans-serif; padding: 0 10px 0 0; font-weight: bold; border: 0;">
																<?php echo $deal_data['Currency and Price']; ?>
															</td>
															<td align="center" style="border-radius:10px; font-size: 20px;background-color: #415BE7;" bgcolor="#415BE7" >
																<?php if ( $deal_data['Subscription Tier'] == 'free' ) { ?>
																	<a href="<?php echo $deal_data['Skyscanner Deal Link']; ?>" target="_blank" style="font-size: 14px;font-weight: normal;text-decoration: none;color: #ffffff;background-color: #415BE7;border:1px solid #263EC4;border-radius:10px;padding:10px 18px;display: inline-block; font-family: 'Inter', Arial, sans-serif;">
																		Get Deal
																	</a>
																<?php } else if ( $deal_data['Subscription Tier'] == 'paid' ) { ?>
																	<a href="https://tomsflightclub.com/subscribe/" target="_blank" style="font-size: 14px;font-weight: normal;text-decoration: none;color: #ffffff;background-color: #415BE7;border:1px solid #263EC4;border-radius:10px;padding:10px 18px;display: inline-block; font-family: 'Inter', Arial, sans-serif;">
																		Become a Premiunm Member
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