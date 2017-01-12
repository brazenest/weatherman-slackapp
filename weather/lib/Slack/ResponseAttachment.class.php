<?php

namespace AldenG\SlackSdk;

/**
	* Slack Response Attachment
	*/
class ResponseAttachment {

	public $color, $title, $pretext, $text, $fields, $mrkdwn_in;

	function __construct( string $text, string $title = null )
	{
		$this->text		= $text;
		$this->title	= $title;
		$this->fields	= [];
		$this->mrkdwn_in	= [
			'text',
		];
	}

	function setColor( string $color ) {

		$this->color = $color;
	}

	function setPretext( string $pretext ) {

		$this->pretext = $pretext;
	}

	function getTitle()
	{
		return $this->title;
	}

	function getPretext() {

		return $this->pretext;
	}

	function getText()
	{
		return $this->text;
	}

	function enableMarkdownInProperty( string $field_name ) {

		if ( property_exists( $this, $field_name ) ) {
			$this->mrkdwn_in = array_unique( array_merge( $this->mrkdwn_in, [ $field_name ] ) );
			return true;
		} else {
			return false;
		}
	}

	function enableMarkdownInProperties( array $field_names, bool $return_responses = false ) {

		$responses = [];

		foreach ( $field_names as $field_name ) {
			$responses[$field_name] = $this->enableMarkdownInProperty( $field_name );
		}

		if ( $return_responses ) {
			return $responses;
		}
	}

	function disableMarkdownInProperty( string $field_name ) {

		$key = array_search( $field_name, $this->mrkdwn_in );

		if ( false !== $key ) {
			unset( $this->mrkdwn_in[$key] );
		}
	}

	function disableMarkdownInProperties( array $field_names ) {

		foreach ( $field_names as $field_name ) {
			$this->disableMarkdownInProperty( $field_name );
		}
	}

	function addField( string $value, string $title = '', bool $short = false ) {

		$this->fields[] = [
			'title'	=> $title,
			'value' => $value,
			'short' => $short,
		];
	}

	function addFields( array $fields ) {

		foreach ( $fields as $field_data ) {
			$this->addField( $field_data );
		}
	}

	function addFooter( string $footer ) {

		$this->footer = $footer;
	}

}
