<?php
/**
 * WebSocket PHP library
 *
 * This file implements the Serializer interface. Serializers are used to
 * automatically encode and decode the messages
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
 * Serializer interface
 *
 * @package WebSocket
 */
interface Serializer
{
	/**
	 * Serialize data
	 *
	 * @param mixed $data
	 * @return string
	 */
	function serialize($data);


	/**
	 * Unserialize data
	 *
	 * @param string $data
	 * @return mixed
	 */
	function unserialize($data);
}