<?php

namespace PhpRobotRemoteServer;

class RobotRemoteServer {

	private $protocol;

	public function init($protocol) {
		$this->protocol = $protocol;
	}

	// TODO use server port... and get data from there
	public function startOnPort($serverPort) {
		$inputStream = fopen('data://text/plain;base64,'
			. base64_encode('<?xml version="1.0"?>
		<methodCall>
		   <methodName>get_keyword_names</methodName>
		   <params>
		      </params>
		   </methodCall>'), 'r');
		$outputStream = fopen('php://stdout', 'w');
		$this->start($inputStream, $outputStream);
	}

	public function start($inputStream, $outputStream) {
		while (true) {
			// TODO implement server logic, feeding the streams from sockets
			$this->execRequest($inputStream, $outputStream);
			die("\nStopping: not yet an actual server\n");
		}
	}

	function execRequest($inputStream, $outputStream) {
		$request = stream_get_contents($inputStream);
		$result = $this->protocol->exec($request);
		fwrite($outputStream, $result);
	}

}
