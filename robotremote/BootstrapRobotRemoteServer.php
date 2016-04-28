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
$keywordStore = new KeywordStore();
$keywordStore->collectKeywords($keywordsDirectory);

$protocol = RobotRemoteProtocol::getInstance();
$protocol->init($keywordStore);

$server = new RobotRemoteServer();
$server->init($protocol);
$serverPort = $argv[2];
$server->start($serverPort);
