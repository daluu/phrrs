<?php

namespace PhpRobotRemoteServer;

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class RobotRemoteServer {

	private $keywordStore;
	private $server;

	public function start($keywordsDirectory) {
		$this->keywordStore = new KeywordStore();
		$this->keywordStore->collectKeywords($keywordsDirectory);
		$this->server = RobotRemoteProtocol::getInstance();
		$this->server->init($this->keywordStore);
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
