<?php

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteServer;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

class FullProtocolTest extends PHPUnit_Framework_TestCase {

    private $server;

    protected function setUp() {
        $keywordStore = new KeywordStore(FALSE);
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $protocol = new RobotRemoteProtocol(FALSE);
        $protocol->init($keywordStore);

        $this->server = new RobotRemoteServer(FALSE);
        $this->server->init($protocol);
    }

    protected function tearDown() {

    }

    private function checkRpcCall($rpcRequest, $expectedRpcAnswer) {
        $actualRpcAnswer = $this->server->execRequest($rpcRequest);
        
        $this->assertXmlStringEqualsXmlString($expectedRpcAnswer, $actualRpcAnswer);
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
<value><string>stop_remote_server</string></value>
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

    public function testRunExceptionKeyword() {
        $rpcRequest = '<?xml version="1.0"?>
            <methodCall>
               <methodName>run_keyword</methodName>
               <params>
                  <param><value><string>strings_should_be_equal</string></value></param> 
                  <param><value><array><data>
                  <value><string>abc</string></value>
                  <value><string>def</string></value></data></array></value></param> 
               </params>
               </methodCall>';
        $actualRpcAnswer = $this->server->execRequest($rpcRequest);

        // We only check the beginning to avoid comparing the "traceback" part, containing the full stack trace, which is brittle
        $toCheck = '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><struct>
<member><name>return</name>
<value><string></string></value>
</member>
<member><name>status</name>
<value><string>FAIL</string></value>
</member>
<member><name>output</name>
<value><string></string></value>
</member>
<member><name>error</name>
<value><string>Given strings are not equal</string></value>
</member>
<member><name>traceback</name>
<value><string>#0 [internal function]: ExampleLibrary::strings_should_be_equal(&apos;abc&apos;, &apos;def&apos;)';
        $this->assertTrue(strpos($actualRpcAnswer, $toCheck) === 0, $actualRpcAnswer."\nDO NOT START WITH\n".$toCheck);
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

    public function testStopRemoteServer() {
        $this->checkRpcCall('<?xml version="1.0"?>
            <methodCall>
               <methodName>stop_remote_server</methodName>
               <params>
               </params>
               </methodCall>', '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value><boolean>1</boolean></value>
</param>
</params>
</methodResponse>');
        $this->assertTrue($this->server->isStopped());
    }

}
