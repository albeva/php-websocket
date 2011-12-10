<?php
/**
 * WebSocket PHP library
 *
 * This file implements INI format serialalizer.
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
 * Ini serializer
 *
 * TODO implement ini serializer
 */
class Ini implements Serializer
{
	/**
	 * Serialize data
	 *
	 * @param mixed $data
	 * @return string
	 */
	function serialize($data)
	{
		// generate ini
	}


	/**
	 * Unserialize data
	 *
	 * @param string $data
	 * @return mixed
	 */
	function unserialize($data)
	{
		// read ini
	}
}
