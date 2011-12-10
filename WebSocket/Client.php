<?php
/**
 * WebSocket PHP library
 *
 * This file implements the Client class that represents a connected
 * endpoint to the running server.
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
 * Base class for the application client
 *
 * This class has one abstract method: receive. It is only method that
 * has to be provided in order to make use of this library. Receive method
 * deals with incoming messages.
 */
abstract class Client
{
	/**
	 * Unique numeric ID of the connected client (socket number)
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Client socket object
	 *
	 * @var resource
	 */
	protected $socket;

	/**
	 * Server instance that owns this client
	 *
	 * @var Server
	 */
	protected $server;

	/**
	 * Protocol object used for encoding / decoding communication
	 * with the client.
	 *
	 * @var Protocol
	 */
	protected $protocol;

	/**
	 * Serializer object for automatic data serialization an deserialization.
	 *
	 * @var Serializer
	 */
	protected $serializer;

	/**
	 * Is this client connected to the endpoint?
	 *
	 * @var bool
	 */
	protected $connected = false;


	/**
	 * Create new instance of the client.
	 *
	 * @param int		$id
	 * @param resource   $socket
	 * @param Server	 $server
	 * @param Protocol   $protocol
	 * @param Serializer $serializer
	 */
	final function __construct($id, $socket, Server $server, Protocol $protocol, Serializer $serializer = null)
	{
		// set properties
		$this->id         = $id;
		$this->socket     = $socket;
		$this->server     = $server;
		$this->protocol   = $protocol;
		$this->serializer = $serializer;
		$this->init();
	}


	/**
	 * Get client dd
	 *
	 * @see $id
	 * @return int
	 */
	function getId()
	{
		return $this->id;
	}


	/**
	 * Get connection socket
	 *
	 * @see $socket
	 * @return resource
	 */
	function getSocket()
	{
		return $this->socket;
	}


	/**
	 * Get server
	 *
	 * @see $server
	 * @return Server
	 */
	function getServer()
	{
		return $this->server;
	}


	/**
	 * Get protocol
	 *
	 * @see $protocol
	 * @return Protocol
	 */
	function getProtocol()
	{
		return $this->protocol;
	}


	/**
	 * Get serializer
	 *
	 * @see $serializer
	 * @return Serializer
	 */
	function getSerializer()
	{
		return $this->serializer;
	}


	/**
	 * Is client connected?
	 *
	 * @see $connected
	 * @return bool
	 */
	function isConnected()
	{
		return $this->connected;
	}


	/**
	 * Set client connection status
	 *
	 * @see $connected
	 * @param bool $connected
	 */
	function setConnected($connected)
	{
		$this->connected = $connected;
	}


	/**
	 * Init is called after object is successfully created from within the
	 * constructor. Use init() to do any additional setup because constructor
	 * is declared final
	 */
	function init()
	{
	}


	/**
	 * This method is called when client is attempting to connect. This method
	 * is called prior to handshaking. Returning false will disconnect this
	 * client. "onDisconnected" will NOT be called if this method returns false.
	 * It is not possible to send messages to the client at this point however
	 * it can broadcast messages to other connected clients.
	 *
	 * @param array  $http containing http information: headers, params, body
	 * @param string $host
	 * @param string $origin
	 * @return bool
	 */
	function onConnect(array $http, $host, $origin)
	{
		return true;
	}


	/**
	 * This method is called when connection is fully established with the
	 * client and can send messages.
	 */
	function onConnected()
	{
	}


	/**
	 * This method is called after client disconnects. Either from client or
	 * server side. No messages can be sent to this client anymore, but can
	 * broadcast to others.
	 *
	 * @return void
	 */
	function onDisconnected()
	{
	}


	/**
	 * This method is called when a message is received from the client and
	 * must be implemented by the extending class to handle the message.
	 * If serialisation is enabled then message will be unserialised prior
	 * to this call automatically,
	 *
	 * @param mixed $message
	 */
	abstract function receive($message);


	/**
	 * Disconnect this client
	 */
	function disconnect()
	{
		if (!$this->connected) return;
		$this->server->disconnect($this);
	}


	/**
	 * Send message to this client
	 * if serialisation is enabled then message will be serialised prior
	 * to sending
	 *
	 * @param mixed $message
	 * @return bool true on success
	 */
	function send($message)
	{
		return $this->connected && $this->server->send($this, $message);
	}


	/**
	 * Send message to all clients
	 * if serialisation is enabled then message will be serialised prior
	 * to sending!
	 *
	 * @param mixed		$message to send
	 * @param array|Client $exclude clients to exclude from the list
	 * @return bool true on success
	 */
	function sendAll($message, $exclude = null)
	{
		// turn into an array
		if ($exclude instanceof Client) $exclude = array($exclude);

		// include self in exclude list if not yet connected.
		if (!$this->connected) {
			if (!is_array($exclude)) $exclude = array($this);
			else if (!in_array($this, $exclude, true)) $exclude[] = $this;
		}

		// send
		return $this->server->sendAll($message, $exclude);
	}
}
