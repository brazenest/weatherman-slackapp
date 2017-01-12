<?php
/**
	* Weather God -- A weather app for Slack
	*
	* Endpoint for handling Slack's Events API push notifications.
	*
	* @copyright 2016 Alden Gillespy
	* @license Proprietary. All rights reserved.
	* @author Alden Gillespy
	* @version 1.3.0
	* @since 1.0
	* @package AldenG_Slackapps
	*/
namespace AldenG\Slackapps\Weather;

require_once __DIR__ . '/lib/Slack/Response.class.php';

use AldenG\SlackSdk\Response as SlackResponse;
use AldenG\SlackSdk\ResponseAttachment as SlackResponseAttachment;

// App descriptors:
define( 'APP_VERSION_NUMBER',					'1.3.0' );
define( 'APP_VERSION_DATE',						'2016-12-26' );

// Requirements:
define( 'SLACK_VERIFICATION_TOKEN', 	'GOVlJ1oq7g70YUI8v31thfpB' );

// Defaults:

define( 'REQUEST_TYPE', '_POST');

$ARGS =& ${REQUEST_TYPE};
$BODY = json_decode( file_get_contents('php://input'), true );

/*
	Begin the script.
*/

if(
	! array_key_exists( 'token', $BODY )
	|| ( $BODY[ 'token' ] !== SLACK_VERIFICATION_TOKEN )
)
{
	http_response_code(403);
	exit;
}

switch( $BODY[ 'type' ] )
{
	case 'url_verification':

		$slackResponse		= [
			'challenge'	=> $BODY[ 'challenge' ],
		];
		$responseHeaders	= [
			'Content-Type: application/x-www-form-encoded',
		];
		break;

	default:

		$slackResponse		= [];
		$responseHeaders	= [];
}

foreach( $responseHeaders as $responseHeader )
{
	header( $responseHeader );
}

http_response_code(200);

echo json_encode( $slackResponse );
