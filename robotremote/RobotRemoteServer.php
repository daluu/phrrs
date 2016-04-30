<?php

namespace PhpRobotRemoteServer;

use \PhpRobotRemoteServer\SocketInterface;

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
