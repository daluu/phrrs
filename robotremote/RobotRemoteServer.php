<?php

namespace PhpRobotRemoteServer;

use \PhpRobotRemoteServer\Keywords;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class RobotRemoteServer {

	private $keywords;
	private $server;

	public function start($keywordsDirectory) {
		$this->keywords = new Keywords();
		$this->keywords->collectKeywords($keywordsDirectory);
		$this->server = RobotRemoteProtocol::getInstance();
		$this->server->init($this->keywords);
		while (true) {
			$result = $this->server->exec('<?xml version="1.0"?>
			<methodCall>
			   <methodName>get_keyword_names</methodName>
			   <params>
			      </params>
			   </methodCall>');
			var_dump($result);
			die("Stopping: not yet an actual server\n");
		}
	}

}
