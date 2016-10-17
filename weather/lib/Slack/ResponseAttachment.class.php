<?php

namespace AldenG\SlackSdk;

/**
	* Slack Response Attachment
	*/
class ResponseAttachment {

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
