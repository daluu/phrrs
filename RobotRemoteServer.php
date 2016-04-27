<?php

namespace PhpRobotRemoteServer;

require './vendor/autoload.php';

require_once('Keywords.php');
require_once('RobotRemoteProtocol.php');

class RobotRemoteServer {

	private $keywords;
	private $server;

	public function start() {
		$this->keywords = new \PhpRobotRemoteServer\Keywords();
		$this->keywords->collectKeywords();
		$this->server = \PhpRobotRemoteServer\RobotRemoteProtocol::getInstance();
		$this->server->init($this->keywords);
		$result = $this->server->exec('<?xml version="1.0"?>
		<methodCall>
		   <methodName>get_keyword_names</methodName>
		   <params>
		      </params>
		   </methodCall>');
		var_dump($result);
	}

}
