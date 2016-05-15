<?php

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteServer;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class FullProtocolTest extends PHPUnit_Framework_TestCase {

    private $fakeRequests;
    private $fakeResponses;
    private $server;
    private $callCount;

    protected function setUp() {
        $this->fakeRequests = new FakeRequests();
        $this->fakeResponses = new FakeResponses($this);

        /*
         * TODO we could fake KeywordStore instead of loading files from disk :
         * would me more efficient, more self contained and more stable.
         */
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $protocol = new RobotRemoteProtocol();
        $protocol->init($keywordStore);

        $this->server = new RobotRemoteServer();
        $this->server->init($protocol);
    }

    protected function tearDown() {

    }

    private function startTestServer() {
      $this->server->start($this->fakeRequests, $this->fakeResponses);
    }

    public function testStop() {
      $this->fakeRequests->testRequests[] = '<?xml version="1.0"?>
            <methodCall>
               <methodName>stop_remote_server</methodName>
               <params>
               </params>
               </methodCall>';
      $this->fakeResponses->expectedResponses[] ='<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><boolean>1</boolean></value>
</param>
</params>
</methodResponse>';

      $this->startTestServer();

      $this->fakeResponses->checkAllResponsesDone();
    }

    public function testKeywordThenStop() {
      $this->fakeRequests->testRequests[] = '<?xml version="1.0"?>
            <methodCall>
               <methodName>run_keyword</methodName>
               <params>
                  <param><value><string>truth_of_life</string></value></param> 
                  <param><value><array><data></data></array></value></param> 
               </params>
               </methodCall>';
      $this->fakeResponses->expectedResponses[] ='<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><struct>
<member><name>return</name>
<value><int>42</int></value>
</member>
<member><name>status</name>
<value><string>PASS</string></value>
</member>
<member><name>output</name>
<value><string></string></value>  
</member>
<member><name>error</name>
<value><string></string></value>
</member>
<member><name>traceback</name>
<value><string></string></value>
</member>
</struct></value>
</param>
</params>
</methodResponse>';

      $this->fakeRequests->testRequests[] = '<?xml version="1.0"?>
            <methodCall>
               <methodName>stop_remote_server</methodName>
               <params>
               </params>
               </methodCall>';
      $this->fakeResponses->expectedResponses[] ='<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><boolean>1</boolean></value>
</param>
</params>
</methodResponse>';

      $this->startTestServer();

      $this->fakeResponses->checkAllResponsesDone();
    }

}

class FakeRequests {

  public $testRequests = array();
  private $testRequestsIdx = 0;

  public function get() {
    $request = $this->testRequests[$this->testRequestsIdx++];
    return $request;
  }

}

class FakeResponses {

  private $asserts;
  public $expectedResponses = array();
  private $expectedResponsesIdx = 0;

  public function __construct($asserts) {
    $this->asserts = $asserts;
  }

  public function add($response) {
    $expectedResponse = $this->expectedResponses[$this->expectedResponsesIdx++];
    $this->asserts->assertXmlStringEqualsXmlString($expectedResponse, $response);
  }

  public function checkAllResponsesDone() {
    $this->asserts->assertEquals(count($this->expectedResponses), $this->expectedResponsesIdx);
  }

}
