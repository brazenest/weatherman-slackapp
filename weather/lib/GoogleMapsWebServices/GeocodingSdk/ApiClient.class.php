<?php

namespace AldenG\GoogleMapsWebServices\GeocodingSdk;

require_once __DIR__ . '/ApiRequest.class.php';

class ApiClient {

	private $apiSecret;

	public function __construct( string $apiSecret )
	{
		$this->apiSecret = $apiSecret;
	}

	public function locateByZipCode( int $zipcode )
	{
		$response = $this->request( 'components', [
			'components'	=> 'postal_code:' . $zipcode,
		]);

		return [
			'latitude'	=> round( (float) $response->results[0]->geometry->location->lat, 3 ),
			'longitude'	=> round( (float) $response->results[0]->geometry->location->lng, 3 ),
			'location'	=> $response->results[0]->formatted_address,
		];
	}

	public function locate( string $address )
	{
		$response = $this->request( 'address', [
			'address'	=> $address,
		]);

		return [
			'latitude'	=> round( (float) $response->results[0]->geometry->location->lat, 3 ),
			'longitude'	=> round( (float) $response->results[0]->geometry->location->lng, 3 ),
			'location'	=> $response->results[0]->formatted_address,
		];
	}
	
	private function request( string $requestType, array $params )
	{
		$request = new ApiRequest( $this->apiSecret, $requestType );
		foreach( $params as $paramName => $paramValue )
		{
			try {
				$request->setParam( $paramName, $paramValue );
			}
			catch( \Exception $e )
			{
				// do nothing for now. we're just ignoring invalid param settings and letting Google act accordingly.
			}

		}
		$url = $request->makeEndpointUrl();
		$response = file_get_contents( $url );

		return json_decode( $response );
	}

}
