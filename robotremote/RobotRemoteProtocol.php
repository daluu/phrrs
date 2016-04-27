<?php

namespace PhpRobotRemoteServer;

use \PhpXmlRpc\Server;
use \PhpXmlRpc\Response;
use \PhpXmlRpc\Value;

ini_set('always_populate_raw_post_data', -1);
ini_set('date.timezone', 'Europe/Paris');

define('ENABLE_STOP_SVR', FALSE);

class RobotRemoteProtocol {

    private static $instance = NULL;

	private $keywords;
	private $svr;

    public function getInstance() {
    	if (is_null(self::$instance)) {
    		self::$instance = new RobotRemoteProtocol();
    	}
        return self::$instance;
    }

	public function init($keywords) {
		$this->keywords = $keywords;
		$this->svr = new \PhpXmlRpc\Server(
			$this->getXmlRpcDispatchMap(),
			false/*do NOT start server*/
		);
	}

	public function exec($data) {
		return $this->svr->service($data, true);
	}

	private function getXmlRpcDispatchMap() {
		return array(
		  // XML-RPC function/method name.
		  'get_keyword_names' => array(
		    // PHP function name of the XML-RPC function/method.
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_get_keyword_names',
		  ),
		  'run_keyword'  => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_run_keyword',
		  ),
		  'get_keyword_arguments'  => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_get_keyword_arguments',
		  ),
		  'get_keyword_documentation'  => array(
		    'function' => '\PhpRobotRemoteServer\RobotRemoteProtocol::_get_keyword_documentation',
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

	/**
	 * Helper function.
	 */
	private function xmlrpc_encode_keyword_result($keyword_result) {
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

	/**
	 * Helper function.
	 */
	private function get_keyword_names($xmlrpcmsg) {
	  $keywordNames = $this->keywords->getKeywordNames();
	  $keywordNameValues = new Value(array(), "array");
	  foreach ($keywordNames as $keywordName) {
	    $keywordNameValues->addScalar($keywordName);
	  }
	  // $keywordNameValues->addScalar("stop_remote_server");
	  $xmlrpcresponse = new Response($keywordNameValues);
	  return $xmlrpcresponse;
	}

	/**
	 * Helper function.
	 */
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

	  if ($keyword_method == "stop_remote_server") {
	    if (!ENABLE_STOP_SVR) {
	      $keyword_result['output'] = "NOTE: remote server not configured to allow remote shutdowns. Your request has been ignored.";
	    }
	    else {
	      // $keyword_result['output'] = "NOTE: remote server shutting/shut down.";
	      $keyword_result['output'] = "NOTE: remote server shutdown currently not implemented, so your request has been ignored.";

	      // Since XML-RPC over PHP is served via a web server (Apache, IIS, etc.)
	      // it can't be shut down on its own
	      // therefore, we can shut down by shutting down the web server
	      // (via shell commands, kill process, WMI, web service call, etc.)

	      // Do we want to allow/do that?

	      // If yes, in any case,
	      // this section here is placeholder for you to add in that code
	      // and swap the output message as appropriately above.
	    }
	    $xmlrpcresponse = new Response(xmlrpc_encode_keyword_result($keyword_result));
	    return $xmlrpcresponse;
	  } // else all other keywords...

	  // execute keyword based on examples from http://en.wikipedia.org/wiki/Reflection_(computer_programming)
	  // output will always be empty since we can't redirect echo's and print's in PHP...

	  try {
	    // With reflection.
	    $reflector = $this->keywords->getReflector();
	    $library_instance = $reflector->newInstance();
	    $keyword_executor = $reflector->getMethod($keyword_method);
	    // Per Robot Framework remote library spec, all arguments will stored in an array
	    // as the 2nd argument to XML-RPC method call, and first argument is keyword name
	    // which we've parsed out of array, so then arguments should be $arg_list[0]
	    $result = $keyword_executor->invokeArgs($library_instance,$arg_list[0]);

	    // using variable variables syntax
	    //$library_instance = $this->keywords->getReflector()r;
	    //$method = $keyword_method;
	    //using variable argument list version
	    //$result = $library_instance->$method($arg_list[0]);

	    if (!is_null($result)) {
	      $keyword_result['return'] = $result;
	    }
	    $xmlrpcresponse = new Response(xmlrpc_encode_keyword_result($keyword_result));
	    return $xmlrpcresponse;
	  }
	  catch(Exception $e){
	    $keyword_result['return']     = "";
	    $keyword_result['status']     = "FAIL";
	    $keyword_result['output']     = "";
	    $keyword_result['error']       = $e->getMessage();
	    $keyword_result['traceback']   = $e->getTraceAsString();
	    $xmlrpcresponse = new Response(xmlrpc_encode_keyword_result($keyword_result));
	    return $xmlrpcresponse;
	  }
	}

	/**
	 * Helper function.
	 */
	private function get_keyword_arguments($xmlrpcmsg) {
	  $keyword_name = $xmlrpcmsg->getParam(0)->scalarVal();
	  $reflector = $this->keywords->getReflector();
	  $keyword = $reflector->getMethod($keyword_name);
	  // Array of ReflectionParameter objects.
	  $kw_params = $keyword->getParameters();
	  $num_args = count($kw_params);
	  $keyword_arguments = new Value(array(), "array");
	  for ($i = 0; $i < $num_args; $i++) {
	    $keyword_arguments->addScalar($kw_params[$i]->name);
	  }
	  $xmlrpcresponse = new Response($keyword_arguments);
	  return $xmlrpcresponse;
	}

	/**
	 * Helper function.
	 */
	private function get_keyword_documentation($xmlrpcmsg) {
	  $keyword_name = $xmlrpcmsg->getParam(0)->scalarVal();
	  $reflector = $this->keywords->getReflector();
	  $keyword = $reflector->getMethod($keyword_name);
	  $phpkwdoc = $keyword->getDocComment();

	  // Clean up formatting of documentation
	  // (e.g. remove CRLF, tabs, and the PHP doc comment identifiers "/**...*/")
	  $phpkwdoc = preg_replace("/[\010]/", "\n", $phpkwdoc);
	  $phpkwdoc = preg_replace("/[\013]/", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\s{2,}/", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\/\*\*/", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\*\//", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\*\s/", "", $phpkwdoc);

	  $keyword_documentation = new Value($phpkwdoc, "string");
	  $xmlrpcresponse = new Response($keyword_documentation);
	  return $xmlrpcresponse;
	}

}
