<?php

use \PhpRobotRemoteServer\ClassFinder;

class ClassFinderTests extends PHPUnit_Framework_TestCase {

    private $classFinder;

    protected function setUp() {
        $this->classFinder = new ClassFinder();
    }

    protected function tearDown() {

    }

    public function testSingleClass() {
        $found = $this->classFinder->findFunctionsByClasses(__DIR__.'/test-libraries/ExampleLibrary.php');

        $this->assertEquals(array(
            '\\ExampleLibrary' => array(
                'truth_of_life' => array(
                    'arguments' => array(),
                    'documentation' => ''),
                'strings_should_be_equal' => array(
                    'arguments' => array('$str1', '$str2'),
                    'documentation' => '/**
   * Compare 2 strings. If they are not equal, throws exception.
   */')
                )), $found);
    }

    public function testMultipleClassesWithNamespace() {
        $found = $this->classFinder->findFunctionsByClasses(__DIR__.'/test-libraries-multiple-files/another-subfolder/ClassesWithNamespace.php');

        $this->assertEquals(array(
            '\\TestNamespace\\ClassWithNamespace1' => array(
                'keywordWithNamespace1' => array(
                    'arguments' => array(),
                    'documentation' => ''),
                ),
            '\\TestNamespace\\ClassWithNamespace2' => array(
                'keywordWithNamespace2' => array(
                    'arguments' => array(),
                    'documentation' => ''),
                ),
            '\\TestNamespace\\ClassWithNamespace3' => array(
                'keywordWithNamespace3' => array(
                    'arguments' => array(),
                    'documentation' => ''),
                'keywordWithNamespace4' => array(
                    'arguments' => array(),
                    'documentation' => ''),
                'keywordWithNamespace5' => array(
                    'arguments' => array(),
                    'documentation' => ''),
                )), $found);
    }

}
