<?php
namespace AldenG\Slackapps\Weather;

require_once __DIR__ . '/lib/Darksky/ApiClient.class.php';
require_once __DIR__ . '/lib/Slack/Response.class.php';

use AldenG\DarkskySdk\ApiClient as DarkskyApi;
use AldenG\SlackSdk\Response as SlackResponse;
use AldenG\SlackSdk\ResponseAttachment as SlackResponseAttachment;

// Requirements:
define( 'SLACK_COMMAND_TOKEN', 				'GPJWCJYsbHH06DpoLwbLVsBy' );
define( 'DARKSKY_API_SECRET',					'b6876b3993226c84627ba2a331ed697b' );

// Defaults:
define( 'DEFAULT_ENDPOINT_NAME',			'forecast' );
define( 'DEFAULT_FORECAST_TYPE',			'currently' );
define( 'DEFAULT_LOCATION_LATITUDE', 	28.480 );
define( 'DEFAULT_LOCATION_LONGITUDE',	-81.455 );

/*
	Begin the script.
*/

if(
	! array_key_exists( 'token', $_POST )
	|| ( $_POST[ 'token' ] !== SLACK_COMMAND_TOKEN )
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

	$weatherData = requestWeather( $weatherRequestParams );

	$slackResponse = new SlackResponse( 'Current conditions ' . $weatherRequestParams[ 'location' ] );

	$weather = $weatherData->{DEFAULT_FORECAST_TYPE}; // i.e. `currently`
	$responseDetailsText = ( (int) $weather->temperature ) . '° ' . $weather->summary . " \n winds " . ( (int) $weather->windSpeed ) . ' mph from ' . DarkskyApi::convertDegreesToCompass( $weather->windBearing );
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
