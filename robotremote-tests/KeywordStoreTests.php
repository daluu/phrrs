<?php

use \PhpRobotRemoteServer\KeywordStore;

/**
 * Tests involving parsing of xml and handling of xmlrpc values
 */
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
        $this->assertEquals('count_items_in_directory', $keywordNames[0]);
        $this->assertEquals('strings_should_be_equal', $keywordNames[1]);
    }

}
