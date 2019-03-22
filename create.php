<?php
require_once( 'vendor/autoload.php' );
include_once( 'Api.php' );
// load .env files
$dotenv = Dotenv\Dotenv::create( __DIR__ );
$dotenv->load();

/**
 * check domain name availability
 *
 * @param $config
 * @param $domain_names array
 *
 * @return \Namecheap\Namecheap\Command\ACommand
 */
function checkAvailability( $config, $domain_names ) {
	try {
		$command = Namecheap\Api::factory( $config, 'domains.check' );
		$command->domainList( $domain_names )->dispatch();
	} catch ( \Exception $e ) {
		die( $e->getMessage() );
	}

	return $command;
}

/**
 * Register a domain name
 *
 * @param $params
 * @param $config
 *
 * @return string
 */
function createDomain( $config, $params ) {
	try {
		$command = Namecheap\Api::factory( $config, 'domains.create' );
		$command->setParams( $params )->dispatch();
	} catch ( \Exception $e ) {
		die( $e->getMessage() );
	}

	return $command;
}
$config = new \Namecheap\Config();
$config->apiUser( getenv( 'NAMECHEAP_USERNAME' ) )
       ->apiKey( getenv( 'NAMECHEAP_KEY' ) )
       ->clientIp( getenv( 'IP_ADDRESS' ) )
       ->sandbox( true );
$domain_list  = array( 'twilio.com', 'sendgrid.com' );
$check_create = checkAvailability( $config, $domain_list );
foreach ( $check_create->domains as $domain => $available ) {
	if ( $available === true ) {
		// all parameters are mandatory
		$domain_details = array(
			'DomainName'              => $domain,
			'RegistrantFirstName'     => 'Michael',
			'RegistrantLastName'      => 'Jaroya',
			'RegistrantAddress1'      => '2556',
			'RegistrantCity'          => 'KISUMU',
			'RegistrantStateProvince' => 'KISUMU',
			'RegistrantPostalCode'    => '40100',
			'RegistrantCountry'       => 'KENYA',
			'RegistrantPhone'         => '+254.711440682',
			'RegistrantEmailAddress'  => 'liddypedia@gmail.com',
			'TechFirstName'           => 'Lidmak',
			'TechLastName'            => 'Ltd',
			'TechAddress1'            => '2556',
			'TechCity'                => 'KISUMU',
			'TechStateProvince'       => 'KISUMU',
			'TechPostalCode'          => '40100',
			'TechCountry'             => 'KENYA',
			'TechPhone'               => '+254.711440682',
			'TechEmailAddress'        => 'liddypedia@gmail.com',
			'AdminFirstName'          => 'Lidmak',
			'AdminLastName'           => 'Ltd',
			'AdminAddress1'           => '2556',
			'AdminCity'               => 'KISUMU',
			'AdminStateProvince'      => 'KISUMU',
			'AdminPostalCode'         => '40100',
			'AdminCountry'            => 'KENYA',
			'AdminPhone'              => '+254.711440682',
			'AdminEmailAddress'       => 'liddypedia@gmail.com',
			'AuxBillingFirstName'     => 'Lidmak',
			'AuxBillingLastName'      => 'Ltd',
			'AuxBillingAddress1'      => '2556',
			'AuxBillingCity'          => 'KISUMU',
			'AuxBillingStateProvince' => 'KISUMU',
			'AuxBillingPostalCode'    => '40100',
			'AuxBillingCountry'       => 'KENYA',
			'AuxBillingPhone'         => '+254.711440682',
			'AuxBillingEmailAddress'  => 'liddypedia@gmail.com',
		);
		$command        = createDomain($config,$domain_details );
		if ( $command->status() == 'error' ) {
			print_r( $command->errorMessage )."<br>";
		}
		print_r($command)."<br>";
	}
}