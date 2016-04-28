<?php

use \PhpRobotRemoteServer\KeywordStore;

class KeywordStoreTests extends PHPUnit_Framework_TestCase {

    private $keywordStore;

    protected function setUp() {
        $this->keywordStore = new KeywordStore();
        $this->keywordStore->collectKeywords(__DIR__.'/test-libraries');
    }

    protected function tearDown() {

    }

    public function testGetKeywordNames() {
        $keywordNames = $this->keywordStore->getKeywordNames();

        $this->assertEquals(2, count($keywordNames));
        $this->assertEquals('truth_of_life', $keywordNames[0]);
        $this->assertEquals('strings_should_be_equal', $keywordNames[1]);
    }

    public function testExecKeyword() {
        $args = array();
        $result = $this->keywordStore->execKeyword('truth_of_life', $args);

        $this->assertEquals(42, $result);
    }

    public function testExecKeywordArgs() {
        $args = array('abc', 'abc');
        $result = $this->keywordStore->execKeyword('strings_should_be_equal', $args);

        $this->assertEquals(42, $result);
    }

    public function testExecKeywordException() {
        $this->setExpectedException('Exception');

        $args = array('abc', 'def');
        $result = $this->keywordStore->execKeyword('strings_should_be_equal', $args);
    }

    public function testGetKeywordArgumentsNoArgs() {
        $keywordArgs = $this->keywordStore->getKeywordArguments('truth_of_life');

        $this->assertEquals(0, count($keywordArgs));
    }

    public function testGetKeywordArgumentsTwoArgs() {
        $keywordArgs = $this->keywordStore->getKeywordArguments('strings_should_be_equal');

        $this->assertEquals(2, count($keywordArgs));
        $this->assertEquals('str1', $keywordArgs[0]);
        $this->assertEquals('str2', $keywordArgs[1]);
    }

    public function testGetKeywordDocumentationEmptyDoc() {
        $keywordDoc = $this->keywordStore->getKeywordDocumentation('truth_of_life');

        $this->assertEquals('', $keywordDoc);
    }

    public function testGetKeywordDocumentationWithDoc() {
        $keywordDoc = $this->keywordStore->getKeywordDocumentation('strings_should_be_equal');

        $this->assertEquals('Compare 2 strings. If they are not equal, throws exception.', $keywordDoc);
    }

    // TODO special characters in doc

}
