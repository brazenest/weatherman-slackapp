<?php

namespace AldenG\DarkskySdk;

/**
	* Dark Sky API Client
	*
	* The driver for RESTful activity with the Dark Sky API (formerly forecast.io)
	*/
class ApiClient {

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

		return \implode( '/', $urlArray ) . '?' . \http_build_query( $queryParams );
	}

	public static function convertDegreesToCompass( $deg )
	{
		\settype( $deg, 'float' );
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
