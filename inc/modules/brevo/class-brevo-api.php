<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\EmailCampaignsApi;
use Brevo\Client\Api\ListsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateEmailCampaign;
use Brevo\Client\Model\CreateEmailCampaignRecipients;
use Brevo\Client\Model\CreateEmailCampaignSender;
use Brevo\Client\Model\CreateList;
use Brevo\Client\Model\AddContactToList;
use GuzzleHttp\Client;

class TFC_Brevo_API {

	private $api_key;

	public function __construct() {
		$this->api_key = get_field( 'tfc_brevo_api_key', 'option' );
	}

	public function create_contact( $contact_email, $data ) {
		$config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $this->api_key );
		$config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'partner-key', $this->api_key );

		$apiInstance = new Brevo\Client\Api\ContactsApi(
			new GuzzleHttp\Client(),
			$config
		);

		$createContact = new \Brevo\Client\Model\CreateContact(); // \Brevo\Client\Model\CreateContact | Values to create a contact

		$createContact->setEmail( $contact_email );

		if ( isset( $data['attributes'] ) ) {
			$createContact->setAttributes( $data['attributes'] );
		}

		if ( isset( $data['listIds'] ) ) {
			$createContact->setListIds( $data['listIds'] );
		}

		if ( isset( $data['updateEnabled'] ) ) {
			$createContact->setUpdateEnabled( $data['updateEnabled'] );
		}

		try {
			$apiInstance->createContact( $createContact );

		} catch ( Exception $e ) {
			echo 'Exception when calling ContactsApi->createContact: ', $e->getMessage(), PHP_EOL;
		}
	}


	public function update_contact( $contact_email, $data ) {
		$config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $this->api_key );
		$config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'partner-key', $this->api_key );

		$apiInstance = new Brevo\Client\Api\ContactsApi(
			new GuzzleHttp\Client(),
			$config
		);

		$identifier = $contact_email; // string | Email (urlencoded) OR ID of the contact

		$updateContact = new \Brevo\Client\Model\UpdateContact(); // \Brevo\Client\Model\UpdateContact | Values to update a contact

		if ( isset( $data['attributes'] ) ) {
			$updateContact->setAttributes( $data['attributes'] );
		}
		try {
			$apiInstance->updateContact( $identifier, $updateContact );
		} catch ( Exception $e ) {
			echo 'Exception when calling ContactsApi->updateContact: ', $e->getMessage(), PHP_EOL;
		}
	}


	public function create_brevo_campaign( $list_id, $origin_city, $subscription_type, $campaign_name, $subject, $content ) {
		global $tfc_logger;

		$log_data = array(
			'name' => $campaign_name,
		);

		// Step 1: Initialize the Brevo API client
		$config  = Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $this->api_key );
		$contacts_api = new ContactsApi( new Client(), $config );
		$campaigns_api = new EmailCampaignsApi( new Client(), $config );
		$lists_api = new ListsApi( new Client(), $config );

		tfc_maybe_add_new_origin_city_term( $origin_city );

		try {
			// Step 2: Fetch contacts from the specified segment and filter by city
			$limit = 50; // Number of contacts to fetch per request
			$offset = 0;
			$filtered_emails = array();

			do {
				// Fetch contacts from the segment
				$contacts = $contacts_api->getContacts( $limit, $offset, null, null, 'desc', $list_id, null );

				foreach ( $contacts->getContacts() as $contact ) {
						// Check if the contact has attributes and the specified city in the attributes
						$attributes = $contact['attributes'];
						if ( isset( $attributes ) && is_object( $attributes ) ) {

							if ( 
								( isset( $attributes->CITY ) && strtolower( $attributes->CITY ) === strtolower( $origin_city ) )
								&& ( isset( $attributes->SUBSCRIPTION ) && $attributes->SUBSCRIPTION  == $subscription_type )
							) {
								$filtered_emails[] = $contact['email'];
							}
						}
				}

				$offset += $limit;
			} while ( count( $contacts->getContacts() ) === $limit );

			if ( empty( $filtered_emails ) ) {
				error_log( __( 'No contacts found for the specified criteria.', 'myplugin' ) );
				return;
			}

			$log_data['emails'] = $filtered_emails;


			// Step 3: Create a temporary list to hold the filtered contacts
			$temp_list_name = 'Temp List ' . date("Y-m-d H:i:s");

			$log_data['list'] = $temp_list_name;

			$temp_folder_id = 28;
			$list = new CreateList( array( 'name' => $temp_list_name, 'folderId' => $temp_folder_id) );
			$list_response = $lists_api->createList( $list );
			$list_id = $list_response->getId();

			// Step 4: Add contacts to the temporary list
			$add_contacts = new AddContactToList(array('emails' => $filtered_emails));
			$lists_api->addContactToList( $list_id, $add_contacts );

			// Step 5: Create the email campaign
			$campaign = new CreateEmailCampaign();
			$campaign->setName( $campaign_name );
			$campaign->setSubject( $subject );
			$campaign->setHtmlContent( $content );
			$campaign->setSender( new CreateEmailCampaignSender( array(
				'name'  => get_bloginfo( 'name' ),
				'email' => 'hello@tomsflightclub.com'
			) ) );
			$campaign->setReplyTo( 'hello@tomsflightclub.com' );
			$campaign->setPreviewText( 'Today we have 9 amazing deals from ' . $origin_city . ' for you!' );

			// Step 6: Set the campaign recipients using the temporary list
			$recipients = new CreateEmailCampaignRecipients();
			$recipients->setListIds( array( $list_id ) );
			$campaign->setRecipients( $recipients );

			// Step 7: Create the campaign
			$response = $campaigns_api->createEmailCampaign( $campaign );
			$campaign_id = $response->getId();

			// Step 8: Send the campaign immediately
			$response = $campaigns_api->sendEmailCampaignNow( $campaign_id );


			// Step 9: Remove the temporary list
			$this->schedule_cleanup_action( $list_id, $campaign_id );

			
			
			// Log the success message
			$log_data['status'] = 'Campaign created successfully!';

		} catch ( Exception $e ) {
			// Log the exception message
			$log_data['status'] = sprintf( 'Exception when creating campaign: %s', $e->getMessage() );
		}

		$tfc_logger->log( "Creating a new campaign:\n" . print_r( $log_data, true ) . "\n" );

	}


	/**
	 * Schedules the cleanup action to delete the temporary list and campaign after one week.
	 *
	 * @param int $list_id
	 * @param int $campaign_id
	 */
	private function schedule_cleanup_action( $list_id, $campaign_id ) {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		// Schedule the cleanup action to run one week (7 days) from now
		$time = time() + DAY_IN_SECONDS; // Calculate the timestamp for one week later
		as_schedule_single_action( $time, 'tfc_cleanup_brevo_list_and_campaign', array( 'list_id' => $list_id, 'campaign_id' => $campaign_id ), 'TFC' );
	}


	/**
	 * Cleanup method to delete the temporary list and associated campaign.
	 *
	 * @param int $list_id
	 * @param int $campaign_id
	 */
	public function cleanup_brevo_list_and_campaign( $list_id, $campaign_id ) {
		global $tfc_logger;

		$log_data = array(
			'list_id' => $list_id,
		);

		$config = Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $this->api_key );
		$lists_api = new ListsApi( new Client(), $config );
		// $campaigns_api = new EmailCampaignsApi( new Client(), $config );

		try {
			// Get the list details before deleting
			$list_details = $lists_api->getList( $list_id );
			$list_name = $list_details->getName();

			// Delete the list
			$lists_api->deleteList( $list_id );

			// Optionally, delete the campaign if required
			// $campaigns_api->deleteEmailCampaign( $campaign_id );

			$log_data['list_name'] = $list_name;

			$log_data['status'] = 'List deleted successfully!';
		} catch ( Exception $e ) {
			$log_data['status'] =  sprintf( 'Exception when deleting list: %s', $e->getMessage() );
		}

		$tfc_logger->log( "Deleting an old list:\n" . print_r( $log_data, true ) . "\n" );
	}

	public function delete_temp_lists() {
		$config = Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $this->api_key );
		$lists_api = new ListsApi( new Client(), $config );

		try {
			// Fetch all lists
			$limit = 50; // Number of lists to fetch per request
			$offset = 0;
			$lists_to_delete = array();

			do {
				// Get a batch of lists
				$lists = $lists_api->getLists( $limit, $offset );

				foreach ( $lists->getLists() as $list ) {
					if ( strpos( $list['name'], 'Temp List' ) === 0 ) {
						$lists_to_delete[] = $list['id'];
					}
				}

				$offset += $limit;
			} while ( count( $lists->getLists() ) === $limit );

			// Delete the lists
			foreach ( $lists_to_delete as $list_id ) {
				$lists_api->deleteList( $list_id );
				error_log( sprintf( __( 'Deleted temporary list with ID: %d', 'myplugin' ), $list_id ) );
			}

			error_log( __( 'All temporary lists have been deleted.', 'myplugin' ) );
		} catch ( Exception $e ) {
			error_log( sprintf( __( 'Exception when deleting temporary lists: %s', 'myplugin' ), $e->getMessage() ) );
		}
	}
	
}
