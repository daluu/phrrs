<?php

use \PhpRobotRemoteServer\KeywordStore;
use \PhpRobotRemoteServer\RobotRemoteProtocol;

use \PhpXmlRpc\Value;

class RobotRemoteProtocolTest extends PHPUnit_Framework_TestCase {

    private $protocol;

    protected function setUp() {
        $this->protocol = new RobotRemoteProtocol(FALSE);
    }

    protected function tearDown() {

    }

    /* ----- tests of xmlrpcEncodeKeywordResultValue ------ */

    private function checkScalar($encodedValue, $type, $actualValue) {
        $this->assertEquals('scalar', $encodedValue->kindOf());
        $this->assertEquals($type, $encodedValue->scalartyp());
        $this->assertEquals($actualValue, $encodedValue->scalarval());
    }

    public function testXmlrpcEncodeKeywordResultValueBoolean() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(TRUE);
        $this->checkScalar($encodedValue, 'boolean', TRUE);
    }

    public function testXmlrpcEncodeKeywordResultValueInteger() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(9);
        $this->checkScalar($encodedValue, 'int', 9);
    }

    public function testXmlrpcEncodeKeywordResultValueDouble() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(7.5342);
        $this->checkScalar($encodedValue, 'string', 7.5342);
    }

    public function testXmlrpcEncodeKeywordResultValueString() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue('test String');
        $this->checkScalar($encodedValue, 'string', 'test String');
    }

    public function testXmlrpcEncodeKeywordResultValueArray() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(array('un peu', 'beaucoup', 'passionnément', 'à la folie', 'pas du tout'));
        $this->assertEquals('array', $encodedValue->kindOf());
        $this->assertEquals(5, count($encodedValue));
        $this->checkScalar($encodedValue[0], 'string', 'un peu');
        $this->checkScalar($encodedValue[1], 'string', 'beaucoup');
        $this->checkScalar($encodedValue[2], 'string', 'passionnément');
        $this->checkScalar($encodedValue[3], 'string', 'à la folie');
        $this->checkScalar($encodedValue[4], 'string', 'pas du tout');
    }

    public function testXmlrpcEncodeKeywordResultValueAssociativeArray() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(array('paquerette'=>'un peu', 'coquelicot'=>'beaucoup',
            'pissenlit'=>'passionnément', 'rose'=>'à la folie', 'tulipe'=>'pas du tout'));
        $this->assertEquals('struct', $encodedValue->kindOf());
        $this->assertEquals(5, count($encodedValue));
        $this->checkScalar($encodedValue['paquerette'], 'string', 'un peu');
        $this->checkScalar($encodedValue['coquelicot'], 'string', 'beaucoup');
        $this->checkScalar($encodedValue['pissenlit'], 'string', 'passionnément');
        $this->checkScalar($encodedValue['rose'], 'string', 'à la folie');
        $this->checkScalar($encodedValue['tulipe'], 'string', 'pas du tout');
    }
    
    public function testXmlrpcEncodeKeywordResultValueNestedArray() {
        // TODO not a very readable test, especially the 'check' part... Too much a complex a test?
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(array(
            'k1' => 'v1',
            'k2' => array(
                'v2',
                'v3',
                array(
                    'v4',
                    array(
                        'k3' => 'v5'
                        )
                    )
                ),
            'k4' => array(
                'k5' => array(),
                'k6' => NULL,
                'k7' => 76,
                'k8' => array(
                    'v6',
                    array(
                        'k9' => 'v7'
                    ),
                    'v8',
                    'v9'
                )
            ),
        ));

        $this->assertEquals('struct', $encodedValue->kindOf());
        $this->assertEquals(3, count($encodedValue)); 

        $k1 = $encodedValue['k1'];
        $this->checkScalar($k1, 'string', 'v1');

        // k2
        $k2 = $encodedValue['k2'];
        $this->assertEquals('array', $k2->kindOf());
        $this->assertEquals(3, count($k2));
        $this->checkScalar($k2[0], 'string', 'v2');
        $this->checkScalar($k2[1], 'string', 'v3');
        $k2subArray = $k2[2];
        $this->assertEquals('array', $k2subArray->kindOf());
        $this->assertEquals(2, count($k2subArray));
        $this->checkScalar($k2subArray[0], 'string', 'v4');
        $this->assertEquals('struct', $k2subArray[1]->kindOf());
        $this->assertEquals(1, count($k2subArray[1]));
        $this->checkScalar($k2subArray[1]['k3'], 'string', 'v5');

        // k4
        $k4 = $encodedValue['k4'];
        $this->assertEquals('struct', $k4->kindOf());
        $this->assertEquals(4, count($k4));
        $this->assertEquals('array', $k4['k5']->kindOf());
        $this->assertEquals(0, count($k4['k5']));
        $this->checkScalar($k4['k6'], 'null', NULL);
        $this->checkScalar($k4['k7'], 'int', 76);
        $k8 = $k4['k8'];
        $this->assertEquals('array', $k8->kindOf());
        $this->assertEquals(4, count($k8));
        $this->checkScalar($k8[0], 'string', 'v6');
        $this->assertEquals('struct', $k8[1]->kindOf());
        $this->assertEquals(1, count($k8[1]));
        $this->checkScalar($k8[1]['k9'], 'string', 'v7');
        $this->checkScalar($k8[2], 'string', 'v8');
        $this->checkScalar($k8[3], 'string', 'v9');
    }
    
    public function testXmlrpcEncodeKeywordResultValueObject() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(new SamplePhpClassToShareInXmlrpc());
        $this->assertEquals('struct', $encodedValue->kindOf());
        $this->assertEquals(4, count($encodedValue));
        $this->checkScalar($encodedValue['field1'], 'string', 'beginning');
        $this->checkScalar($encodedValue['field2'], 'string', 'next');
        $this->checkScalar($encodedValue['field3'], 'string', 'final');
        $this->checkScalar($encodedValue['publicField'], 'string', 'public');
    }

    public function testXmlrpcEncodeKeywordResultValueResource() {
        $resource = fopen(__FILE__, 'r');
        // echo gettype($resource); // resource
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue($resource);
        $this->checkScalar($encodedValue, 'null', $resource); // TOOD What's the use of having the resource there? What will it become through the XML-RPC link?
    }

    public function testXmlrpcEncodeKeywordResultValueNull() {
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(null);
        $this->checkScalar($encodedValue, 'null', null);
    }

    public function testXmlrpcEncodeKeywordResultValueUnknownType() {
        $resource = fopen(__FILE__, 'r');
        fclose($resource);
        // echo gettype($resource); // unknown type
        $encodedValue = $this->protocol->xmlrpcEncodeKeywordResultValue(null);
        $this->checkScalar($encodedValue, 'null', null);
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

    public function testConvertXmlrpcArgToPhpNestedArray() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(array(
                new Value(4, 'int'),
                new Value(array(
                    new Value(FALSE, 'boolean'),
                    new Value('foobar', 'string'),
                    new Value(NULL, 'null'),
                    new Value(array(
                        new Value(42, 'int'),
                        new Value(array(), 'array'),
                        new Value(7, 'int'),
                    ), 'array'),
                    new Value(9.2321, 'double'),
                ), 'array'),
            ), 'array'));
        $this->assertInternalType('array', $phpValue);
        $this->assertEquals(array(
                4,
                array(
                    FALSE,
                    'foobar',
                    NULL,
                    array(
                        42,
                        array(),
                        7
                    ),
                    9.2321
                ),
            ), $phpValue);
    }

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

    public function testConvertXmlrpcArgToPhpNestedStructArray() {
        $phpValue = $this->protocol->convertXmlrpcArgToPhp(new Value(array(
                'k1' => new Value('v1', 'string'),
                'k2' => new Value(array(
                    new Value('v2', 'string'),
                    new Value('v3', 'string'),
                    new Value(array(
                        new Value('v4', 'string'),
                        new Value(array(
                            'k3' => new Value('v5', 'string')
                            ), 'struct'),
                        ), 'array'),
                ), 'array'),
                'k4' => new Value(array(
                    'k5' => new Value(array(), 'array'),
                    'k6' => new Value(NULL, 'null'),
                    'k7' => new Value(76, 'int'),
                    'k8' => new Value(array(
                        new Value('v6', 'string'),
                        new Value(array(
                            'k9' => new Value('v7', 'string'),
                        ), 'struct'),
                        new Value('v8', 'string'),
                        new Value('v9', 'string'),
                    ), 'array'),
                ), 'struct'),
            ), 'struct'));
        $this->assertEquals(array(
            'k1' => 'v1',
            'k2' => array(
                'v2',
                'v3',
                array(
                    'v4',
                    array(
                        'k3' => 'v5'
                        )
                    )
                ),
            'k4' => array(
                'k5' => array(),
                'k6' => NULL,
                'k7' => 76,
                'k8' => array(
                    'v6',
                    array(
                        'k9' => 'v7'
                    ),
                    'v8',
                    'v9'
                )
            ),
        ), $phpValue);
    }

    public function testConvertXmlrpcArgToPhpUndef() {
        $xmlrpcValue = $this->getMockBuilder('\PhpXmlRpc\Value')->disableOriginalConstructor()->setMethods(['kindOf'])->getMock();
        $xmlrpcValue->expects($this->once())->method('kindOf')->willReturn('undef');

        $phpValue = $this->protocol->convertXmlrpcArgToPhp($xmlrpcValue);
        $this->assertNUll($phpValue);
    }

    /* ----- tests of isAssociativeArray ------ */

    public function testIsAssociativeArray1() {
        $array = array('a', 'b', 'c');
        $isAssociative = $this->protocol->isAssociativeArray($array);
        $this->assertFalse($isAssociative);
    }

    public function testIsAssociativeArray2() {
        $array = array("0" => 'a', "1" => 'b', "2" => 'c');
        $isAssociative = $this->protocol->isAssociativeArray($array);
        $this->assertFalse($isAssociative);
    }

    public function testIsAssociativeArray3() {
        $array = array("1" => 'a', "0" => 'b', "2" => 'c');
        $isAssociative = $this->protocol->isAssociativeArray($array);
        $this->assertTrue($isAssociative);
    }

    public function testIsAssociativeArray4() {
        $array = array("a" => 'a', "b" => 'b', "c" => 'c');
        $isAssociative = $this->protocol->isAssociativeArray($array);
        $this->assertTrue($isAssociative);
    }

    public function testIsAssociativeArrayEmpty() {
        $array = array();
        $isAssociative = $this->protocol->isAssociativeArray($array);
        $this->assertFalse($isAssociative);
    }

    /* ----- tests of executeKeyword ------ */

    public function testExecuteKeyword() {
        $testKeywordStore = new TestExecKeywordStore();
        $this->protocol->init($testKeywordStore);

        $result = $this->protocol->executeKeyword('coolMethod', array('nice arg', 'beautiful arg', 'handsome arg'));
        $this->assertEquals('', $result['error']); // check error first: will contain the useful info to debug when test goes bad
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals('', $result['output']);
        $this->assertEquals('', $result['traceback']);
        $this->assertEquals('Call to: coolMethod(nice arg, beautiful arg, handsome arg)', $result['return']);
    }

    public function testExecuteKeywordCaptureStdout() {
        $testKeywordStore = new TestExecKeywordStoreStdout();
        $this->protocol->init($testKeywordStore);

        $result = $this->protocol->executeKeyword('coolMethod', array('nice arg', 'beautiful arg', 'handsome arg'));
        $this->assertEquals('', $result['error']); // check error first: will contain the useful info to debug when test goes bad
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals('Call to: coolMethod(nice arg, beautiful arg, handsome arg)', $result['output']);
        $this->assertEquals('', $result['traceback']);
        $this->assertEquals('', $result['return']);
    }

}

class SamplePhpClassToShareInXmlrpc {
    var $field1 = 'beginning';
    var $field2 = 'next';
    var $field3 = 'final';
    private $privateField = 'private';
    public $publicField = 'public';
    protected $protectedField = 'protected';
    static $staticField = 'static';
    function notAField() { }
}

class TestExecKeywordStore extends KeywordStore {
    public function execKeyword($keywordName, $keywordArgs) {
        return 'Call to: '.$keywordName.'('.implode(', ', $keywordArgs).')';
    }
}

class TestExecKeywordStoreStdout extends KeywordStore {
    public function execKeyword($keywordName, $keywordArgs) {
        echo('Call to: '.$keywordName.'('.implode(', ', $keywordArgs).')');
    }
}
