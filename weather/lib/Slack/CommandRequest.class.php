<?php
namespace AldenG\SlackSdk;

require_once dirname( __FILE__ ) . '/CommandResponse.class.php';
require_once dirname( __FILE__ ) . '/Exceptions/InvalidCommandConfig.ex.php';
require_once dirname( __FILE__ ) . '/Exceptions/InvalidSlackToken.ex.php';

use AldenG\SlackSdk\Exceptions\InvalidCommandConfig as InvalidCommandConfig;
use AldenG\SlackSdk\Exceptions\InvalidSlackToken as InvalidSlackToken;

class CommandRequest {

	protected $token;
	protected $team_id;
	protected $team_domain;
	protected $channel_id;
	protected $channel_name;
	protected $user_id;
	protected $user_name;
	protected $command;
	protected $text;
	protected $response_url;

	function __construct( array $request_data ) {

		$this->setValues( $request_data );
		$this->verifyToken();

		if ( false === $this->validateCommandName() ) {
			throw new InvalidCommandConfig( 'The request specified an invalid command.' );
		}
	}

	protected function setValues( array $data ) {

		foreach ( $data as $param_name => $value ) {
			if( property_exists( $this, $param_name ) ) {
				// switch ( $param_name ) {
				// 	case 'command':
				// 	$value = substr( $value, 1 );
				// }
				$this->{$param_name} = $value;
			}
		}
	}

	protected function getValues() : array {

		return array_filter([
			'text' => $this->text,
			'response_url' => $this->response_url,
		]);
	}

	public function process() : CommandResponse {

		$command_request_handler_class_name	= "\\" . __NAMESPACE__ . "\\" . $this->getRequestHandlerClassName();
		require_once $this->getRequestHandlerPath();
		$command_request_handler = new $command_request_handler_class_name( $this->getValues() );

		return $command_request_handler->getCommandResponse();
	}

	protected function verifyToken() {

		if( SLACK_COMMAND_TOKEN !== $this->token ) {
			throw new InvalidSlackToken( 'The command was issued with an invalid token value.' );
		}
	}

	protected function validateCommandName() : bool {

		return file_exists( $this->getRequestHandlerPath() );
	}

	protected function getCommandName() : string {

		return substr( $this->command, 1 );
	}

	protected function getRequestHandlerClassName() : string {

		return ucwords( $this->getCommandName() ) . "CommandRequestHandler";
	}

	protected function getRequestHandlerFilename() : string {

		return $this->getRequestHandlerClassName() . ".class.php";
	}

	protected function getRequestHandlerPath() : string {

		return __DIR__ . "/" . $this->getRequestHandlerFilename();
	}
}
