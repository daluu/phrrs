<?php

use \PhpRobotRemoteServer\KeywordStore;

class KeywordStoreTest extends PHPUnit_Framework_TestCase {

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

    public function testFindFilesSingleFile() {
        $file = __DIR__.'/test-libraries/ExampleLibrary.php';
        $keywordStore = new KeywordStore(FALSE);
        $files = $keywordStore->findFiles($file);
        $this->assertEquals(array(
                $file
            ), $files);
    }

    public function testFindFilesBasic() {
        $rootDir = __DIR__.'/test-libraries';
        $keywordStore = new KeywordStore(FALSE);
        $files = $keywordStore->findFiles($rootDir);
        $this->assertEquals(array(
                $rootDir.'/ExampleLibrary.php'
            ), $files);
    }

    public function testFindFilesMultipleFiles() {
        $rootDir = __DIR__.'/test-libraries-multiple-files';
        $keywordStore = new KeywordStore(FALSE);
        $files = $keywordStore->findFiles($rootDir);

        // Make sure check do not depend on the order of the elements: sorting the result
        natsort($files);
        $this->assertEquals(array(
                $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                $rootDir.'/subfolder/MultipleClassInSameFolder2.php',
                $rootDir.'/subfolder/MultipleClassInSameFolder3.php',
                $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
            ), $files);
    }

    public function testCollectKeywordsFromFile() {
        $file = __DIR__.'/test-libraries/ExampleLibrary.php';
        $keywordStore = new KeywordStore(FALSE);
        $keywordStore->keywords = array();
        $keywordStore->collectKeywordsFromFile($file);
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

    public function testCollectKeywordsMultipleFiles() {
        $rootDir = __DIR__.'/test-libraries-multiple-files';
        $keywordStore = new KeywordStore(FALSE);
        $keywordStore->collectKeywords($rootDir);
        $keywords = $keywordStore->keywords;
        $this->assertEquals(array(
            'keywordWithNamespace1' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace2' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace2',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace3' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace3',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace4' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace3',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace5' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace3',
                    'arguments' => array(),
                    'documentation' => ''),

            'deeplyNestedKeyword1' => array(
                    'file' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                    'class' => '\\DeeplyNestedClass1',
                    'arguments' => array(),
                    'documentation' => ''),
            'deeplyNestedKeyword2' => array(
                    'file' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                    'class' => '\\DeeplyNestedClass1',
                    'arguments' => array(),
                    'documentation' => ''),
            'deeplyNestedKeyword3' => array(
                    'file' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                    'class' => '\\DeeplyNestedClass2',
                    'arguments' => array(),
                    'documentation' => ''),

            'keywordInSameFolder1' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                    'class' => '\\MultipleClassInSameFolder1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder2' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                    'class' => '\\MultipleClassInSameFolder1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder3' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                    'class' => '\\MultipleClassInSameFolder1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder4' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder2.php',
                    'class' => '\\MultipleClassInSameFolder2',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder5' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder3.php',
                    'class' => '\\MultipleClassInSameFolder3',
                    'arguments' => array(),
                    'documentation' => ''),
             ), $keywords);
    }

    public function testCollectKeywordsDuplicateDefinitions() {
        $rootDir = __DIR__.'/test-libraries-duplicate-keywords';
        $keywordStore = new KeywordStore(FALSE);
        $keywordStore->keywords = array();
        $keywordStore->collectKeywords($rootDir);
        $keywords = $keywordStore->keywords;
        $this->assertEquals(array(
            'truth_of_life' => array(
                    'file' => $rootDir.'/FirstKeywordDefinition.php',
                    'class' => '\\FirstKeywordDefinition',
                    'arguments' => array(),
                    'documentation' => ''),
            'strings_should_be_equal' => array(
                    'file' => $rootDir.'/SecondKeywordDefinition.php',
                    'class' => '\\SecondKeywordDefinition',
                    'arguments' => array('str1', 'str2'),
                    'documentation' => 'Compare 2 strings. If they are not equal, throws exception.')
             ), $keywords);
    }

    public function testKeywordReport() {
        $rootDir = __DIR__.'/test-libraries-multiple-files';
        $keywordStore = new KeywordStore(FALSE);
        $keywordStore->keywords = array(
            'keywordWithNamespace1' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace2' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace2',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace3' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace3',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace4' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace3',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordWithNamespace5' => array(
                    'file' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                    'class' => '\\TestNamespace\\ClassWithNamespace3',
                    'arguments' => array(),
                    'documentation' => ''),

            'deeplyNestedKeyword1' => array(
                    'file' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                    'class' => '\\DeeplyNestedClass1',
                    'arguments' => array(),
                    'documentation' => ''),
            'deeplyNestedKeyword2' => array(
                    'file' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                    'class' => '\\DeeplyNestedClass1',
                    'arguments' => array(),
                    'documentation' => ''),
            'deeplyNestedKeyword3' => array(
                    'file' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                    'class' => '\\DeeplyNestedClass2',
                    'arguments' => array(),
                    'documentation' => ''),

            'keywordInSameFolder1' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                    'class' => '\\MultipleClassInSameFolder1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder2' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                    'class' => '\\MultipleClassInSameFolder1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder3' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                    'class' => '\\MultipleClassInSameFolder1',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder4' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder2.php',
                    'class' => '\\MultipleClassInSameFolder2',
                    'arguments' => array(),
                    'documentation' => ''),
            'keywordInSameFolder5' => array(
                    'file' => $rootDir.'/subfolder/MultipleClassInSameFolder3.php',
                    'class' => '\\MultipleClassInSameFolder3',
                    'arguments' => array(),
                    'documentation' => ''),
             );

        $keywordReport = $keywordStore->keywordReport();

        $this->assertEquals(array(
                'keywordWithNamespace1' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                'keywordWithNamespace2' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                'keywordWithNamespace3' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                'keywordWithNamespace4' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                'keywordWithNamespace5' => $rootDir.'/another-subfolder/ClassesWithNamespace.php',
                'deeplyNestedKeyword1' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                'deeplyNestedKeyword2' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                'deeplyNestedKeyword3' => $rootDir.'/subfolder/deeply-nested/DeeplyNestedClasses.php',
                'keywordInSameFolder1' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                'keywordInSameFolder2' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                'keywordInSameFolder3' => $rootDir.'/subfolder/MultipleClassInSameFolder1.php',
                'keywordInSameFolder4' => $rootDir.'/subfolder/MultipleClassInSameFolder2.php',
                'keywordInSameFolder5' => $rootDir.'/subfolder/MultipleClassInSameFolder3.php',
            ),
            $keywordReport);
    }

}
