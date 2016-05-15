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

    // TODO tests with multiple files
    // TODO tests with multiple classes in single file
    // TODO tests with use of namespace in files

}
