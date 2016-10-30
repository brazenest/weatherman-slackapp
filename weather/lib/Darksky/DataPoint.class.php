<?php
namespace AldenG\DarkskySdk;

class DataPoint {

	protected $cloudCover;
	protected $dewPoint;
	protected $humidity;
	protected $icon;
	protected $ozone;
	protected $precipIntensity;
	protected $precipProbability;
	protected $precipType;
	protected $pressure;
	protected $summary;
	protected $time;
	protected $visibility;
	protected $windBearing;
	protected $windSpeed;

	const ICON_EMOJI_CODES = [
		'clear-day'						=> ':sunny:',
		'clear-night'					=> ':full_moon:',
		'rain'								=> ':rain_cloud:',
		'snow'								=> ':snow_cloud:',
		'sleet'								=> ':snow_cloud:',
		'wind'								=> ':wind_blowing_face:',
		'fog'									=> ':fog:',
		'cloudy'							=> ':cloud:',
		'partly-cloudy-day'		=> ':partly_sunny:',
		'partly-cloudy-night'	=> ':cloud:',
		// 'hail',
		// 'thunderstorm',
		// 'tornado',
	];
	const PRECIP_TYPE_EMOJI_CODES = [
		'rain'	=> ':rain_cloud:',
		'snow'	=> ':snow_cloud:',
		'sleet'	=> ':snow_cloud:',
	];

	function __construct(array $params)
	{
		$this->initialize($params);
	}

	protected function initialize(array $params = [])
	{
		foreach($params as $paramName => $paramVal)
		{
			if( property_exists($this, $paramName) ) {
				$this->{$paramName} = $paramVal;
			}
		}
	}

	public function getParam(string $paramName)
	{
		if( !property_exists($this, $paramName) ) {
			throw new InvalidArgumentException( '`' . $paramName . '` is not a valid parameter name.' );
		}
		return $this->{$paramName};
	}

	public function getParams(array $excludedParams = [])
	{
		return array_diff_key( get_object_vars($this), array_flip($excludedParams) );
	}

	public function getIconEmojiCode()
	{
		return self::ICON_EMOJI_CODES[$this->icon];
	}

	public function getPrecipTypeEmojiCode()
	{
		if( ! isset($this->precipType) ) {
			throw new Exception( 'Cannot retrieve emoji code. There is no precipitation intensity for this data point.' );
		}
		return self::PRECIP_TYPE_EMOJI_CODES[$this->precipType];
	}

	public function getWindBearingDirection()
	{
		if( ! isset($this->windSpeed) || $this->windSpeed == 0 ) {
			throw new Exception( 'Wind speed is zero.' );
		}
		return self::convertDegreesToCompass($this->windBearing);
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
