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
	 * connected clients
	 *
	 * @var array
	 */
	static $clients = array();

	/**
	 * Notify the client of its ID
	 */
	function onConnected()
	{
		$this->send(array(
			'action' => 'notify',
			'id' => $this->id,
			'clients' => array_values(self::$clients)
		));
	}


	/**
	 * Notify others
	 */
	function onDisconnected()
	{
		unset(self::$clients[$this->id]);
		$this->sendAll(array(
			'action' => 'disconnect',
			'id' => $this->id
		));
	}

	/**
	 * Receive message from the client
	 *
	 * @param array $msg
	 */
	function receive($msg)
	{
		$msg['id'] = $this->id;
		if (in_array($msg['action'], array('connect', 'update'))) {
			$copy = $msg;
			unset($copy['action']);
			self::$clients[$this->id] = $copy;
		}
		$this->sendAll($msg);
	}
}

// Create server
try {
	$server = new WebSocket\Server(array(
		'host'			=> '173.203.111.216',
		'port'			=> 12345,
		'clientClass'	=> 'Chatter',
		'serializer'	=> 'json'
	));
} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
}