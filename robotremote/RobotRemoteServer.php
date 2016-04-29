<?php

namespace PhpRobotRemoteServer;

class RobotRemoteServer {

	private $protocol;
	private $stopped = FALSE;

	public function init($protocol) {
		$this->protocol = $protocol;
		$this->protocol->setRobotRemoteServer($this);
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
		while (!$this->stopped) {
			// TODO implement server logic, feeding the streams from sockets
			$this->execRequest($inputStream, $outputStream);
		}
	}

	function execRequest($inputStream, $outputStream) {
		$request = stream_get_contents($inputStream);
		$result = $this->protocol->exec($request);
		fwrite($outputStream, $result);
	}

	public function stop() {
		$this->stopped = TRUE;
	}

	public function isStopped() {
		return $this->stopped;
	}

}
