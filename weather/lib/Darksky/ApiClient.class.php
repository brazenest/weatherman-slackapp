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

}
