<?php

namespace PhpRobotRemoteServer;

require_once('RobotRemoteServer.php');

$argvCount = count($argv);
if ($argvCount < 2) {
	die("Missing parameter: path to the keywords implementation in PHP\n");
} else if ($argvCount > 2) {
	die("Too many parameters: only one parameter required\n");
}

(new RobotRemoteServer())->start($argv[1]);
