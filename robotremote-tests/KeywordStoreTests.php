<?php

use \PhpRobotRemoteServer\KeywordStore;

class KeywordStoreTests extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {

    }

    protected function tearDown()
    {

    }

    public function testGetKeywordNames()
    {
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $keywordNames = $keywordStore->getKeywordNames();

        $this->assertEquals(2, count($keywordNames));
        $this->assertEquals('truth_of_life', $keywordNames[0]);
        $this->assertEquals('strings_should_be_equal', $keywordNames[1]);
    }

    public function testExecKeyword()
    {
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $args = array();
        $result = $keywordStore->execKeyword('truth_of_life', $args);

        $this->assertEquals(42, $result);
    }

    public function testExecKeywordArgs()
    {
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $args = array('abc', 'abc');
        $result = $keywordStore->execKeyword('strings_should_be_equal', $args);

        $this->assertEquals(42, $result);
    }

    public function testExecKeywordException()
    {
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywords(__DIR__.'/test-libraries');

        $this->setExpectedException('Exception');

        $args = array('abc', 'def');
        $result = $keywordStore->execKeyword('strings_should_be_equal', $args);
    }

}
