<?php
namespace AldenG\SlackSdk;

require_once dirname( __FILE__ ) . '/Response.class.php';

use AldenG\SlackSdk\Response;

class CommandResponse extends Response {

	function __construct( $response_data = '' ){

		switch ( gettype( $response_data ) ) {
			case 'array':
			$message = $response_data['text'];
			$title = $response_data['title'];
			break;

			default:
			$message = (string) $response_data;
			$title = null;
			break;
		}

		parent::__construct( $message, $title );
	}
}
