<?php
require_once( 'vendor/autoload.php' );
include_once( 'Api.php' );

use Twilio\Rest\Client;

// load .env file
$dotenv = Dotenv\Dotenv::create( __DIR__ );
$dotenv->load();

/**
 * get list of domains and their details
 * @return \Namecheap\Namecheap\Command\ACommand
 */
function getDomainList( $config ) {
	try {
		$command = Namecheap\Api::factory( $config, 'domains.getList' );
		$command->dispatch();
	} catch ( \Exception $e ) {
		die( $e->getMessage() );
	}

	return $command->domains;
}

/**
 * get domain contact information
 *
 * @param $config
 * @param $domain_name
 *
 * @return mixed
 */
function getContacts( $config, $domain_name ) {
	try {
		$command = Namecheap\Api::factory( $config, 'domains.getContacts' );
		$command->domainName( $domain_name )->dispatch();
	} catch ( \Exception $e ) {
		die( $e->getMessage() );
	}

	return $command;
}

/**
 * renew domain name
 *
 * @param $config
 * @param $domain_name
 * @param $years
 *
 * @return \Namecheap\Namecheap\Command\ACommand
 */
function renewDomain( $config, $domain_name, $years ) {
	try {
		$command = Namecheap\Api::factory( $config, 'domains.renew' );
		$command->setParams( array(
			'DomainName' => $domain_name,
			'Years'      => $years
		) )->dispatch();
	} catch ( \Exception $e ) {
		die( $e->getMessage() );
	}

	return $command;
}

/**
 * send Twilio SMS
 *
 * @param $to
 * @param $sms_body
 */
function sendSMS( $to, $sms_body ) {
// Your Account SID and Auth Token from twilio.com/console
	$sid    = getenv( 'TWILIO_SID' );
	$token  = getenv( 'TWILIO_AUTH_TOKEN' );
	$client = new Client( $sid, $token );
	$client->messages->create(
		$to,
		array(
			// A Twilio phone number you purchased at twilio.com/console
			'from' => getenv( 'TWILIO_PHONE_NUMBER' ),
			// the body of the text message you'd like to send
			'body' => $sms_body
		)
	);
}

/**
 * @param array $value
 */
function dd( $value = array() ) {
	echo '<pre>' . "\n";
	print_r( $value );
	die( '</pre>' . "\n" );
}

/**
 * Send all notifications
 *
 * @param $config
 */
function sendNotifications( $config ) {
	$domains = getDomainList( $config );
	foreach ( $domains as $domain ) {
		$contact                 = getContacts( $config, $domain['Name'] );
		$phone_numbers           = [];
		$registrant_phone_number = str_replace( ".", "", $contact->registrant['Phone'] );
		$admin_phone_number      = str_replace( ".", "", $contact->admin['Phone'] );
		if ( $registrant_phone_number != "" ) {
			array_push( $phone_numbers, $registrant_phone_number );
		}
		// ensure registrant and admin phone numbers are not the same to avoid duplicate SMS
		if ( $admin_phone_number != "" && $admin_phone_number != $registrant_phone_number ) {
			array_push( $phone_numbers, $admin_phone_number );
		}
		// domain name expiry notifications
		if ( $domain['IsExpired'] === true ) {
			$expiry_message = "Your domain " . $domain['Name'] . " has expired.Go to your dashboard to reactivate";
		} else {
			// to avoid spamming your users you should start sending this message 2 or 1 month before the domain expiry date.
			$expiry_message = "Your domain " . $domain['Name'] . " will expire on " . $domain['Expires'] . ".";
		}
		//calculate date difference
		$now                  = time(); // today's timestamp
		$expiry_date_array    = explode( "/", $domain['Expires'] );
		$reformat_expiry_date = $expiry_date_array[2] . "-" . $expiry_date_array[0] . "-" . $expiry_date_array[1];
		$expiry_date          = strtotime( $reformat_expiry_date );
		$datediff_days        = round( ( $expiry_date - $now ) / ( 60 * 60 * 24 ) );
		$renewal_status       = false;
		// renew domain 30 days to expiry date
		// domain name renewal notifications
		if ( $datediff_days < 30 && $datediff_days > 0 ) {
			$renew = renewDomain( $config, $domain['Name'], 1 );
			if ( $renew->status() == 'error' ) {
				die( $renew->errorMessage );
			}
			$renewal_message = "Your domain " . $domain['Name'] . " has been renewed successfully.";
			$renewal_status  = true;
		}
		foreach ( $phone_numbers as $phone_number ) {
			sendSMS( $phone_number, $expiry_message );
			if ( $renewal_status ) {
				sendSMS( $phone_number, $renewal_message );
			}
		}
	}
}

$config = new \Namecheap\Config();
$config->apiUser( getenv( 'NAMECHEAP_USERNAME' ) )
       ->apiKey( getenv( 'NAMECHEAP_KEY' ) )
       ->clientIp( getenv( 'IP_ADDRESS' ) )
       ->sandbox( true );
sendNotifications( $config );



