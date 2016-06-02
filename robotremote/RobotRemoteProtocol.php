<?php

namespace PhpRobotRemoteServer;

use \PhpXmlRpc\Server;
use \PhpXmlRpc\Response;
use \PhpXmlRpc\Value;

// TODO are these finally needed? When run as CLI...
//ini_set('always_populate_raw_post_data', -1);
//ini_set('date.timezone', 'Europe/Paris'); // TODO maybe set a better timezone??? Or avoid entirely to force any timezone?

class RobotRemoteProtocol {

    private static $rpcCallInstance = NULL;

	private $keywordStore;
	private $xmlrpcProcessor;
	private $robotRemoteServer;

    private static function getRpcCallInstance() {
        return self::$rpcCallInstance;
    }

	public function init($keywordStore) {
		self::$rpcCallInstance = $this;

		$this->keywordStore = $keywordStore;

		$this->xmlrpcProcessor = new \PhpXmlRpc\Server(
			$this->getXmlRpcDispatchMap(),
			false/*do NOT start server*/
		);
	}

	public function setRobotRemoteServer($robotRemoteServer) {
		$this->robotRemoteServer = $robotRemoteServer;
		$this->keywordStore->setStoppableServer($robotRemoteServer);
	}

	public function exec($data) {
		return $this->xmlrpcProcessor->service($data, true);
	}

	private function getXmlRpcDispatchMap() {
		return array(
		  // XML-RPC function/method name.
		  'get_keyword_names' => array(
		    // PHP function name of the XML-RPC function/method.
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_get_keyword_names',
		  ),
		  'run_keyword' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_run_keyword',
		  ),
		  'get_keyword_arguments' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_get_keyword_arguments',
		  ),
		  'get_keyword_documentation' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_get_keyword_documentation',
		  ),
		  'stop_remote_server' => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_stop_remote_server',
		  ),
		);
	}

	static function _get_keyword_names($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->get_keyword_names($xmlrpcMsg);
	}

	static function _run_keyword($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->run_keyword($xmlrpcMsg);
	}

	static function _get_keyword_arguments($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->get_keyword_arguments($xmlrpcMsg);
	}

	static function _get_keyword_documentation($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->get_keyword_documentation($xmlrpcMsg);
	}

	static function _stop_remote_server($xmlrpcMsg) {
		return RobotRemoteProtocol::getRpcCallInstance()->stop_remote_server($xmlrpcMsg);
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
		$xmlrpcType = 'string'; // TODO make it a server failure if can't find the type
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
		    	// Unable to do anything useful with this type -- TODO issue a warning
			    $xmlrpcType = 'null';
	    		break;

		    case 'NULL':
			    $xmlrpcType = 'null'; // TODO or an empty array? What would be the most useful?
	    		break;

		    case 'unknown type':
		    	// Unable to do anything useful with this type -- TODO issue a warning
			    $xmlrpcType = 'null';
	      		break;
		}

		return new Value($encodedValue, $xmlrpcType);
	}

	function isAssociativeArray($arr) {
    	return count($arr)>0 && array_keys($arr) !== range(0, count($arr) - 1);
	}


	private function get_keyword_names($xmlrpcMsg) {
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
        		// Handling simple case of struct of scalars.
        		// Todo - handle struct of arrays & struct of structs,
        		// recursively or iteratively.
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

	private function executeKeyword($keywordMethod, $phpArgList) {
		$keywordResult = array(
	    	'status' => 'PASS',
	    	'output' => '',
	    	'error' => '',
	    	'traceback' => '',
	    	'return' => '',
	  	);

		// execute keyword based on examples from http://en.wikipedia.org/wiki/Reflection_(computer_programming)
		// output will always be empty since we can't redirect echo's and print's in PHP...
		// TODO I'm not so sure! Why not redirect in a file and read the file? Or something else...! We'll work on this later.

	  	try {
	    	// Per Robot Framework remote library spec, all arguments will stored in an array
	    	// as the 2nd argument to XML-RPC method call, and first argument is keyword name
	    	// which we've parsed out of array, so then arguments should be $phpArgList[0]
	    	$keywordArgs = $phpArgList[0];
	    	$result = $this->keywordStore->execKeyword($keywordMethod, $keywordArgs);

	    	// using variable variables syntax
	    	//$library_instance = $this->keywordStore->getReflector();
	    	//$method = $keywordMethod;
	    	//using variable argument list version
	    	//$result = $library_instance->$method($phpArgList[0]);

	    	if (!is_null($result)) {
	      		$keywordResult['return'] = $result;
	    	}
	  	} catch(\Exception $e) {
		    $keywordResult['return']    = "";
	   		$keywordResult['status']    = "FAIL";
	    	$keywordResult['output']    = "";
	   		$keywordResult['error']     = $e->getMessage();
	    	$keywordResult['traceback'] = $e->getTraceAsString();
	  	}

		return $keywordResult;
	}

	private function run_keyword($xmlrpcMsg) {
		try {
			$parsedXmlrpcMsg = $this->parseXmlrpcMsg($xmlrpcMsg);
			$keywordMethod = $parsedXmlrpcMsg['keywordMethod'];
		 	$xmlrpcArgList = $parsedXmlrpcMsg['xmlrpcArgList'];

			$phpArgList = $this->convertXmlrpcArgListToPhp($xmlrpcArgList);

			$keywordResult = $this->executeKeyword($keywordMethod, $phpArgList);

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

	private function get_keyword_arguments($xmlrpcMsg) {
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

	private function get_keyword_documentation($xmlrpcMsg) {
		$keywordName = $xmlrpcMsg->getParam(0)->scalarVal();
		$phpkwdoc = $this->keywordStore->getKeywordDocumentation($keywordName);

		$keywordDocumentation = new Value($phpkwdoc, "string");
		$xmlrpcResponse = new Response($keywordDocumentation);
		return $xmlrpcResponse;
	}

	private function stop_remote_server($xmlrpcMsg) {
		$this->robotRemoteServer->stop();

		$serverStopped = new Value(TRUE, "boolean");
		$xmlrpcResponse = new Response($serverStopped);
		return $xmlrpcResponse;
	}

}
