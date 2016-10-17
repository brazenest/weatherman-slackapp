<?php
namespace AldenG\Slackapps\Weather;

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

processRequest();

/**
	* The main function.
	*/
function processRequest()
{

	// sendAdminNotification();

	$weatherRequestParams = [
		'forecastType'	=> DEFAULT_FORECAST_TYPE,
		'latitude'			=> DEFAULT_LOCATION_LATITUDE,
		'longitude'			=> DEFAULT_LOCATION_LONGITUDE,
	];

	$weatherData = requestWeather( $weatherRequestParams );

	$slackResponse = new SlackResponse( 'Current conditions at Concepta HQ:' );

	$weather = $weatherData->{DEFAULT_FORECAST_TYPE}; // i.e. `currently`
	$responseDetailsText = ( (int) $weather->temperature ) . '° ' . $weather->summary . " \n winds " . ( (int) $weather->windSpeed ) . ' mph from ' . DarkskyApi::convertDegreesToCompass( $weather->windBearing );
	$slackResponse->addAttachment( new SlackResponseAttachment( $responseDetailsText ) );

	header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' 200 OK' );
	header( 'Content-Type: application/json' );

	echo json_encode( $slackResponse );
}

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

/**
	* SlackResponse
	*/
class SlackResponse {

	public $response_type, $text, $attachments;

	const VALID_RESPONSE_TYPES = [
		'ephemeral',
		'in_channel'
	];
	const DEFAULT_RESPONSE_TYPE = self::VALID_RESPONSE_TYPES[0];

	function __construct( string $text = null )
	{
		$this->response_type	= self::DEFAULT_RESPONSE_TYPE;
		$this->text						= $text;
		$this->attachments		= [];
	}

	function setResponseType( $responseType )
	{
		if( ! in_array( $responseType, self::VALID_RESPONSE_TYPES ) )
		{
			throw new \Exception( 'Response type is invalid.' );
		}

		$this->response_type	= $responseType;
	}

	function setText( string $text )
	{
		$this->text = $text;
	}

	function addAttachment( SlackResponseAttachment $attachment )
	{
		$this->attachments[] = $attachment;
	}
}

/**
	* SlackResponseAttachment
	*/
class SlackResponseAttachment {

	public $title, $text;

	function __construct( string $text, string $title = null )
	{
		$this->text		= $text;
		$this->title	= $title;
	}

	function getTitle()
	{
		return $this->title;
	}

	function getText()
	{
		return $this->text;
	}

}

/**
	* DarkskyApi
	*
	* The driver for RESTful activity with the Dark Sky API (formerly forecast.io)
	*/
class DarkskyApi {

	const VALID_DATA_BLOCKS = [
		'currently',
		'minutely',
		'hourly',
		'daily',
		'alerts',
		'flags',
	];
	const ENDPOINT_BASE	= 'https://api.darksky.net';

	public static function makeEndpointUrl( string $endpointName, array $urlSegments, array $queryParams = [] )
	{
		$urlArray	= [
			self::ENDPOINT_BASE,
			$endpointName,
			implode( '/', $urlSegments ),
		];

		return implode( '/', $urlArray ) . '?' . http_build_query( $queryParams );
	}

	public static function convertDegreesToCompass( $deg )
	{
		settype( $deg, 'float' );
		$text = $deg;

		// begin with midpoint at due north. subsequent compass directions are found at successive 22.5-degree rotations.
		if( $deg < 11.25 || $deg >= 348.75 ) {
			$text = 'N';
		} elseif( $deg < 33.75 ) {
			$text = 'NNE';
		} elseif( $deg < 56.25 ) {
			$text = 'NE';
		} elseif( $deg < 78.75 ) {
			$text = 'ENE';
		} elseif( $deg < 101.25 ) {
			$text = 'E';
		} elseif( $deg < 123.75 ) {
			$text = 'ESE';
		} elseif( $deg < 146.25 ) {
			$text = 'SE';
		} elseif( $deg < 168.75 ) {
			$text = 'SSE';
		} elseif( $deg < 191.25 ) {
			$text = 'S';
		} elseif( $deg < 213.75 ) {
			$text = 'SSW';
		} elseif( $deg < 236.25 ) {
			$text = 'SW';
		} elseif( $deg < 258.75 ) {
			$text = 'WSW';
		} elseif( $deg < 281.25 ) {
			$text = 'W';
		} elseif( $deg < 303.75 ) {
			$text = 'WNW';
		} elseif( $deg < 326.25 ) {
			$text = 'NW';
		} elseif( $deg < 348.75 ) {
			$text = 'NNW';
		}

		return $text;
	}

}
