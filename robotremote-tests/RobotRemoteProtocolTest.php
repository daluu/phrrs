<?php

use \PhpRobotRemoteServer\RobotRemoteProtocol;

use \PhpXmlRpc\Value;

class RobotRemoteProtocolTest extends PHPUnit_Framework_TestCase {

    private $protocol;

    protected function setUp() {
        $this->protocol = new RobotRemoteProtocol();
    }

    protected function tearDown() {

    }

    /* ----- tests of xmlrpcEncodeKeywordResultValue ------ */

    public function testXmlrpcEncodeKeywordResultValueBoolean() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(TRUE);
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('boolean', $value->scalartyp());
        $this->assertEquals(TRUE, $value->scalarval());
    }

    public function testXmlrpcEncodeKeywordResultValueInteger() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(9);
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('int', $value->scalartyp());
        $this->assertEquals(9, $value->scalarval());
    }

    public function testXmlrpcEncodeKeywordResultValueDouble() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(7.5342);
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('string', $value->scalartyp());
        $this->assertEquals(7.5342, $value->scalarval());
    }

    public function testXmlrpcEncodeKeywordResultValueString() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue('test String');
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('string', $value->scalartyp());
        $this->assertEquals('test String', $value->scalarval());
    }

    public function testXmlrpcEncodeKeywordResultValueArray() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(array('un peu', 'beaucoup', 'passionnément', 'à la folie', 'pas du tout'));
        $this->assertEquals('array', $value->kindOf());
        $this->assertEquals(5, $value->arraysize());
        // TODO: values below ought to be be PhpXmlRpc\Value, not the strings... That's the todo in the code in action
        $this->assertEquals('un peu', $value->arraymem(0));
        $this->assertEquals('beaucoup', $value->arraymem(1));
        $this->assertEquals('passionnément', $value->arraymem(2));
        $this->assertEquals('à la folie', $value->arraymem(3));
        $this->assertEquals('pas du tout', $value->arraymem(4));
    }
    
    public function testXmlrpcEncodeKeywordResultValueObject() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(new TestClass());
        $this->assertEquals('struct', $value->kindOf());
        // TODO what's inside is non-sense to me. I'd rather write the test when our code is at least trying to send what it should than enforcing weird stuff.
        // var_dump($value->structeach());
        // var_dump($value->structeach());
        // var_dump($value->structeach());
        // var_dump($value->structeach());
    }

    public function testXmlrpcEncodeKeywordResultValueResource() {
        $resource = fopen(__FILE__, 'r');
        // echo gettype($resource); // resource
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue($resource);
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('null', $value->scalartyp());
        $this->assertEquals($resource, $value->scalarval()); // TOOD What's the use of having the resource there? What will it become through the XML-RPC link?
    }

    public function testXmlrpcEncodeKeywordResultValueNull() {
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(null);
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('null', $value->scalartyp());
        $this->assertEquals(null, $value->scalarval());
    }

    public function testXmlrpcEncodeKeywordResultValueUnknownType() {
        $resource = fopen(__FILE__, 'r');
        fclose($resource);
        // echo gettype($resource); // unknown type
        $value = $this->protocol->xmlrpcEncodeKeywordResultValue(null);
        $this->assertEquals('scalar', $value->kindOf());
        $this->assertEquals('null', $value->scalartyp());
        $this->assertEquals(null, $value->scalarval());
    }

    /* ----- tests of convertXmlrpcArgToPhp ------ */

    public function testConvertXmlrpcArgToPhpScalarBoolean() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(TRUE, 'boolean'));
        $this->assertInternalType('boolean', $phpValue);
        $this->assertEquals(TRUE, $phpValue);
    }

    public function testConvertXmlrpcArgToPhpScalarInt() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(3, 'int'));
        $this->assertInternalType('int', $phpValue);
        $this->assertEquals(3, $phpValue);
    }

    public function testConvertXmlrpcArgToPhpScalarDouble() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(123.991918, 'double'));
        $this->assertInternalType('double', $phpValue);
        $this->assertEquals(123.991918, $phpValue);
    }

    public function testConvertXmlrpcArgToPhpScalarString() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value('(boom)', 'string'));
        $this->assertInternalType('string', $phpValue);
        $this->assertEquals('(boom)', $phpValue);
    }

    public function testConvertXmlrpcArgToPhpScalarNull() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(NULL, 'null'));
        $this->assertInternalType('null', $phpValue);
        $this->assertEquals(NULL, $phpValue);
    }

    public function testConvertXmlrpcArgToPhpArrayScalar() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(array(
                new Value(4, 'int'),
                new Value(FALSE, 'boolean'),
                new Value('foobar', 'string'),
                new Value(NULL, 'null'),
                new Value(9.2321, 'double'),
            ), 'array'));
        $this->assertInternalType('array', $phpValue);
        $this->assertEquals(array(
                4,
                FALSE,
                'foobar',
                NULL,
                9.2321
            ), $phpValue);
    }

    public function testConvertXmlrpcArgToPhpNestedArray() { // TODO!!!
        // $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(array(
        //         new Value(4, 'int'),
        //         new Value(FALSE, 'boolean'),
        //         new Value('foobar', 'string'),
        //         new Value(NULL, 'null'),
        //         new Value(9.2321, 'double'),
        //     ), 'array'));
        // $this->assertInternalType('array', $phpValue);
        // $this->assertEquals(array(
        //         4,
        //         FALSE,
        //         'foobar',
        //         NULL,
        //         9.2321
        //     ), $phpValue);
    }

    // TODO tests for array's of non-scalar (not yet supported in code anyway)

    public function testConvertXmlrpcArgToPhpStructScalar() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(array(
                'key1' => new Value('value as a string', 'string'),
                'key2' => new Value(56, 'int'),
                'key3' => new Value(NULL, 'null'),
                'key4' => new Value(9.62, 'double'),
                'key5' => new Value(TRUE, 'boolean'),
            ), 'struct'));
        $this->assertInternalType('array', $phpValue);
        $this->assertEquals(array(
                'key1' => 'value as a string',
                'key2' => 56,
                'key3' => NULL,
                'key4' => 9.62,
                'key5' => TRUE,
            ), $phpValue);
    }
    // TODO tests for struct's of non-scalar (not yet supported in code anyway)

    public function testConvertXmlrpcArgToPhpUndef() {
        $xmlrpcValue = $this->getMockBuilder('\PhpXmlRpc\Value')->disableOriginalConstructor()->setMethods(['kindOf'])->getMock();
        $xmlrpcValue->expects($this->once())->method('kindOf')->willReturn('undef');

        $phpValue = $this->protocol->convertXmlrpcArgToPhp($xmlrpcValue);
        $this->assertNUll($phpValue);
    }

}

class TestClass {
    var $field1 = 'beginning';
    var $field2 = 'next';
    var $field3 = 'final';
}
