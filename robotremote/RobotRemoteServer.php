<?php

namespace PhpRobotRemoteServer;

class RobotRemoteServer {

	private $protocol;
	private $stopped = FALSE;

	public function init($protocol) {
		$this->protocol = $protocol;
		$this->protocol->setRobotRemoteServer($this);
	}

	public function startOnPort($serverPort) {
		$socketInterface = new SocketInterface();
		$socketInterface->startSession($serverPort);

		$this->start($socketInterface, $socketInterface);

		$socketInterface->cleanUp();
	}

	public function start($requests, $responses) {
		while (!$this->stopped) {
			$request = $requests->get();
			$response = $this->execRequest($request);
			$responses->add($response);
		}
	}

	function execRequest($request) {
		$result = $this->protocol->exec($request);
		return $result;
	}

	public function stop() {
		$this->stopped = TRUE;
	}

	public function isStopped() {
		return $this->stopped;
	}

}

class SocketInterface {

	private $serverSocket;
	private $requestSocket;

    public function startSession($serverPort) {
		$serverAddress = 'localhost:'.$serverPort;
		echo('Starting server on '.$serverAddress.'...');

		$errno = 0;
		$errstr = '';

		$this->serverSocket = stream_socket_server(
			'tcp://'.$serverAddress, $errno, $errstr);
		if (!$this->serverSocket) {
			die('Unable to open socket on '.$serverAddress
				.' - Error: '.$errno.' / '.$errstr);
		}
    }

    private function acceptConnection() {
		$requestSocket = stream_socket_accept($this->serverSocket, -1);
		if (!$requestSocket) {
			die('Unable to accept connection');
		}

		$blockingOk = stream_set_blocking($requestSocket, TRUE);
		if (!$blockingOk) {
			die('Unable to set socket as blocking');
		}

		return $requestSocket;
    }

	public function get() {
		$this->requestSocket = $this->acceptConnection();

		// search for the Content-Length line
		$contentLength = FALSE;
		while (true) {
			$line = stream_get_line($this->requestSocket, 1500, "\r\n");
			$parts = explode(': ', $line);
			if (strcasecmp('Content-Length', $parts[0]) == 0) {
				$contentLength = intval($parts[1]);
				break;
			}
		}

		// skip until we find an empty line, followed by the HTTP body
		while ($line != '') {
			$line = stream_get_line($this->requestSocket, 1500, "\r\n");
		}

		// and after the empty line here comes the HTTP body
		$httpBody = stream_get_contents($this->requestSocket, $contentLength);

		return $httpBody;
	}

	public function add($response) {
		$contentLength = strlen($response);
		$httpResponse = 'HTTP/1.0 200 OK
Date: '.(new \DateTime())->format(\DateTIme::RFC822).'
Server: PhpRobotRemoteServer
Connection: close
Content-Type: text/xml
Content-Length: '.$contentLength."\r\n"
		."\r\n"
		.$response;

		stream_socket_sendto($this->requestSocket, $httpResponse);

		stream_socket_shutdown($this->requestSocket, STREAM_SHUT_RDWR);
	}

	public function cleanUp() {
		stream_socket_shutdown($this->serverSocket, STREAM_SHUT_RDWR);
	}

}
