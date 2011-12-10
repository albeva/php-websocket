<?php
/**
 * WebSocket PHP library
 *
 * This file implements the hixie-75 WebSocket prtocol draft.
 *
 * @link http://tools.ietf.org/html/draft-hixie-thewebsocketprotocol-75
 *
 * Browsers:
 * - Chrome 4
 * - Safari 5.0.0
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
namespace WebSocket\Protocol;

use \Exception as Exception,
	\WebSocket\Protocol as Protocol;

/**
 * Handle Hixie75 75 protocol
 */
class Hixie75 extends Protocol
{

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
	function detect(array $http)
	{
		return false;
	}


	/**
	 * Perform handshake with the server
	 *
	 * @param resource $socket
	 * @param array    $http containing http request information
	 *
	 * @return bool
	 */
	function handshake($socket, array $http)
	{
		return false;
	}


	/**
	 * Disconnect from the socket
	 *
	 * @param resource $socket
	 */
	function disconnect($socket)
	{
	}


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
	function decode($socket, $data)
	{
		return "";
	}


	/**
	 * Encode the data in the protocol specific format.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	function encode($data)
	{
		return "";
	}
}