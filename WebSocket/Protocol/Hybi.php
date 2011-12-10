<?php
/**
 * WebSocket PHP library
 *
 * This file implements the HyBi WebSocket prtocol draft 10 and 17
 *
 * @link http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-10
 *
 * Browsers:
 * - IE 10 Developer Preview
 * - Firefox 7
 * - Chrome 14
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
 * Handle HyBi10 and HyBi17
 */
class HyBi extends Protocol
{

	/**
	 * The GUID magic string defined by the protocol specification
	 */
	protected static $guid = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";


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
			$headers = $http['headers'];
			return array(
				'Host'   => $headers['Host'],
				'Origin' => isset($headers['Sec-Websocket-Origin'])
					? $headers['Sec-Websocket-Origin']
					: $headers['Origin']
			);
		}
		return false;
	}


	/**
	 * Validate the request
	 *
	 * @param array $http
	 *
	 * @return bool
	 */
	protected function validate(array $http)
	{
		$headers = $http['headers'];
		return isset($headers['Sec-Websocket-Key'])
			&& (isset($headers['Sec-Websocket-Origin']) || isset($headers['Origin']))
			&& isset($headers['Sec-Websocket-Version'])
			&& ($headers['Sec-Websocket-Version'] == 8 || $headers['Sec-Websocket-Version'] == 13);
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
		$headers   = $http['headers'];
		$request   = $http['request'];
		$originKey = isset($headers['Sec-Websocket-Origin'])
			? 'Sec-Websocket-Origin'
			: 'Origin';
		$origin    = $headers[$originKey];
		$version   = $headers['Sec-Websocket-Version'];
		$key       = $headers['Sec-Websocket-Key'];
		$protocol  = isset($headers['Sec-WebSocket-Protocol'])
			? $headers['Sec-WebSocket-Protocol']
			: '';

		// generate the hash
		$hash = base64_encode(sha1($key . self::$guid, true));

		// response
		$reply = "HTTP/1.1 101 Switching Protocols\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Version: $version\r\n"
			. "$originKey: $origin\r\n"
			. ($protocol ? "Sec-WebSocket-Protocol: $protocol\r\n" : '')
			. "Sec-WebSocket-Accept: $hash\r\n"
			. "\r\n";

		// send 
		$bytes = socket_write($socket, $reply, strlen($reply));
		if ($bytes === null) {
			$this->server->logSocketError();
			return false;
		}

		// done
		return true;
	}


	/**
	 * Disconnect from the socket
	 *
	 * @param resource $socket
	 */
	function disconnect($socket)
	{
		$bytes = socket_write($socket, pack('C', 0x88));
		if ($bytes === false) {
			$this->server->logSocketError();
		}
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
		// extract info
		$byte1  = ord($data[0]);
		$byte2  = ord($data[1]);
		$final  = $byte1 & 0x80;
		$opcode = $byte1 & 0xF;
		$mask   = $byte2 & 0x80;
		$len    = $byte2 & 0x7F;

		// no support for multiframe
		if (!$final) {
			$this->log("No support for multi frame packets");
			return false;
		}

		// specification insists that all incoming packets
		// must be masked
		if (!$mask) {
			$this->log("Unmasked packet received");
			return null;
		}

		// invalid
		if ($opcode == 0) {
			$this->log("Invalid opcode 0x0");
			return false;
		}

		// disconnect
		if ($opcode == 0x8) {
			return null;
		}

		// get size and mask key offset
		$offset = 0;
		$size   = 0;
		if ($len == 126) {
			list($size) = array_values(unpack("n", substr($data, 2, 2)));
			$offset = 4;
		} else if ($len == 127) {
			list($size) = array_values(unpack("N", substr($data, 6, 4)));
			$offset = 10;
		} else {
			$size   = $len;
			$offset = 2;
		}
		$mask    = substr($data, $offset, 4);
		$content = substr($data, $offset + 4);

		// check size
		if ($size != strlen($content)) {
			$this->log("Invalid frame. Size mismatch between header and content");
			return false;
		}

		// unmask
		for ($i = 0; $i < strlen($content); $i++) {
			$content[$i] = $content[$i] ^ $mask[$i % 4];
		}

		// ping message
		if ($opcode == 0x9) {
			$content = $this->generate($content, 0xA);
			$bytes   = socket_write($socket, $content);
			if ($bytes === false) {
				$this->server->logSocketError();
			}
			return true;
		}

		// done
		return $content;
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
		return $this->generate($data, 0x1);
	}


	/**
	 * encode the content
	 *
	 * @param string $content
	 * @param int    $opcode
	 *
	 * @return string
	 */
	protected function generate($content, $opcode)
	{
		if (strlen($content) < 126) $len = strlen($content);
		else if (strlen($content) <= 0xFFFF) $len = 126;
		else									$len = 127;

		// begin
		$reply = pack('CC', 0x80 + ($opcode & 0xF), $len);

		// 16bit / 64bit
		if ($len == 126) {
			$reply .= pack('n', strlen($content));
		} else if ($len == 127) {
			$reply .= pack('NN', 0, strlen($content));
		}

		// done
		$reply .= $content;
		return $reply;
	}
}