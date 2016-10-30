<?php

namespace AldenG\SlackSdk;

/**
	* Slack Response Attachment
	*/
class ResponseAttachment {

	public $title, $text, $mrkdwn_in;

	function __construct( string $text, string $title = null )
	{
		$this->text		= $text;
		$this->title	= $title;
		$this->mrkdwn_in	= [
			'text',
		];
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
