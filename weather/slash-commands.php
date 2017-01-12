<?php
/**
	* Weather God -- A weather app for Slack
	*
	* @copyright 2016 Alden Gillespy
	* @license Proprietary. All rights reserved.
	* @author Alden Gillespy
	* @version 1.3.0
	* @since 1.0
	* @package AldenG_Slackapps
	*/
namespace AldenG\Slackapps\Weather;


require_once __DIR__ . '/lib/Slack/CommandRequest.class.php';
require_once __DIR__ . '/lib/Slack/CommandResponse.class.php';
require_once __DIR__ . '/lib/Slack/ResponseAttachment.class.php';
require_once __DIR__ . '/lib/Slack/Exceptions/InvalidCommandConfig.ex.php';
require_once __DIR__ . '/lib/Slack/Exceptions/InvalidSlackToken.ex.php';

use AldenG\SlackSdk\CommandRequest as SlashCommandRequest;
use AldenG\SlackSdk\CommandResponse as SlackResponse;
use AldenG\SlackSdk\ResponseAttachment as SlackResponseAttachment;

use AldenG\SlackSdk\Exceptions\InvalidCommandConfig as InvalidCommandConfig;
use AldenG\SlackSdk\Exceptions\InvalidSlackToken as InvalidSlackToken;

// App descriptors:
define( 'APP_VERSION_NUMBER',	'1.3.0' );
define( 'APP_VERSION_DATE',		'2016-12-26' );

// Requirements:
define( 'SLACK_COMMAND_TOKEN', 				'GPJWCJYsbHH06DpoLwbLVsBy' );
define( 'DARKSKY_API_SECRET',					'b6876b3993226c84627ba2a331ed697b' );
define( 'GEOCODING_API_SECRET',				'AIzaSyBQM7dPovqPEwOg1-rVy9Xv1uOqADnop1U' );
define( 'ADMINS', [
	'U0VTT5EHL', // @alden
]);
define( 'TEAMS', [
	'T02996B3M' // concepta
]);


define( 'REQUEST_TYPE', '_POST');

$ARGS =& ${REQUEST_TYPE};
/*
	Begin the script.
*/

;

try {
	$request = new SlashCommandRequest( $ARGS );
	$response = $request->process();
}
catch ( InvalidCommandConfig $ex )
{
	$response	= new SlackResponse( "*Oh no!* :hushed: I couldn't understand you." );

	$attachment	= new SlackResponseAttachment( '_Try `/weather help`_', 'Need some help? :persevere:' );
	$attachment->setColor( 'warning' );

	$response->addAttachment( $attachment );

	// add Debug Info to requests made by administrator users.

	if( in_array( $ARGS['team_id'], TEAMS ) && in_array( $ARGS['user_id'], ADMINS ) ) {
		$debug_attachment = new SlackResponseAttachment( "_Here are some of the details..._ :sun_behind_cloud:", "Debug Info" );
		$debug_attachment->addFooter( "You're seeing this info because I recognize you as an administrator. Please notify <@".ADMINS[0]."> if I'm mistaken!" );
		$debug_attachment->setColor( '#183691' ); // dark blue
		//$debug_attachment->setColor( '#795da3' ); // light purple
		$debug_attachment->enableMarkdownInProperties([
			'fields',
		]);
		$debug_attachment->addField( "```{$ex->getMessage()}```", 'Error message from PHP' );
		foreach ( $ARGS as $param_name => $param_value ) {
			$debug_attachment->addField( $param_value, $param_name, true );
		}
		$response->addAttachment( $debug_attachment );
	}
}
catch ( InvalidSlackToken $ex )
{
	http_response_code(403);
	exit;
}


// sendAdminNotification();

render_response( $response );

/**
* Renders an HTTP response containing a Slack-interprerable JSON body.
*
* @param SlackResponse
*/
function render_response( SlackResponse $response ) {
	header( 'Content-Type: application/json' );
	http_response_code(200);
	echo json_encode( $response );
	exit;
}

die;


/**
	* Sends a REST message to Slack, to report a use of the `/weather` slash command.
	*
	* @param	array		The message's metadata and body
	*
	* @return	boolean	An indication of Slack's successful (or failed) receipt of the notification request.
	*/
function sendAdminNotification()
{
	$request = [
		'response_type'	=> 'ephemeral',
		'text'					=> 'Request received.',
		'attachments'		=> [
			[
				'text'	=> implode( '\n', $requestDetails )
			]
		]
	];
	transmitRestRequest( $data );
}
