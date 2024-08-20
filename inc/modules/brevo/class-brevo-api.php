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
}


// // Example data
// $data = array(
// 	'attributes' => [
// 		'CITY' => 'London',
// 		'SUBSCRIPTION' => 1
// 	]
// );