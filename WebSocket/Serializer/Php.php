<?php
/**
 * WebSocket PHP library
 *
 * This file implements the native PHP serializer
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
namespace WebSocket\Serializer;

use \WebSocket\Serializer as Serializer;

/**
 * Native PHP serializer
 *
 * Useful for communicating with another PHP client
 */
class Php implements Serializer
{
	/**
	 * Serialize data
	 *
	 * @param mixed $data
	 * @return string
	 */
	function serialize($data)
	{
		return serialize($data);
	}


	/**
	 * Unserialize data
	 *
	 * @param string $data
	 * @return mixed
	 */
	function unserialize($data)
	{
		return unserialize($data);
	}
}
