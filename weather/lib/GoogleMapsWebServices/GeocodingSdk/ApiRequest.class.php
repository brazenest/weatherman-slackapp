<?php
namespace AldenG\GoogleMapsWebServices\GeocodingSdk;

class ApiRequest {

	const VALID_REQUEST_TYPES = [
		'address',
		'components',
	];

	const VALID_PARAMS = [
		'address',
		'components',
		'bounds',
		'language',
		'region',
	];

	const ENDPOINT_BASE = 'https://maps.googleapis.com/maps/api/geocode';

	private $apiSecret, $outputFormat, $requestType, $parameters;

	public function __construct( string $apiSecret, string $requestType = 'address' )
	{
		if( !in_array( $requestType, self::VALID_REQUEST_TYPES ) )
		{
			throw new \Exception( 'Invalid request type.' );
		}
		$this->apiSecret		= $apiSecret;
		$this->requestType	= $requestType;
		$this->outputFormat	= 'json';
	}

	/**
		* Sets a value of a valid parameter.
		* NOTE: Currently, we don't strict-check the setting of the `address` or `components` parameters. Take care to ensure that one or the other is always set, and that `address` is omitted whenever one wishes to perform a components-based query! Otherwise, you WILL receive a garbage result.
		*
		* @param	string	$paramName	The name of the valid, optional parameter.
		* @param	string	$paramValue	The corresponding value for the parameter.
		*
		* @return	true|\Exception	A thrown exception in the case of failure. Otherwise, return `true`.
		*/
	public function setParam( string $paramName, string $paramValue )
	{
		if( ! self::isValidParam( $paramName ) )
		{
			throw new \Exception( '`' . $paramName . '` is not a valid parameter name.' );
		}

		$this->parameters[ $paramName ] = $paramValue;

		return true;
	}

	public static function isValidParam( string $paramName )
	{
		return in_array( $paramName, self::VALID_PARAMS );
	}

	public function makeEndpointUrl()
	{
		$urlParams = [];

		return self::ENDPOINT_BASE . '/' . $this->outputFormat . '?key=' . $this->apiSecret . '&' . http_build_query( array_filter( $this->parameters ) );
	}
}
