<?php

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteServer;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class FullProtocolTests extends PHPUnit_Framework_TestCase {

    private $server;

    protected function setUp() {
        /*
         * TODO we could fake KeywordStore instead of loading files from disk :
         * would me more efficient, more self contained and more stable.
         */
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $protocol = RobotRemoteProtocol::getInstance();
        $protocol->init($keywordStore);

        $this->server = new RobotRemoteServer();
        $this->server->init($protocol);
    }

    protected function tearDown() {

    }

    private function checkRpcCall($rpcRequest, $expectedRpcAnswer) {
        $inputStream = fopen('data://text/plain;base64,'
                . base64_encode($rpcRequest), 'r');
        $outputStream = fopen('php://memory', 'w');
        $this->server->execRequest($inputStream, $outputStream);

        rewind($outputStream);
        $result = stream_get_contents($outputStream);
        $this->assertEquals($expectedRpcAnswer, $result);
    }

    public function testGetKeywordNames() {
        $this->checkRpcCall('<?xml version="1.0"?>
            <methodCall>
               <methodName>get_keyword_names</methodName>
               <params>
                  </params>
               </methodCall>', '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><array>
<data>
<value><string>truth_of_life</string></value>
<value><string>strings_should_be_equal</string></value>
</data>
</array></value>
</param>
</params>
</methodResponse>');
    }

    public function testRunKeyword() {
        $this->checkRpcCall('<?xml version="1.0"?>
            <methodCall>
               <methodName>run_keyword</methodName>
               <params>
                  <param><value><string>truth_of_life</string></value></param> 
                  <param><value><array><data></data></array></value></param> 
               </params>
               </methodCall>', '<?xml version="1.0"?>
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
</methodResponse>');
    }

    public function testGetKeywordArguments() {
        $this->checkRpcCall('<?xml version="1.0"?>
            <methodCall>
               <methodName>get_keyword_arguments</methodName>
               <params>
                  <param><value><string>strings_should_be_equal</string></value></param> 
               </params>
               </methodCall>', '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><array>
<data>
<value><string>str1</string></value>
<value><string>str2</string></value>
</data>
</array></value>
</param>
</params>
</methodResponse>');
    }

    public function testGetKeywordDocumentation() {
        $this->checkRpcCall('<?xml version="1.0"?>
            <methodCall>
               <methodName>get_keyword_documentation</methodName>
               <params>
                  <param><value><string>strings_should_be_equal</string></value></param> 
               </params>
               </methodCall>', '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><string>Compare 2 strings. If they are not equal, throws exception.</string></value>
</param>
</params>
</methodResponse>');
    }

}
