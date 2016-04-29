<?php

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteServer;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class FullProtocolTests extends PHPUnit_Framework_TestCase {

    private $server;
    private $callCount;

    protected function setUp() {
        /*
         * TODO we could fake KeywordStore instead of loading files from disk :
         * would me more efficient, more self contained and more stable.
         */
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $protocol = ; // TODO objet à la volée !
        {
          public function exec($data) {
            $this->callCount++;
            return $this->callCount;
          }
        }
        $protocol->init($keywordStore);

        $this->server = new RobotRemoteServer();
        $this->server->init($protocol);

        $this->callCount = 0;
    }

    protected function tearDown() {

    }

    public function x_testStop() {
      $inputStream;
      $outputStream;
      $this->server->start($inputStream, $outputStream);

      $response; // from $outputStream
      $this->assertEquals('', $response);
    }

}
