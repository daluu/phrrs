<?php

namespace PhpRobotRemoteServer;

require './vendor/autoload.php';

$argvCount = count($argv);
if ($argvCount < 2) {
	die("Missing parameters: path to the keywords implementation in PHP\n");
} else if ($argvCount > 2) {
	die("Too many parameters: only one parameter required\n");
}

$keywordsDirectory = $argv[1];
$keywordStore = new KeywordStore();
$keywordStore->collectKeywords($keywordsDirectory);

$protocol = RobotRemoteProtocol::getInstance();
$protocol->init($keywordStore);

$server = new RobotRemoteServer();
$server->init($protocol);
$server->start(new DemoRequests(), new DemoResponses());

class DemoRequests {

	private $stopRequest = '<?xml version="1.0"?>
		<methodCall>
		   <methodName>stop_remote_server</methodName>
		   <params>
		      </params>
		   </methodCall>';

	private $requests = array(
		'<?xml version="1.0"?>
		<methodCall>
		   <methodName>get_keyword_names</methodName>
		   <params>
		      </params>
		   </methodCall>',
		$this->stopRequest // always keep this one or the server will loop endlessly
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
