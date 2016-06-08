<?php

use \PhpRobotRemoteServer\KeywordStore;

class HighLevelKeywordStoreTest extends PHPUnit_Framework_TestCase {

    private $keywordStore;

    protected function setUp() {
        $this->keywordStore = new KeywordStore(FALSE);
        $this->keywordStore->collectKeywords(__DIR__.'/test-libraries');
        $this->keywordStore->addStopRemoteServerKeyword('', '');
    }

    protected function tearDown() {

    }

    public function testGetKeywordNames() {
        $keywordNames = $this->keywordStore->getKeywordNames();

        $this->assertEquals(3, count($keywordNames));
        $this->assertEquals('truth_of_life', $keywordNames[0]);
        $this->assertEquals('strings_should_be_equal', $keywordNames[1]);
        $this->assertEquals('stop_remote_server', $keywordNames[2]);
    }

    public function testExecKeyword() {
        $args = array();
        $result = $this->keywordStore->execKeyword('truth_of_life', $args);

        $this->assertEquals(42, $result);
    }

    public function testExecKeywordArgs() {
        $args = array('abc', 'abc');
        $result = $this->keywordStore->execKeyword('strings_should_be_equal', $args);

        $this->assertTrue($result);
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

    // TODO test special characters in doc

    public function testExecKeywordMultipleFiles() {
        $this->keywordStore->collectKeywords(__DIR__.'/test-libraries-multiple-files');

        $keywordsToTest = array(
            'keywordWithNamespace1',
            'keywordWithNamespace2',
            'keywordWithNamespace3',
            'keywordWithNamespace4',
            'keywordWithNamespace5',
            'deeplyNestedKeyword1',
            'deeplyNestedKeyword2',
            'deeplyNestedKeyword3',
            'keywordInSameFolder1',
            'keywordInSameFolder2',
            'keywordInSameFolder3',
            'keywordInSameFolder4',
            'keywordInSameFolder5',
            );

        foreach ($keywordsToTest as $keywordName) {
            $args = array();
            $result = $this->keywordStore->execKeyword($keywordName, $args);

            $this->assertEquals($keywordName, $result);
        }
    }

}
