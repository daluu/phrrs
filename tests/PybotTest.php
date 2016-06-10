<?php

use \PhpRobotRemoteServer\KeywordStore;

class PybotTest extends PHPUnit_Framework_TestCase {

    private function runPybotCheckSuccess($testLibraries, $robotFile) {
        $output = array();
        $exitCode = -1;

        $port = 8270;
        $pybotCommand = 'pybot --variable PHP_REMOTE_HOST:localhost:'.$port.' -o NONE -l NONE -r NONE '.$robotFile;
        $robotRemoteCommand = 'php '.__DIR__.'/../src/BootstrapRobotRemoteServer.php '.$testLibraries.' '.$port.' --quiet';

        $robotRemote = popen($robotRemoteCommand, 'w');
        // TODO wait a little? Not needed so far...

        exec($pybotCommand, $output, $exitCode);
        pclose($robotRemote);

        $this->assertEquals(0, $exitCode, "\n".implode("\n", $output)."\n");
    }

    public function testBasicKeywords() {
        $this->runPybotCheckSuccess(__DIR__.'/test-libraries', __DIR__.'/test-robot-framework/BasicExample.robot');
    }

    public function testBasicList() {
        $this->runPybotCheckSuccess(__DIR__.'/test-libraries-complex-data', __DIR__.'/test-robot-framework/BasicList.robot');
    }

    public function testBasicDictionary() {
        $this->runPybotCheckSuccess(__DIR__.'/test-libraries-complex-data', __DIR__.'/test-robot-framework/BasicDictionary.robot');
    }

}
