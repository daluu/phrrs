<?php

use \PhpRobotRemoteServer\KeywordStore;

class KeywordStoreTests extends PHPUnit_Framework_TestCase {

    protected function setUp() {

    }

    protected function tearDown() {

    }

    public function testCleanUpPhpArguments() {
        $rawArguments = array(
            '$abc',
            '$prettymegagigalongandthatsnothngyetboooooyaaaaaa',
            '$o',
            '$somanyparameters'
            );
        $actual = KeywordStore::cleanUpPhpArguments($rawArguments);
        $this->assertEquals(array(
            'abc',
            'prettymegagigalongandthatsnothngyetboooooyaaaaaa',
            'o',
            'somanyparameters'
            ), $actual);
    }

    public function testCleanUpPhpArgumentsNoArgs() {
        $rawArguments = array();
        $actual = KeywordStore::cleanUpPhpArguments($rawArguments);
        $this->assertEquals(array(), $actual);
    }

    public function testCleanUpPhpDocumentation() {
        $rawDocumentation = '/**
   * Compare 2 strings. If they are not equal, throws exception.
   */';
        $actual = KeywordStore::cleanUpPhpDocumentation($rawDocumentation);
        $this->assertEquals('Compare 2 strings. If they are not equal, throws exception.', $actual);
    }

    public function testCollectKeywordsFromFile() {
        $file = __DIR__.'/test-libraries/ExampleLibrary.php';
        $keywordStore = new KeywordStore();
        $keywordStore->collectKeywordsFromFile(__DIR__.'/test-libraries/ExampleLibrary.php');
        $keywords = $keywordStore->keywords;
        $this->assertEquals(array(
            'truth_of_life' => array(
                    'file' => $file,
                    'class' => '\\ExampleLibrary',
                    'arguments' => array(),
                    'documentation' => ''),
            'strings_should_be_equal' => array(
                    'file' => $file,
                    'class' => '\\ExampleLibrary',
                    'arguments' => array('str1', 'str2'),
                    'documentation' => 'Compare 2 strings. If they are not equal, throws exception.')
             ), $keywords);
    }

    // TODO multiple classes in same file
    // TODO multiple files

}
