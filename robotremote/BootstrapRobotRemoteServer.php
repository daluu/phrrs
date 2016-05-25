<?php

namespace PhpRobotRemoteServer;

require './vendor/autoload.php';

$argvCount = count($argv);
if ($argvCount < 3) {
	die("Missing parameters: path to the keywords implementation in PHP + port that the server must use\n");
} else if ($argvCount > 3) {
	die("Too many parameters: only two parameters required\n");
}

$keywordsDirectory = $argv[1];
$serverPort = $argv[2];

if (!is_numeric($serverPort)) {
	die("Port must be a number\n");
}

$keywordStore = new KeywordStore();
$keywordStore->collectKeywords($keywordsDirectory);

$protocol = new RobotRemoteProtocol();
$protocol->init($keywordStore);

$server = new RobotRemoteServer();
$server->init($protocol);
$server->startOnPort($serverPort);
