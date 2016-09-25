<?php

namespace PhpRobotRemoteServer;

class SocketInterface {

	private $serverSocket;
	private $requestSocket;

    public function startSession($serverPort, $verbose) {
		$serverAddress = 'localhost:'.$serverPort;
		if ($verbose) {
			echo('Starting server on '.$serverAddress.'...'."\n");
		}

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
Server: PHP Robot Framework Remote Server
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
