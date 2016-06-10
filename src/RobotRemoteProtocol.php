<?php

namespace PhpRobotRemoteServer;

use \PhpXmlRpc\Server;
use \PhpXmlRpc\Response;
use \PhpXmlRpc\Value;

class RobotRemoteProtocol {

    private static $rpcCallInstance = NULL;

	private $keywordStore;
	private $xmlrpcProcessor;
	private $robotRemoteServer;
	private $verbose;

    private static function getRpcCallInstance() {
        return self::$rpcCallInstance;
    }

    public function __construct($verbose = TRUE) {
    	$this->verbose = $verbose;
    }

	public function init(KeywordStore $keywordStore) {
		self::$rpcCallInstance = $this;

		$this->keywordStore = $keywordStore;

		$this->xmlrpcProcessor = new \PhpXmlRpc\Server(
			$this->getXmlRpcDispatchMap(),
			false/*do NOT start server*/
		);
	}

	public function setRobotRemoteServer($robotRemoteServer) {
		$this->robotRemoteServer = $robotRemoteServer;
		$this->keywordStore->addStopRemoteServerKeyword(__FILE__, __CLASS__);
	}

	public function exec($data) {
		return $this->xmlrpcProcessor->service($data, true);
	}

	private function getXmlRpcDispatchMap() {
		return array(
		  // XML-RPC function/method name.
		  'get_keyword_names' => array(
		    // PHP function name of the XML-RPC function/method.
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::get_keyword_names',
		  ),
		  'run_keyword' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::run_keyword',
		  ),
		  'get_keyword_arguments' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::get_keyword_arguments',
		  ),
		  'get_keyword_documentation' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::get_keyword_documentation',
		  ),
		  'stop_remote_server' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::stop_remote_server',
		  ),
		);
	}

	static function get_keyword_names($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->getKeywordNames($xmlrpcMsg);
	}

	static function run_keyword($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->runKeyword($xmlrpcMsg);
	}

	static function get_keyword_arguments($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->getKeywordArguments($xmlrpcMsg);
	}

	static function get_keyword_documentation($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->getKeywordDocumentation($xmlrpcMsg);
	}

	static function stop_remote_server($xmlrpcMsg = NULL) {
		return RobotRemoteProtocol::getRpcCallInstance()->stopRemoteServer();
	}

	private function xmlrpcEncodeKeywordResult($keywordResult) {
		$encodedReturn = $this->xmlrpcEncodeKeywordResultValue($keywordResult['return']);
		$encoded = new Value(
	    	array(
	    		'return' => $encodedReturn,
	    		'status' => new Value($keywordResult['status'], 'string'),
	    		'output' => new Value($keywordResult['output'], 'string'),
	    		'error' => new Value($keywordResult['error'], 'string'),
	    		'traceback' => new Value($keywordResult['traceback'], 'string'),
		    ),
	    	'struct');
		return $encoded;
	}

	function xmlrpcEncodeKeywordResultValue($keywordResultValue) {
		/*
		 * Determine keyword return data type, then convert to one of possible XML-RPC types:
		 * i4, int, boolean, string, double, dateTime.iso8601, base64, array, struct, null
		 */
		$type = gettype($keywordResultValue);
		$encodedValue = $keywordResultValue;
		$xmlrpcType = NULL;
		switch ($type) {
	    	case 'boolean':
	    		$xmlrpcType = 'boolean';
	    		break;

		    case 'integer':
			    $xmlrpcType = 'int';
	    		break;

		    case 'double':
			    $xmlrpcType = 'string'; // TODO why not 'double'?
	    		break;

		    case 'string':
			    $xmlrpcType = 'string';
	    		break;

		    case 'array':
		    	$encodedValue = array();
		    	if ($this->isAssociativeArray($keywordResultValue)) {
			    	foreach ($keywordResultValue as $elemKey => $elemValue) {
			    		$encodedValue[$elemKey] = $this->xmlrpcEncodeKeywordResultValue($elemValue);
			    	}
			    	$xmlrpcType = 'struct';
		    	} else {
			    	foreach ($keywordResultValue as $elem) {
			    		$encodedValue[] = $this->xmlrpcEncodeKeywordResultValue($elem);
			    	}
			    	$xmlrpcType = 'array';
		    	}
		      break;

		    case 'object': // treat it as an associative array
		    	$encodedValue = array();
		    	$asAnAssociativeArray = get_object_vars($keywordResultValue);
		    	foreach ($asAnAssociativeArray as $elemKey => $elemValue) {
		    		$encodedValue[$elemKey] = $this->xmlrpcEncodeKeywordResultValue($elemValue);
		    	}
		    	$xmlrpcType = 'struct';
			    break;

		    case 'resource':
		    	if ($this->verbose) {
		    		echo("WARNING: processing a PHP value of type 'resource', unable to pass it back via XML-RPC: ".$keywordResultValue."\n");
			    }
			    $xmlrpcType = 'null';
	    		break;

		    case 'NULL':
			    $xmlrpcType = 'null';
	    		break;

		    case 'unknown type':
		    	if ($this->verbose) {
		   	 		echo("WARNING: processing a PHP value of type 'unknown type', unable to pass it back via XML-RPC: ".$keywordResultValue."\n");
		   		}
			    $xmlrpcType = 'null';
	      		break;

	      	default:
	      		throw new \Exception('Unable to resolve type of keyword result value: '.$keywordResultValue);
		}

		return new Value($encodedValue, $xmlrpcType);
	}

	function isAssociativeArray($arr) {
    	return count($arr)>0 && array_keys($arr) !== range(0, count($arr) - 1);
	}

	private function getKeywordNames($xmlrpcMsg) {
		$keywordNames = $this->keywordStore->getKeywordNames();

		$keywordNameValues = new Value(array(), "array");
		foreach ($keywordNames as $keywordName) {
	    	$keywordNameValues->addScalar($keywordName);
		}
		$xmlrpcResponse = new Response($keywordNameValues);
		return $xmlrpcResponse;
	}

	private function parseXmlrpcMsg($xmlrpcMsg) {
		$numArgs = $xmlrpcMsg->getNumParams();
		$xmlrpcArgList = array();
		for ($i = 0; $i < $numArgs; $i++) {
	    	$xmlrpcArgList[] = $xmlrpcMsg->getParam($i);
		}
		$keywordMethod = $xmlrpcArgList[0]->scalarVal();
		// Remove the keyword name from the argument list for the keyword.
		array_shift($xmlrpcArgList);

		return array(
			'keywordMethod' => $keywordMethod,
			'xmlrpcArgList' => $xmlrpcArgList,
		);
	}

	private function convertXmlrpcArgListToPhp($xmlrpcArgList) {
		// Convert argument list from XML-RPC format to PHP format.
		$phpArgList = array();
		foreach ($xmlrpcArgList as $xmlrpcArg) {
		    $phpArgList[] = $this->convertXmlrpcArgToPhp($xmlrpcArg);
	  	}
		return $phpArgList;
	}

	function convertXmlrpcArgToPhp($xmlrpcArg) {
		$phpArg = NULL;

   		switch ($xmlrpcArg->kindOf()) {
      		case 'scalar':
        		$phpArg = $xmlrpcArg->scalarVal();
		        break;

		    case 'array':
        		$phpArray = array();
        		foreach ($xmlrpcArg as $xmlrpcValue) {
          			$phpArray[] = $this->convertXmlrpcArgToPhp($xmlrpcValue);
        		}
        		$phpArg = $phpArray;
		        break;

			case 'struct':
        		$phpArray = array();
        		foreach ($xmlrpcArg as $key => $xmlrpcValue) {
          			$phpArray[$key] = $this->convertXmlrpcArgToPhp($xmlrpcValue);
        		}
        		$phpArg = $phpArray;
        		break;

      		case 'undef':
        		$phpArg = NULL;
        		break;
	    }

	    return $phpArg;
	}

	function executeKeyword($keywordMethod, $keywordArgs) {
		$keywordResult = array();

		// Code to capture stdout comes from: http://stackoverflow.com/questions/139474/how-can-i-capture-the-result-of-var-dump-to-a-string
  		ob_start();
	  	try {
	    	$result = $this->keywordStore->execKeyword($keywordMethod, $keywordArgs);
	    	if (is_null($result)) {
	      		$result = '';
	    	}

	    	// keyword execution success case
      		$keywordResult['status'] = 'PASS';
      		$keywordResult['return'] = $result;
      		$keywordResult['error'] = '';
      		$keywordResult['traceback'] = '';
	  	} catch(\Exception $e) {
	    	// keyword execution failure case
	   		$keywordResult['status']    = 'FAIL';
		    $keywordResult['return']    = '';
	   		$keywordResult['error']     = $e->getMessage();
	    	$keywordResult['traceback'] = $e->getTraceAsString();
	  	}
    	$output = ob_get_clean();
    	$keywordResult['output'] = $output;

		return $keywordResult;
	}

	private function runKeyword($xmlrpcMsg) {
		try {
			$parsedXmlrpcMsg = $this->parseXmlrpcMsg($xmlrpcMsg);
			$keywordMethod = $parsedXmlrpcMsg['keywordMethod'];
		 	$xmlrpcArgList = $parsedXmlrpcMsg['xmlrpcArgList'];

			$phpArgList = $this->convertXmlrpcArgListToPhp($xmlrpcArgList);

	    	// Per Robot Framework remote library spec, all arguments will stored in an array
	    	// as the 2nd argument to XML-RPC method call, and first argument is keyword name
	    	// which we've parsed out of array, so then arguments should be $phpArgList[0]
	    	$keywordArgs = $phpArgList[0];
			$keywordResult = $this->executeKeyword($keywordMethod, $keywordArgs);

	    	$xmlrpcResponse = new Response($this->xmlrpcEncodeKeywordResult($keywordResult));
	    	return $xmlrpcResponse;
    	} catch (\Exception $e) {
    		echo('
-----------------------------------------------------
ROBOT FRAMEWORK REMOTE SERVER KEYWORD EXECUTION ERROR
-----------------------------------------------------
'.$e->getMessage().'
-----------------------------------------------------
'.$e->getTraceAsString().'
-----------------------------------------------------
          ~~~~~~~~~~~~~~ END ~~~~~~~~~~~~~~
-----------------------------------------------------
');
    	}
	}

	private function getKeywordArguments($xmlrpcMsg) {
		$keywordName = $xmlrpcMsg->getParam(0)->scalarVal();
		// Array of ReflectionParameter objects.
		$keywordArgumentNames = $this->keywordStore->getKeywordArguments($keywordName);

		$keywordArgumentNameValues = new Value(array(), "array");
		foreach ($keywordArgumentNames as $keywordArgumentName) {
			$keywordArgumentNameValues->addScalar($keywordArgumentName);
		}
		$xmlrpcResponse = new Response($keywordArgumentNameValues);
		return $xmlrpcResponse;
	}

	private function getKeywordDocumentation($xmlrpcMsg) {
		$keywordName = $xmlrpcMsg->getParam(0)->scalarVal();
		$phpkwdoc = $this->keywordStore->getKeywordDocumentation($keywordName);

		$keywordDocumentation = new Value($phpkwdoc, "string");
		$xmlrpcResponse = new Response($keywordDocumentation);
		return $xmlrpcResponse;
	}

	private function stopRemoteServer() {
		$this->robotRemoteServer->stop();

		$serverStopped = new Value(TRUE, "boolean");
		$xmlrpcResponse = new Response($serverStopped);
		return $xmlrpcResponse;
	}

}
