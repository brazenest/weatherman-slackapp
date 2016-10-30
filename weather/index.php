<?php
/**
	* Weather God -- A weather app for Slack
	*
	* @copyright 2016 Alden Gillespy
	* @license Proprietary. All rights reserved.
	* @author Alden Gillespy
	* @version 1.2
	* @since 1.0
	* @package AldenG_Slackapps
	*/
namespace AldenG\Slackapps\Weather;

require_once __DIR__ . '/lib/Darksky/ApiClient.class.php';
require_once __DIR__ . '/lib/Slack/Response.class.php';
require_once __DIR__ . '/lib/GoogleMapsWebServices/GeocodingSdk/ApiClient.class.php';
require_once __DIR__ . '/lib/GoogleMapsWebServices/GeocodingSdk/Exceptions/InvalidZipcodeException.class.php';

use AldenG\DarkskySdk\ApiClient as DarkskyApi;
use AldenG\SlackSdk\Response as SlackResponse;
use AldenG\SlackSdk\ResponseAttachment as SlackResponseAttachment;

use AldenG\GoogleMapsWebServices\GeocodingSdk\ApiClient as GeocodingApi;
use AldenG\GoogleMapsWebServices\GeocodingSdk\Exceptions\InvalidZipcodeException as InvalidZipcodeException;

// App descriptors:
define( 'APP_VERSION_NUMBER',					'1.2.1' );
define( 'APP_VERSION_DATE',						'2016-10-30' );

// Requirements:
define( 'SLACK_COMMAND_TOKEN', 				'GPJWCJYsbHH06DpoLwbLVsBy' );
define( 'DARKSKY_API_SECRET',					'b6876b3993226c84627ba2a331ed697b' );
define( 'GEOCODING_API_SECRET',				'AIzaSyBQM7dPovqPEwOg1-rVy9Xv1uOqADnop1U' );

// Defaults:
define( 'DEFAULT_ENDPOINT_NAME',			'forecast' );
define( 'DEFAULT_FORECAST_TYPE',			'currently' );
define( 'DEFAULT_LOCATION_LATITUDE', 	28.480 );
define( 'DEFAULT_LOCATION_LONGITUDE',	-81.455 );

define( 'REQUEST_TYPE', '_POST');

$ARGS =& ${REQUEST_TYPE};
/*
	Begin the script.
*/

if(
	! array_key_exists( 'token', $ARGS )
	|| ( $ARGS[ 'token' ] !== SLACK_COMMAND_TOKEN )
)
{
	http_response_code(403);
	exit;
}


	// sendAdminNotification();

	$weatherRequestParams = [
		'forecastType'	=> DEFAULT_FORECAST_TYPE,
		'latitude'			=> DEFAULT_LOCATION_LATITUDE,
		'longitude'			=> DEFAULT_LOCATION_LONGITUDE,
		'location'			=> 'at Concepta HQ',
	];
// if the request has argument(s)...
if( isset( $ARGS[ 'text' ] ) && ! empty( trim( $ARGS[ 'text' ] ) ) ) {

	$argString = trim( $ARGS[ 'text' ] );
	$argSwitchEndPos = strpos($argString, ' ');

	if( 0 === strpos($argString, '-') )
	{
		if( false !== $argSwitchEndPos ) {
			$argSwitch = substr($argString, 1, $argSwitchEndPos);
			$argString = substr($argString, trim($argSwitchEndPos+1) );
		} else {
			$argSwitch = substr($argString, 1);
			$argString = null;
		}

		switch( $argSwitch )
		{

			case '?':

				$response = new SlackResponse();
				$response->addAttachment( new SlackResponseAttachment("version " . APP_VERSION_NUMBER . " (" . APP_VERSION_DATE . ")\n© " . date('Y') . " Alden Gillespy", "About Weatherman") );
				header('Content-Type: application/json');
				http_response_code(200);
				echo json_encode( $response );
				die();
		}
	}

	if( isset($argString) )
	{
	try {
		// translate the argument into a coordinates tuple.
		$geocodingApi = new GeocodingApi( GEOCODING_API_SECRET );
		$geodata = $geocodingApi->locate( $argString );

		$weatherRequestParams[ 'latitude' ] 	= $geodata[ 'latitude' ];
		$weatherRequestParams[ 'longitude' ]	= $geodata[ 'longitude' ];
		$weatherRequestParams[ 'location' ]		= 'for ' . $geodata[ 'location' ];
	}
	catch( InvalidZipcodeException $e )
	{
		// for now, we do nothing here, as we've already set defaults.
	}
	}

}

	$weatherData = requestWeather( $weatherRequestParams );

	$slackResponse = new SlackResponse( 'Current conditions ' . $weatherRequestParams[ 'location' ] );

	$responseDetailsText = ( (int) $weather->temperature ) . '° ' . $weather->summary . " \n winds " . ( (int) $weather->windSpeed ) . ' mph from ' . DarkskyApi::convertDegreesToCompass( $weather->windBearing );
	$weather = $weatherData->{$weatherRequestParams['forecastType']}; // i.e. `currently`
	$slackResponse->addAttachment( new SlackResponseAttachment( $responseDetailsText ) );

	header( 'Content-Type: application/json' );
http_response_code(200);

	echo json_encode( $slackResponse );

/**
	* Sends a REST message to Slack, to report a use of the `/weather` slash command.
	*
	* @param	array		The message's metadata and body
	*
	* @return	boolean	An indication of Slack's successful (or failed) receipt of the notification request.
	*/
function sendAdminNotification()
{
	$request = [
		'response_type'	=> 'ephemeral',
		'text'					=> 'Request received.',
		'attachments'		=> [
			[
				'text'	=> implode( '\n', $requestDetails )
			]
		]
	];
	transmitRestRequest( $data );
}

/**
	* A generic method which transmits a RESTful request.
	*
	* @param	array		The request's payload (e.g. message body and metadata)
	*
	* @return	object	A formal representation of the response.
	*/
function transmitRestRequest( $data )
{

}

/**
	* Retrieves a weather report for a specified location.
	*
	* NOTE: Request may contain ONLY ONE valid data block per request. (In other words: requests are invalid whenever they ask for two or more data blocks.)
	*/
function requestWeather( $requestParams )
{

	$urlSegments = [
		DARKSKY_API_SECRET,
		$requestParams[ 'latitude' ] . ',' . $requestParams[ 'longitude' ],
	];

	$queryParams	= [
		'exclude'	=> implode( ',', array_diff( DarkskyApi::VALID_DATA_BLOCKS, (array) $requestParams[ 'forecastType' ] ) ),
		'units'		=> 'auto',
		// 'extend'	=> 'hourly',
		// 'lang'		=> 'en', // default is imperial units (`us`)
	];

	$url = DarkskyApi::makeEndpointUrl( DEFAULT_ENDPOINT_NAME, $urlSegments, $queryParams );

	$response = file_get_contents( $url );

	return json_decode( $response );
}
