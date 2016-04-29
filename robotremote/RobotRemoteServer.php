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
		$requests = new DemoRequests();
		$responses = new DemoResponses();
		$this->start($requests, $responses);
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

class DemoRequests {

	private $requests = array(
		'<?xml version="1.0"?>
		<methodCall>
		   <methodName>get_keyword_names</methodName>
		   <params>
		      </params>
		   </methodCall>',
		'<?xml version="1.0"?>
		<methodCall>
		   <methodName>stop_remote_server</methodName>
		   <params>
		      </params>
		   </methodCall>'
	);
	private $requestsIdx = 0;

	public function get() {
		$request = $this->requests[$this->requestsIdx++];
		echo('Request: '.$request."\n\n");
		return $request;
	}

}

class DemoResponses {

	public function add($response) {
		echo('Response: '.$response."\n\n");
	}

}
