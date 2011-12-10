<?php
//------------------------------------------------------------------------------
// This is a sample chat application for PHP WebSocket library
// Author: Albert Varaksin
//------------------------------------------------------------------------------

// show all errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

// class autoloader (based on PSR-0.)
require_once '../../WebSocket/SplClassLoader.php';
$autoloader = new SplClassLoader(null, __DIR__ . '/../../');
$autoloader->register();

/**
 * Chatter class represents a connected
 * chat client
 */
class Chatter extends WebSocket\Client
{
	/**
	 * @var string containing the name of the connected user
	 */
	public $name = 'User';
	
	/**
	 * Receive message from the client
	 *
	 * @param array $msg
	 */
	function receive($msg)
	{
		if (isset($msg['name'])) {
			$this->name = $msg['name'];
			return;
		}

		// send to all except self
		$this->sendAll(array(
			'user'		=> $this->name,
			'id'		=> $this->getId(),
			'message'	=> $msg['message']
		));
	}
}

// Create server
try {
	$server = new WebSocket\Server(array(
		'host'			=> 'localhost',
		'port'			=> 12345,
		'clientClass'	=> 'Chatter',
		'serializer'	=> 'json'
	));
} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
}