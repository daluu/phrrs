<?php

namespace PhpRobotRemoteServer;

require './vendor/autoload.php';

$argvCount = count($argv);
if ($argvCount < 3) {
	die("Missing parameters: path to the keywords implementation in PHP + port that the server must use\n");
} else if ($argvCount > 4) {
	die("Too many parameters: only two parameters required; optional parameter --quiet or -q\n");
}

$verbose = TRUE;
$keywordsDirectory = NULL;
$serverPort = NULL;

for ($i = 1; $i < $argvCount; $i++) {
	$arg = $argv[$i];
	if ($arg == '--quiet' || $arg == '-q') {
		$verbose = FALSE;
	} else {
		if (is_null($keywordsDirectory)) {
			$keywordsDirectory = $arg;
		} else {
			if (is_null($serverPort)) {
				$serverPort = $arg;
				if (!is_numeric($serverPort)) {
					die('Port must be a number: '.$serverPort."\n");
				}
			} else {
				die('Superfluous argument: '.$arg."\n");
			}
		}
	}
}

$keywordStore = new KeywordStore($verbose);
$keywordStore->collectKeywords($keywordsDirectory);

$protocol = new RobotRemoteProtocol($verbose);
$protocol->init($keywordStore);

$server = new RobotRemoteServer($verbose);
$server->init($protocol);
$server->startOnPort($serverPort);
