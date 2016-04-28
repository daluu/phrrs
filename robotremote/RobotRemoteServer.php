<?php

namespace PhpRobotRemoteServer;

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class RobotRemoteServer {

	private $keywordStore;
	private $server;

	public function init($keywordsDirectory) {
		$this->keywordStore = new KeywordStore();
		$this->keywordStore->collectKeywords($keywordsDirectory);
		$this->server = RobotRemoteProtocol::getInstance();
		$this->server->init($this->keywordStore);
	}

	public function start() {
		while (true) {
			// TODO implement server logic, feeding the streams from sockets
			$inputStream = fopen('data://text/plain;base64,'
				. base64_encode('<?xml version="1.0"?>
			<methodCall>
			   <methodName>get_keyword_names</methodName>
			   <params>
			      </params>
			   </methodCall>'), 'r');
			$outputStream = fopen('php://stdout', 'w');
			$this->execRequest($inputStream, $outputStream);
			die("\nStopping: not yet an actual server\n");
		}
	}

	function execRequest($inputStream, $outputStream) {
		$request = stream_get_contents($inputStream);
		$result = $this->server->exec($request);
		fwrite($outputStream, $result);
	}

}
