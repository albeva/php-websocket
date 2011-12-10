<?php
/**
 * WebSocket PHP library
 *
 * This file implements XML serializer
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
 * XML serializer
 *
 * TODO implement zml serializer
 */
class Xml implements Serializer
{
	/**
	 * Serialize data
	 *
	 * @param mixed $data
	 * @return string
	 */
	function serialize($data)
	{
		// generate xml
	}


	/**
	 * Unserialize data
	 *
	 * @param string $data
	 * @return mixed
	 */
	function unserialize($data)
	{
		// read xml and return SimpleXml ?
	}
}
