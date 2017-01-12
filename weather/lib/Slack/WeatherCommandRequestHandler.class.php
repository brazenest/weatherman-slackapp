<?php
namespace AldenG\SlackSdk;

require_once __DIR__ . '/CommandResponse.class.php';

require_once __DIR__ . '/../Darksky/ApiClient.class.php';
require_once __DIR__ . '/../Darksky/DataPoint.class.php';
require_once __DIR__ . '/../GoogleMapsWebServices/GeocodingSdk/ApiClient.class.php';
require_once __DIR__ . '/../GoogleMapsWebServices/GeocodingSdk/Exceptions/InvalidZipcodeException.class.php';

use AldenG\DarkskySdk\ApiClient as DarkskyApi;
use AldenG\DarkskySdk\DataPoint as DarkskyDataPoint;
use AldenG\GoogleMapsWebServices\GeocodingSdk\ApiClient as GeocodingApi;
use AldenG\GoogleMapsWebServices\GeocodingSdk\Exceptions\InvalidZipcodeException as InvalidZipcodeException;

// Defaults:
define( 'DEFAULT_ENDPOINT_NAME',		'forecast' );
define( 'DEFAULT_FORECAST_TYPE',		'currently' );
define( 'DEFAULT_LOCATION_LATITUDE', 	28.480 );
define( 'DEFAULT_LOCATION_LONGITUDE',	-81.455 );

class WeatherCommandRequestHandler {

	private $request_data;

	public function __construct( array $request_data ) {

		$this->request_data = $request_data;
		$this->process();
	}

	protected function process() {

		require_once __DIR__ . '/ResponseAttachment.class.php';

		$weatherRequestParams = [
			'forecastType'	=> DEFAULT_FORECAST_TYPE,
			'latitude'		=> DEFAULT_LOCATION_LATITUDE,
			'longitude'		=> DEFAULT_LOCATION_LONGITUDE,
			'location'		=> 'at Concepta HQ',
		];

		// if the request has argument(s)...

		if( isset( $this->request_data[ 'text' ] ) && ! empty( trim( $this->request_data[ 'text' ] ) ) ) {

			$argString = trim( $this->request_data[ 'text' ] );
			$argSwitchEndPos = strpos($argString, ' ');

			if( 0 === strpos($argString, '-') )
			{
				if( false !== $argSwitchEndPos ) {
					$argSwitch = substr($argString, 1, $argSwitchEndPos-1 );
					$argString = substr($argString, $argSwitchEndPos+1 );
					//throw new \RuntimeException( "argSwitchEndPos = {$argSwitchEndPos}, argSwitch = {$argSwitch} , argString = {$argString}");
				} else {
					$argSwitch = substr($argString, 1);
					$argString = null;
				}

				switch( $argSwitch )
				{

					case '?':

						$this->command_response = new CommandResponse();
						$this->command_response->addAttachment( new ResponseAttachment("version " . APP_VERSION_NUMBER . " (" . APP_VERSION_DATE . ")\n© " . date('Y') . " Alden Gillespy", "About Weatherman") );
						return;

					case 'hourly':
					case 'daily':
					$weatherRequestParams['forecastType'] = $argSwitch;
					break;

				}
			}
//$argString = 'sao paulo';
			if( isset($argString) ) {

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

		$weatherData = $this->requestWeather( $weatherRequestParams );

		$this->command_response = new CommandResponse([
			'text' => $weatherRequestParams[ 'location' ],
		]);

		$weather = $weatherData->{$weatherRequestParams['forecastType']}; // i.e. `currently`

		$datetime = new \DateTime();
		$datetimezone = new \DateTimeZone( $weatherData->timezone );

		switch( $weatherRequestParams['forecastType'] ) {

			case 'currently':

			if( property_exists( $weatherData, 'alerts' ) ) {
				foreach( $weatherData->alerts as $alertData ) {
					$alert = new ResponseAttachment( $alertData->description, ":heavy_exclamation_mark: {$alertData->title}" );
					$alert->setColor( 'warning' );

					$alert->addFooter( "until " . date_format( $datetime->setTimestamp($alertData->expires)->setTimezone($datetimezone), 'g:ia' ) . " on " . date_format( $datetime, 'M jS' ) );
					$alert->enableMarkdownInProperty( 'title' );
					$this->command_response->addAttachment( $alert );
				}

			}
			$responseDetailsText = ( DarkskyDataPoint::ICON_EMOJI_CODES[$weather->icon] . "\x20\x20*" . (int) $weather->temperature ) . "°*\x20" . $weather->summary . "\n winds " . ( (int) $weather->windSpeed ) . ' mph from ' . DarkskyDataPoint::convertDegreesToCompass( $weather->windBearing );
			$attachment = new ResponseAttachment( $responseDetailsText, 'Current Conditions' );
			$datetime->setTimestamp( $weather->time );
			$datetime->setTimezone( $datetimezone );
			$attachment->addFooter( "at " . $datetime->format( 'g:ia' ) . " local time (" . $datetime->format( 'T' ) . ")" );
			$this->command_response->addAttachment( $attachment );
			break;

			case 'hourly':

			$responseDetailsText = DarkskyDataPoint::ICON_EMOJI_CODES[$weather->icon] . " {$weather->summary}";
			$attachment = new ResponseAttachment( $responseDetailsText, "Today's Hourly Forecast" );
			$attachment->enableMarkdownInProperty( 'fields' );
			$this->command_response->addAttachment( $attachment );
			$hourlyAttachments = [];
			for( $i = 0; $i < 5; $i++ ) {
				$hourlyData =& $weather->data[$i];
				$datetime->setTimestamp( $hourlyData->time );
				$datetime->setTimezone( $datetimezone );
				$hourlyAttachments[] = "*{$datetime->format( 'ga' )}*\t" . DarkskyDataPoint::ICON_EMOJI_CODES[$hourlyData->icon] . "\t" . (int) $hourlyData->temperature . "°\t" . $hourlyData->summary;
			}

			$datetime->setTimestamp( $weather->time );
			$datetime->setTimezone( $datetimezone );
			$hourlyAttachment = new ResponseAttachment( implode( "\n", $hourlyAttachments ) );
			$hourlyAttachment->addFooter( "in local time (" . $datetime->format( 'T' ) . ")" );
			$this->command_response->addAttachment( $hourlyAttachment );
			break;

			case 'daily':

			$responseDetailsText = DarkskyDataPoint::ICON_EMOJI_CODES[$weather->icon] . " {$weather->summary}";
			$attachment = new ResponseAttachment( $responseDetailsText, "This Week's Forecast" );
			$attachment->enableMarkdownInProperty( 'fields' );
			$this->command_response->addAttachment( $attachment );
			$dailyAttachments = [];
			for( $i = 0; $i < 7; $i++ ) {
				$dailyData =& $weather->data[$i];
				$datetime->setTimestamp( $dailyData->time );
				$datetime->setTimezone( $datetimezone );
				switch( $datetime->format('w') ) {
					case 3: // Wed
					$tabString = "\x20\x20\x20\x20\x20\x20";
					break;

					case 4: // Thu
					$tabString = "\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20";
					break;

					case 6: // Sat
					$tabString = "\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20";
					break;

					case 0: // Sun
					$tabString = "\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20";
					break;

					case 1: // Mon
					case 2: // Tue
					$tabString = "\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20";
					break;

					case 5: //  Fri
					$tabString = "\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20";
					break;
				}
				$dailyAttachments[] = "*{$datetime->format( 'l' )}* " . $tabString . DarkskyDataPoint::ICON_EMOJI_CODES[$dailyData->icon] . "\t" . (int) $dailyData->temperatureMax . '° / ' . (int) $dailyData->temperatureMin . "°\t{$dailyData->summary}";
			}
			$this->command_response->addAttachment( new ResponseAttachment( implode( "\n", $dailyAttachments ) ) );
			//$attachment->addFooter( "at " . $datetime->format( 'g:ia' ) . " local time (" . $datetime->format( 'T' ) . ")" );

			break;

		}
		// $this->command_response->addAttachment( new ResponseAttachment( "{$this->weather_request_url}", 'Reference URL' ) );


	}

	/**
		* Retrieves a weather report for a specified location.
		*
		* NOTE: Request may contain ONLY ONE valid data block per request. (In other words: requests are invalid whenever they ask for two or more data blocks.)
		*/
	protected function requestWeather( $requestParams )
	{

		$urlSegments = [
			DARKSKY_API_SECRET,
			$requestParams[ 'latitude' ] . ',' . $requestParams[ 'longitude' ],
		];

		$queryParams	= [
			'exclude'	=> implode( ',', array_diff( DarkskyApi::VALID_DATA_BLOCKS, [ $requestParams[ 'forecastType' ], 'alerts' ] ) ),
			'units'		=> 'us',
			// 'extend'	=> 'hourly',
			// 'lang'		=> 'en', // default is imperial units (`us`)
		];

		$url = DarkskyApi::makeEndpointUrl( DEFAULT_ENDPOINT_NAME, $urlSegments, $queryParams );
		$this->weather_request_url = $url;
		$response = file_get_contents( $url );

		return json_decode( $response );
	}

	public function getCommandResponse() : CommandResponse {

		//return new CommandResponse( "Ha! It works :) Values: " . var_export( $this->request_data, true ) );
		return $this->command_response;
	}
}
