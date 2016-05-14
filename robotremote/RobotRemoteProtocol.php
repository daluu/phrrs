<?php

namespace PhpRobotRemoteServer;

use \PhpXmlRpc\Server;
use \PhpXmlRpc\Response;
use \PhpXmlRpc\Value;

ini_set('always_populate_raw_post_data', -1);
ini_set('date.timezone', 'Europe/Paris');

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
	  // Determine keyword return data type.
	  $type = gettype($keywordResult['return']);
	  $xmlrpcType = "string";
	  switch ($type) {
	    case "boolean":
	      $xmlrpcType = "boolean";
	      break;

	    case "integer":
	      $xmlrpcType = "int";
	      break;

	    case "double":
	      $xmlrpcType = "string";
	      break;

	    case "string":
	      $xmlrpcType = "string";
	      break;

	    case "array":
	      // Todo - encode each element in array to XML-RPC val type.
	      $xmlrpcType = "array";
	      break;

	    case "object": // ~struct
	      // Todo - encode individual member in object to XML-RPC val type.
	      $xmlrpcType = "struct";
	      break;

	    case "resource":
	      $xmlrpcType = "null";
	      break;

	    case "NULL":
	      $xmlrpcType = "null";
	      break;

	    case "unknown type":
	      $xmlrpcType = "null";
	      break;
	  }
	  $encoded = new Value(
	    array(
	      "return" => new Value($keywordResult['return'], $xmlrpcType),
	      "status" => new Value($keywordResult['status'], "string"),
	      "output" => new Value($keywordResult['output'], "string"),
	      "error" => new Value($keywordResult['error'], "string"),
	      "traceback" => new Value($keywordResult['traceback'], "string"),
	    ),
	    "struct");
	  return $encoded;
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
	    	$xmlrpcArgList[$i] = $xmlrpcMsg->getParam($i);
		}
		$keywordMethod = $xmlrpcArgList[0]->scalarVal();
		// Remove the keyword name from the argument list for the keyword.
		array_shift($xmlrpcArgList);
		$numArgs--;

		return array(
			'keywordMethod' => $keywordMethod,
			'numArgs' => $numArgs,
			'xmlrpcArgList' => $xmlrpcArgList,
		);
	}

	private function convertXmlrpcArgsToPhp($numArgs, $xmlrpcArgList) {
		// Convert argument list from XML-RPC format to PHP format.
		$argList = array();
		for ($i = 0; $i < $numArgs; $i++) {
	   		switch ($xmlrpcArgList[$i]->kindOf()) {
	      		case "scalar":
	        		$argList[$i] = $xmlrpcArgList[$i]->scalarVal();
		        break;

			    case "array":
	    		    // Handling simple case of array of scalars.
	        		// Todo - handle array of arrays & array of structs,
	        		// recursively or iteratively.
	        		$xmlrpcArraySize = $xmlrpcArgList[$i]->arraySize();
	        		$phpArray = array();
	        		for ($j = 0; $j < $xmlrpcArraySize; $j++) {
	          			$phpArray[$j] = $xmlrpcArgList[$i]->arrayMem($j)->scalarVal();
	        		}
	        		$argList[$i] = $phpArray;
		        break;

				case "struct":
	        		// Handling simple case of struct of scalars.
	        		// Todo - handle struct of arrays & struct of structs,
	        		// recursively or iteratively.
	        		$phpArray = array();
	        		$xmlrpcArgList[$i]->structreset();
	        		while (list($key, $val) = $xmlrpcArgList[$i]->structEach()) {
	          			$phpArray[$key] = $val->scalarVal();
	        		}
	        		$argList[$i] = $phpArray;
	        		break;

	      		case "undef":
	        		$argList[$i] = NULL;
	        		break;
		    }
	  	}
		return $argList;
	}

	private function executeKeyword($keywordMethod, $argList) {
		$keywordResult = array(
	    	'status' => 'PASS',
	    	'output' => '',
	    	'error' => '',
	    	'traceback' => '',
	    	'return' => '',
	  	);

		// execute keyword based on examples from http://en.wikipedia.org/wiki/Reflection_(computer_programming)
		// output will always be empty since we can't redirect echo's and print's in PHP...

	  	try {
	    	// Per Robot Framework remote library spec, all arguments will stored in an array
	    	// as the 2nd argument to XML-RPC method call, and first argument is keyword name
	    	// which we've parsed out of array, so then arguments should be $argList[0]
	    	$keywordArgs = $argList[0];
	    	$result = $this->keywordStore->execKeyword($keywordMethod, $keywordArgs);

	    	// using variable variables syntax
	    	//$library_instance = $this->keywordStore->getReflector();
	    	//$method = $keywordMethod;
	    	//using variable argument list version
	    	//$result = $library_instance->$method($argList[0]);

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

	// TODO split this baby-monster method
	private function run_keyword($xmlrpcMsg) {
		try {
			$parsedXmlrpcMsg = $this->parseXmlrpcMsg($xmlrpcMsg);
			$keywordMethod = $parsedXmlrpcMsg['keywordMethod'];
			$numArgs = $parsedXmlrpcMsg['numArgs'];
		 	$xmlrpcArgList = $parsedXmlrpcMsg['xmlrpcArgList'];

			$argList = $this->convertXmlrpcArgsToPhp($numArgs, $xmlrpcArgList);

			$keywordResult = $this->executeKeyword($keywordMethod, $argList);

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
