<?php
/**
 * WebSocket PHP library
 *
 * This file implements JSON format serialalizer.
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
 * Json serializer
 *
 * Useful for communicating with the browsers
 */
class Json implements Serializer
{

	/**
	 * Serialize data
	 *
	 * @param mixed $data
	 * @return string
	 */
	function serialize($data)
	{
		return json_encode($data);
	}


	/**
	 * Unserialize data
	 *
	 * @param string $data
	 * @return mixed
	 */
	function unserialize($data)
	{
		return json_decode($data, true);
	}
}
