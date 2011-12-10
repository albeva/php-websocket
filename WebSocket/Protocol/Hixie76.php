<?php
/**
 * WebSocket PHP library
 *
 * This file implements the hixie-76 WebSocket prtocol draft.
 *
 * @link http://tools.ietf.org/html/draft-hixie-thewebsocketprotocol-76
 *
 * Browsers:
 * - Chrome 6
 * - Safari 5.0.1
 * - FireFox 4.0 (disabled)
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
 * Handle Hixi-76 protocol
 */
class Hixie76 extends Protocol
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
		if ($this->validate($http)) {
			return array(
				'Host'   => $http['headers']['Host'],
				'Origin' => $http['headers']['Origin']
			);
		}
		return false;
	}


	/**
	 * Validate the request
	 *
	 * @param array $http
	 * @return bool
	 */
	function validate(array $http)
	{
		$headers = $http['headers'];
		$body    = $http['body'];
		return isset($headers['Sec-Websocket-Key1'])
			&& isset($headers['Sec-Websocket-Key2'])
			&& isset($headers['Origin'])
			&& strlen($body) == 8;
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
		$headers  = $http['headers'];
		$body     = $http['body'];
		$request  = $http['request'];
		$origin   = $headers['Origin'];
		$host     = $headers['Host'];
		$key1     = $headers['Sec-Websocket-Key1'];
		$key2     = $headers['Sec-Websocket-Key2'];
		$protocol = isset($headers['Sec-WebSocket-Protocol'])
			? $headers['Sec-WebSocket-Protocol']
			: '';

		// HTTP response
		$reply = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n"
			. "Upgrade: WebSocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Origin: $origin\r\n"
			. "Sec-WebSocket-Location: ws://$host$request\r\n"
			. ($protocol ? "Sec-WebSocket-Protocol: $protocol\r\n" : '')
			. "\r\n"
			. $this->generateHash($key1, $key2, $body)
			. "\0";

		// send
		$bytes = socket_write($socket, $reply);
		if ($bytes === null) {
			$this->server->logSocketError();
			return false;
		}

		// done
		return true;
	}


	/**
	 * Generate handshake hash
	 *
	 * @param string $key1
	 * @param string $key2
	 * @param string $code
	 *
	 * @return string
	 */
	function generateHash($key1, $key2, $code)
	{
		return md5(
			pack('N', $this->calculate($key1)) .
				pack('N', $this->calculate($key2)) .
				$code,
			true
		);
	}


	/**
	 * Calculate
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	function calculate($key)
	{
		return preg_match_all('/[0-9]/', $key, $number)
			&& preg_match_all('/ /', $key, $space)
			? implode('', $number[0]) / count($space[0])
			: '';
	}


	/**
	 * Disconnect from the socket
	 *
	 * @param resource $socket
	 */
	function disconnect($socket)
	{
		socket_write($socket, pack('C', 0xFF));
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
		return substr($data, 1, strlen($data) - 2);
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
		return $data . chr(255) . chr(0);
	}

}
