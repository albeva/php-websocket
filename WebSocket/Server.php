<?php
/**
 * WebSocket PHP library
 *
 * This file implements the Server class that monitors, creates and binds the
 * socket, listens for incoming connections, messages and marshals them to the
 * Client object
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

use \Exception as Exception;

/**
 * WebSocket server
 *
 * This class monitors, creates and binds the
 * socket, listens for incoming connections, messages and marshals them to the
 * Client object
 */
class Server
{
	/**
	 * The host address to bind and listen connections from
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The port number to bind to
	 *
	 * @var int
	 */
	protected $port;

	/**
	 * Name of the Client class that is instantiated whenever a new connection
	 * comes in. This class must extend the Client class.
	 *
	 * @var string
	 */
	protected $clientClass;

	/**
	 * Array of connected clients
	 *
	 * @var Client[]
	 */
	protected $clients = array();

	/**
	 * The master socket resource that listends for new
	 * connections.
	 *
	 * @var resource
	 */
	protected $master;

	/**
	 * Socket connections for every client to listen
	 *
	 * @var resource[]
	 */
	protected $sockets = array();

	/**
	 * Time when connection was established
	 *
	 * @var double
	 */
	protected $established = 0.0;

	/**
	 * Available protocols
	 *
	 * @var Protocol[]
	 */
	protected $protocols = array();

	/**
	 * Default serializer
	 *
	 * @var Serializer
	 */
	protected $serializer;

	/**
	 * Available serializers
	 *
	 * @var Serializer[]
	 */
	protected $serializers = array();


	/**
	 * Create temporary file containing host and port
	 * information that server was started with at the given path
	 * false to not create
	 *
	 * @var bool|string
	 */
	protected $temporary = 'wshost.tmp';


	/**
	 * Create new WebSocket server instance.
	 *
	 * Configuration options:
	 * host         - host address to bind
	 * port         - port to listen on
	 * clientClass  - class name of the client
	 * serializer   - serializer short name (ini, json, php, xml) or a class
	 *                name or an instance of a class that implements the
	 *                Serializer interface.
	 * tick         - periodic ticker callback for clients.
	 *
	 * @param array $config
	 */
	function __construct(array $config)
	{
		// load the passed configuration
		$this->loadConfig($config);

		// load available protocols
		$this->loadProtocols();

		// create socket and bind the host and port
		$this->start();

		// the main loop
		$this->main();
	}


	/**
	 * Load configuration
	 *
	 * @see __construct
	 * @param array $config
	 */
	function loadConfig(array $config)
	{
		// check host
		if (!isset($config['host']) || !is_string($config['host']) || !strlen($config['host'])) {
			throw new Exception("No host provided");
		}
		$this->host = $config['host'];

		// check port
		if (!isset($config['port']) || !is_numeric($config['port']) || $config['port'] <= 0) {
			throw new Exception("No port provided");
		}
		$this->port = $config['port'];

		// check clientClass
		if (!isset($config['clientClass']) || !is_string($config['clientClass'])) {
			throw new Exception('No clientClass provided');
		}
		$clientClass = $config['clientClass'];

		// clientClass exists?
		if (!class_exists($clientClass)) {
			throw new Exception("Class $clientClass not found");
		}

		// clientClass extends WebSocket\Client ?
		if (!in_array('WebSocket\Client', class_parents($clientClass, false))) {
			throw new Exception("$clientClass does not extend WebSocker\\Client");
		}
		$this->clientClass = $clientClass;

		// temporary file name
		if (array_key_exists('tempoaray', $config)) {
			if (!$config['tempoaray']) $this->temporary = false;
			else $this->temporary = (string)$config['tempoaray'];
		}

		// resolve temporary path
		if ($this->temporary) {
			if ($this->temporary[0] == '/' || preg_match('/^[a-zA-Z]:\\\/', $this->temporary)) {
				$this->temporary = $this->temporary;
			} else {
				$this->temporary = getcwd() . '/' . $this->temporary;
			}
		}

		// serializer
		if (isset($config['serializer'])) {
			$type = $config['serializer'];
			if ($type instanceof Serializer) {
				$this->serializer = $type;
			} else {
				$serializer = $this->getSerializer($type);
				if ($serializer) {
					$this->serializer = $serializer;
				}
			}
		}
	}


	/**
	 * Establish socket and bind the host and port
	 */
	function start()
	{
		// create master socket
		$master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!is_resource($master)) {
			$this->throwSocketError();
		}

		// set options
		if (!socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)) {
			$this->throwSocketError();
		}

		// bind
		if (!socket_bind($master, $this->host, $this->port)) {
			$this->throwSocketError();
		}

		// listen for connections
		if (!socket_listen($master, SOMAXCONN)) {
			$this->throwSocketError();
		}

		// set vars
		$this->master                = $master;
		$this->sockets[(int)$master] = $master;
		$this->established           = microtime(true);

		// write temporary
		if ($this->temporary) {
			file_put_contents($this->temporary, "ws://{$this->host}:{$this->port}");
		}

		// log
		$this->log('Server started: ' . date('Y-m-d H:i:s'));
		$this->log('Master socket : ' . $master);
		$this->log('Listening on  : ' . "{$this->host}:{$this->port}");
	}


	/**
	 * Load available WebSocket protocols
	 */
	function loadProtocols()
	{
		$iter = new \GlobIterator(__DIR__ . '/Protocol/*.php');
		foreach ($iter as $info) {
			$name  = $info->getBasename('.php');
			$class = __NAMESPACE__ . "\\Protocol\\$name";
			if (!class_exists("$class")) {
				throw new Exception("Protocol class $class not found");
			}
			if (!in_array('WebSocket\Protocol', class_parents($class))) {
				throw new Exception("Protocol class $class does not implement WebSocket\\Protocol interface");
			}
			$this->protocols[$name] = new $class($this);
		}
	}


	/**
	 * Get serializer class
	 *
	 * @param string $type name of the serializer or a class name
	 * @return Serializer
	 */
	function getSerializer($type)
	{
		// already loaded ?
		if (isset($this->serializers[$type])) return $this->serializers[$type];

		// try to load serializer by short name
		$name = ucfirst(strtolower($type));
		if (file_exists(__DIR__ . "/Serializer/$name.php")) {
			$class = "WebSocket\Serializer\\$name";
		} else {
			$class = $type;
		}

		// check that class exists
		if (!class_exists($class)) {
			throw new \Exception("Serializer class $class not found");
		}
		// check that it implements the serializer interface
		if (!in_array('WebSocket\Serializer', class_implements($class, false))) {
			throw new \Exception("Serializer class $class does not implement WebSocket\Serializer");
		}
		// store
		return $this->serializers[$type] = new $class();
	}


	/**
	 * Get client by id
	 *
	 * @param int $id
	 * @return \WebSocket\Client
	 */
	function getClient($id)
	{
		if (isset($this->clients[$id])) {
			return $this->clients[$id];
		}
	}


	/**
	 * Get connected clients
	 *
	 * @return Client[]
	 */
	function getClients()
	{
		return $this->clients;
	}


	/**
	 * Get host address
	 *
	 * @return string
	 */
	function getHost()
	{
		return $this->host;
	}


	/**
	 * Get the port address
	 *
	 * @return int
	 */
	function getPort()
	{
		return $this->port;
	}


	/**
	 * Listen for incoming socket connections and handle them
	 *
	 * TODO add timer callbacks to clients (tick)
	 */
	function main()
	{
		// write and exception
		$w = null;
		$e = null;

		while (true) {
			$sockets = $this->sockets;
			$changed = socket_select($sockets, $w, $e, null);
			if ($changed === false) {
				$this->logSocketError();
			} else if ($changed > 0) {
				foreach ($sockets as $socket) {
					if ($this->master === $socket) {
						$this->accept($socket);
					} else {
						$this->process($socket);
					}
				}
			}
		}
	}


	/**
	 * Accept socket and try to connect to it
	 *
	 * @param resource $socket
	 */
	function accept($socket)
	{
		$new = socket_accept($socket);
		if (!is_resource($new)) {
			$this->logSocketError();
		} else {
			$id = (int)$new;
			$this->log("New client #$id connected");
			$this->sockets[$id] = $new;
		}
	}


	/**
	 * Remove socket from the sockets list and close it
	 *
	 * @param resource $socket
	 * @return void
	 */
	function close($socket)
	{
		$id = (int)$socket;
		socket_close($socket);
		unset($this->sockets[$id]);
		$this->log("Client #$id disconnected");
	}


	/**
	 * Process socket message
	 *
	 * @param resource $socket
	 */
	function process($socket)
	{
		$id     = (int)$socket;
		$buffer = "";
		$bytes  = socket_recv($socket, $buffer, 1048576, 0);

		if ($bytes === false) {
			$this->logSocketError();
			return;
		} else if ($bytes === 0) {
			if (isset($this->clients[$id])) {
				$this->disconnect($this->clients[$id]);
			} else {
				$this->close($socket);
			}
			return;
		}
		// has a client. process
		if (isset($this->clients[$id])) {
			// vars
			$client     = $this->clients[$id];
			$protocol   = $client->getProtocol();
			$serializer = $client->getSerializer();

			// decode data and check it
			$data = $protocol->decode($socket, $buffer);
			if ($data === true) {
				return;
			} else if ($data === false) {
				$this->log("Client #$id invalid data");
				return;
			} else if ($data === null) {
				$this->disconnect($client);
				return;
			}

			// process data
			$this->log("Client #$id >>> " . $data);
			if ($serializer) {
				$data = $serializer->unserialize($data);
			}
			// send to the client
			$client->receive($data);
			return;
		}

		// get request information
		$http = $this->parseHttp($buffer);

		// is proper WebSocket connection?
		if (!$this->validateHeaders($http['headers'])) {
			$this->error("Invalid connection request");
			$this->log($http);
			$this->close($socket);
			return;
		}

		// find protocol to handle the connection
		// if found create client class and return
		foreach ($this->protocols as $protocol) {
			if ($info = $protocol->detect($http)) {
				$class  = $this->clientClass;
				$client = new $class($id, $socket, $this, $protocol, $this->serializer);
				if ($client->onConnect($http, $info['Host'], $info['Origin'])) {
					if ($protocol->handshake($socket, $http)) {
						$this->clients[$id] = $client;
						$client->setConnected(true);
						$client->onConnected();
						return;
					} else {
						$protocol->disconnect($socket);
						$this->close($socket);
						$this->error("Handshake failed with " . get_class($protocol));
						$this->log($http);
						return;
					}
				} else {
					$protocol->disconnect($socket);
					$this->close($socket);
					return;
				}
			}
		}

		// no protocol found to handle the connection
		$this->close($socket);
		$this->error("No protocol found to handle the connection");
		$this->log($http);
	}


	/**
	 * Parse HTTP request message and return parsed data in an array
	 *
	 * @param string $content
	 * @return array
	 */
	function parseHttp($content)
	{
		// get headers
		$headers = array();
		$fields  = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $content));
		$match   = array();
		foreach ($fields as $field) {
			if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
				$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
				if (isset($headers[$match[1]])) {
					$headers[$match[1]] = array($headers[$match[1]], $match[2]);
				} else {
					$headers[$match[1]] = trim($match[2]);
				}
			}
		}

		// the request part
		$request = '';
		if (preg_match("/GET (.*) HTTP/", $content, $match)) {
			$request = $match[1];
		}

		// get GET params
		$params = array();
		if (preg_match('/\/?\?(.*)/', $request, $match)) {
			parse_str($match[1], $params);
		}

		// get body
		$body = "";
		if (preg_match('/\r\n(.*?)$/', $content, $match)) {
			$body = $match[1];
		}

		// result
		return array(
			'headers' => $headers,
			'params'  => $params,
			'request' => $request,
			'body'    => $body
		);
	}


	/**
	 * Validate WebSocket connection HTTP headers
	 *
	 * @param array $headers
	 * @return bool
	 */
	function validateHeaders(array $headers)
	{
		return isset($headers['Upgrade'])
			&& strtolower($headers['Upgrade']) == 'websocket'
			&& isset($headers['Connection'])
			&& in_array('Upgrade', array_map('trim', explode(',', $headers['Connection'])));
	}


	/**
	 * Sends a message to the client. Message will be serialized if the
	 * Client has a serializer object.
	 *
	 * @param Client $client
	 * @param mixed  $message to send
	 * @return bool
	 */
	function send(Client $client, $message)
	{
		$protocol   = $client->getProtocol();
		$serializer = $client->getSerializer();

		// process the message
		if ($serializer) {
			$data = $serializer->serialize($message);
		} else {
			$data = $message;
		}

		// encode
		$data = $protocol->encode($data);

		// send
		$this->log("Client #{$client->getId()} <<< " . $data);
		$result = socket_write($client->getSocket(), $data);

		// failed?
		if ($result === false) {
			$this->logSocketError();
			return false;
		}

		// done
		return true;
	}


	/**
	 * Send message to all clients.
	 *
	 * TODO optimize data serialization across
	 *
	 * @param string $message
	 * @param array  $exclude clients
	 * @return bool
	 */
	function sendAll($message, array $exclude = null)
	{
		$result = true;
		foreach ($this->clients as $client) {
			if (!is_null($exclude) && in_array($client, $exclude, true)) continue;
			if (!$this->send($client, $message)) $result = false;
		}
		return $result;
	}


	/**
	 * Disconnect client. This will call Clients onDisconnected
	 *
	 * @param Client $client
	 */
	function disconnect(Client $client)
	{
		unset($this->clients[$client->getId()]);
		$client->getProtocol()->disconnect($client->getSocket());
		$this->close($client->getSocket());
		$client->setConnected(false);
		$client->onDisconnected();
	}


	/**
	 * Simple logging
	 *
	 * @param mixed ...
	 */
	function log()
	{
		$args = func_get_args();
		foreach ($args as $arg) {
			if (is_scalar($arg)) echo $arg . PHP_EOL;
			else print_r($arg);
		}
	}


	/**
	 * Report an error
	 *
	 * @param $msg
	 */
	function error($msg)
	{
		$this->log("ERROR $msg");
	}


	/**
	 * Log socket error
	 */
	function logSocketError()
	{
		$this->error(socket_strerror(socket_last_error()));
	}


	/**
	 * throw socket error exception
	 *
	 * @throws Exception
	 */
	function throwSocketError()
	{
		$msg = socket_strerror(socket_last_error());
		throw new Exception($msg);
	}
}
