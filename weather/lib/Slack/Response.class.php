<?php

namespace AldenG\SlackSdk;

require_once __DIR__ . '/ResponseAttachment.class.php';

/**
	* Slack Response
	*/
class Response {

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

	function addAttachment( ResponseAttachment $attachment )
	{
		$this->attachments[] = $attachment;
	}
}
