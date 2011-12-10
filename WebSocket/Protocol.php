<?php
/**
 * WebSocket PHP library
 *
 * This file implements the abstract class for protocols. Protocol is used
 * to communicate with the client depending on its version of WebSocket draft
 * used.
 *
 * @author    Albert Varaksin <albeva@me.com>
 * @version   0.1
 * @package   WebSocket
 * @copyright Copyright (c) 2011 Albert Varaksin
 * @license   http://albeva.github.com/websocket/LICENCE.txt New BSD License
 */

/**
 * @namespace
 */
namespace WebSocket;

/**
 * Protocol is base class for WebSocket protocols
 */
abstract class Protocol
{
	/**
	 * The owner server object
	 *
	 * @var Server
	 */
	protected $server;


	/**
	 * Instantiate protocol
	 *
	 * @param Server $server
	 */
	final function __construct(Server  $server)
	{
		$this->server = $server;
	}


	/**
	 * Get Server
	 *
	 * @return Server
	 */
	function getServer()
	{
		return $this->server;
	}


	/**
	 * Log information to console
	 *
	 * @param mixed $params ....
	 */
	function log()
	{
		call_user_func_array(array($this->server, 'log'), func_get_args());
	}


	/**
	 * print out hex string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function toHex($string)
	{
		$hex = '';
		for ($i = 0; $i < strlen($string); $i++) {
			$hex .= dechex(ord($string[$i])) . ' ';
		}
		return strtoupper(trim($hex));
	}


	/**
	 * Detect if this protocol can handle the incoming connection.
	 * If successful return following array:
	 *
	 * array (
	 *   'Host'   => 'the host',
	 *   'Origin' => 'the origin'
	 * )
	 *
	 * @param array $http request information
	 *
	 * @return bool|array
	 */
	abstract function detect(array $http);


	/**
	 * Perform handshake with the server
	 *
	 * @param resource $socket
	 * @param array    $http containing http request information
	 *
	 * @return bool
	 */
	abstract function handshake($socket, array $http);


	/**
	 * Disconnect from the socket
	 *
	 * @param resource $socket
	 */
	abstract function disconnect($socket);


	/**
	 * Decode data.
	 *
	 * Return false on decoding failure, but do not disconnect
	 * return null on critical packet error. This will disconnect
	 * return true if no errors and no content (control frames)
	 * return string containing the decoded content
	 *
	 * @param resource $socket
	 * @param string   $data
	 *
	 * @return string
	 */
	abstract function decode($socket, $data);


	/**
	 * Encode the data in the protocol specific format.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	abstract function encode($data);
}
