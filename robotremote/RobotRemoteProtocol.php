<?php

namespace PhpRobotRemoteServer;

use \PhpXmlRpc\Server;
use \PhpXmlRpc\Response;
use \PhpXmlRpc\Value;

ini_set('always_populate_raw_post_data', -1);
ini_set('date.timezone', 'Europe/Paris');

class RobotRemoteProtocol {

    private static $instance = NULL;

	private $keywordStore;
	private $xmlrpcProcessor;
	private $robotRemoteServer;

    static public function getInstance() {
    	if (is_null(self::$instance)) {
    		self::$instance = new RobotRemoteProtocol();
    	}
        return self::$instance;
    }

	public function init($keywordStore) {
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

	static function _get_keyword_names($xmlrpcmsg) {
		return RobotRemoteProtocol::getInstance()->get_keyword_names($xmlrpcmsg);
	}

	static function _run_keyword($xmlrpcmsg) {
		return RobotRemoteProtocol::getInstance()->run_keyword($xmlrpcmsg);
	}

	static function _get_keyword_arguments($xmlrpcmsg) {
		return RobotRemoteProtocol::getInstance()->get_keyword_arguments($xmlrpcmsg);
	}

	static function _get_keyword_documentation($xmlrpcmsg) {
		return RobotRemoteProtocol::getInstance()->get_keyword_documentation($xmlrpcmsg);
	}

	static function _stop_remote_server($xmlrpcmsg) {
		return RobotRemoteProtocol::getInstance()->stop_remote_server($xmlrpcmsg);
	}

	private function xmlrpcEncodeKeywordResult($keyword_result) {
	  // Determine keyword return data type.
	  $type = gettype($keyword_result['return']);
	  $xmlrpc_type = "string";
	  switch ($type) {
	    case "boolean":
	      $xmlrpc_type = "boolean";
	      break;

	    case "integer":
	      $xmlrpc_type = "int";
	      break;

	    case "double":
	      $xmlrpc_type = "string";
	      break;

	    case "string":
	      $xmlrpc_type = "string";
	      break;

	    case "array":
	      // Todo - encode each element in array to XML-RPC val type.
	      $xmlrpc_type = "array";
	      break;

	    case "object": // ~struct
	      // Todo - encode individual member in object to XML-RPC val type.
	      $xmlrpc_type = "struct";
	      break;

	    case "resource":
	      $xmlrpc_type = "null";
	      break;

	    case "NULL":
	      $xmlrpc_type = "null";
	      break;

	    case "unknown type":
	      $xmlrpc_type = "null";
	      break;
	  }
	  $encoded = new Value(
	    array(
	      "return" => new Value($keyword_result['return'], $xmlrpc_type),
	      "status" => new Value($keyword_result['status'], "string"),
	      "output" => new Value($keyword_result['output'], "string"),
	      "error" => new Value($keyword_result['error'], "string"),
	      "traceback" => new Value($keyword_result['traceback'], "string"),
	    ),
	    "struct");
	  return $encoded;
	}

	private function get_keyword_names($xmlrpcmsg) {
		$keywordNames = $this->keywordStore->getKeywordNames();

		$keywordNameValues = new Value(array(), "array");
		foreach ($keywordNames as $keywordName) {
	    	$keywordNameValues->addScalar($keywordName);
		}
		$xmlrpcResponse = new Response($keywordNameValues);
		return $xmlrpcResponse;
	}

	// TODO split this baby-monster method
	private function run_keyword($xmlrpcmsg) {
	  $numargs = $xmlrpcmsg->getNumParams();
	  $xml_rpc_arg_list = array();
	  $arg_list = array();
	  for ($i = 0; $i < $numargs; $i++) {
	    $xml_rpc_arg_list[$i] = $xmlrpcmsg->getParam($i);
	  }
	  $keyword_method = $xml_rpc_arg_list[0]->scalarVal();
	  // Remove the keyword name from the argument list for the keyword.
	  array_shift($xml_rpc_arg_list);
	  $numargs--;

	  // Convert argument list from XML-RPC format to PHP format.
	  for ($i = 0; $i < $numargs; $i++) {
	    switch ($xml_rpc_arg_list[$i]->kindOf()) {
	      case "scalar":
	        $arg_list[$i] = $xml_rpc_arg_list[$i]->scalarVal();
	        break;

	      case "array":
	        // Handling simple case of array of scalars.
	        // Todo - handle array of arrays & array of structs,
	        // recursively or iteratively.
	        $xml_rpc_array_size = $xml_rpc_arg_list[$i]->arraySize();
	        $php_array = array();
	        for ($j = 0; $j < $xml_rpc_array_size; $j++) {
	          $php_array[$j] = $xml_rpc_arg_list[$i]->arrayMem($j)->scalarVal();
	        }
	        $arg_list[$i] = $php_array;
	        break;

	      case "struct":
	        // Handling simple case of struct of scalars.
	        // Todo - handle struct of arrays & struct of structs,
	        // recursively or iteratively.
	        $php_array = array();
	        $xml_rpc_arg_list[$i]->structreset();
	        while (list($key, $val) = $xml_rpc_arg_list[$i]->structEach()) {
	          $php_array[$key] = $val->scalarVal();
	        }
	        $arg_list[$i] = $php_array;
	        break;

	      case "undef":
	        $arg_list[$i] = NULL;
	        break;
	    }
	  }

	  $keyword_result = array(
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
	    // which we've parsed out of array, so then arguments should be $arg_list[0]
	    $keywordArgs = $arg_list[0];
	    $result = $this->keywordStore->execKeyword($keyword_method, $keywordArgs);

	    // using variable variables syntax
	    //$library_instance = $this->keywordStore->getReflector()r;
	    //$method = $keyword_method;
	    //using variable argument list version
	    //$result = $library_instance->$method($arg_list[0]);

	    if (!is_null($result)) {
	      $keyword_result['return'] = $result;
	    }
	    $xmlrpcResponse = new Response($this->xmlrpcEncodeKeywordResult($keyword_result));
	    return $xmlrpcResponse;
	  }
	  catch(Exception $e){
	    $keyword_result['return']    = "";
	    $keyword_result['status']    = "FAIL";
	    $keyword_result['output']    = "";
	    $keyword_result['error']     = $e->getMessage();
	    $keyword_result['traceback'] = $e->getTraceAsString();
	    $xmlrpcResponse = new Response($this->xmlrpcEncodeKeywordResult($keyword_result));
	    return $xmlrpcResponse;
	  }
	}

	private function get_keyword_arguments($xmlrpcmsg) {
		$keywordName = $xmlrpcmsg->getParam(0)->scalarVal();
		// Array of ReflectionParameter objects.
		$keywordArgumentNames = $this->keywordStore->getKeywordArguments($keywordName);

		$keywordArgumentNameValues = new Value(array(), "array");
		foreach ($keywordArgumentNames as $keywordArgumentName) {
			$keywordArgumentNameValues->addScalar($keywordArgumentName);
		}
		$xmlrpcResponse = new Response($keywordArgumentNameValues);
		return $xmlrpcResponse;
	}

	private function get_keyword_documentation($xmlrpcmsg) {
		$keywordName = $xmlrpcmsg->getParam(0)->scalarVal();
		$phpkwdoc = $this->keywordStore->getKeywordDocumentation($keywordName);

		$keywordDocumentation = new Value($phpkwdoc, "string");
		$xmlrpcResponse = new Response($keywordDocumentation);
		return $xmlrpcResponse;
	}

	private function stop_remote_server($xmlrpcmsg) {
		$this->robotRemoteServer->stop();

		$serverStopped = new Value(TRUE, "boolean");
		$xmlrpcResponse = new Response($serverStopped);
		return $xmlrpcResponse;
	}

}
