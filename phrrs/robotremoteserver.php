<?php
################################################################################
# PHP generic remote library server for Robot Framework
# From https://github.com/daluu/phrrs
# Licensed under Apache License 2.0
# http://www.apache.org/licenses/LICENSE-2.0
# @author David Luu
################################################################################

require_once "xmlrpclib/xmlrpc.inc";     // From http://phpxmlrpc.sourceforge.net/
require_once "xmlrpclib/xmlrpcs.inc";    // From http://phpxmlrpc.sourceforge.net/

### Config section START ###

//fill in path to your PHP class file(s) to be used as Robot Framework keyword library below
#require_once 'pathToYourLibrary.php';
require_once 'libraries/example_library.php';
//add additional require_once statements as needed

#define('LIBRARY_NAME','put PHP class name in here that will be used as Robot Framework keyword library');
define('LIBRARY_NAME', 'ExampleLibrary');
define('ENABLE_STOP_SVR', FALSE);

ini_set('always_populate_raw_post_data', -1);
ini_set('date.timezone', 'Europe/Paris');

// alternatively, instead of using constants above, you could retrofit/modify the PHP code here to take in
// the needed values via HTTP GET query string parameters or read from a INI/config file or database query

### Config section END ###

/**
 * Helper function.
 */
function xmlrpc_encode_keyword_result($keyword_result) {
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

    case "object": //~struct
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
  $encoded = new xmlrpcval(
    array(
      "return" 		=> new xmlrpcval($keyword_result['return'], $xmlrpc_type),
      "status" 		=> new xmlrpcval($keyword_result['status'], "string"),
      "output" 		=> new xmlrpcval($keyword_result['output'], "string"),
      "error" 		=> new xmlrpcval($keyword_result['error'], "string"),
      "traceback" => new xmlrpcval($keyword_result['traceback'], "string"),
    ),
    "struct");
  return $encoded;
}

/**
 * Helper function.
 */
function get_keyword_names($xmlrpcmsg) {
  $reflector = new ReflectionClass(LIBRARY_NAME);
  $keywords = $reflector->getMethods();
  $num_keywords = count($keywords);
  $keyword_names = new xmlrpcval(array(), "array");
  for ($i = 0; $i < $num_keywords; $i++) {
    $keyword_names->addScalar($keywords[$i]->name);
  }
  // $keyword_names->addScalar("stop_remote_server");
  $xmlrpcresponse = new xmlrpcresp($keyword_names);
  return $xmlrpcresponse;
}

/**
 * Helper function.
 */
function run_keyword($xmlrpcmsg) {
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
        // Todo - handle array of arrays & array of structs, recursively or iteratively.
        $xml_rpc_array_size =  $xml_rpc_arg_list[$i]->arraySize();
        $php_array = array();
        for ($j = 0; $j < $xml_rpc_array_size; $j++) {
          $php_array[$j] = $xml_rpc_arg_list[$i]->arrayMem($j)->scalarVal();
        }
        $arg_list[$i] = $php_array;
        break;

      case "struct":
        // Handling simple case of struct of scalars.
        // Todo - handle struct of arrays & struct of structs, recursively or iteratively.
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
    'status'    => 'PASS',
    'output'    => '',
    'error'     => '',
    'traceback' => '',
    'return'    => '',
  );

  if ($keyword_method == "stop_remote_server") {
    if (!ENABLE_STOP_SVR) {
      $keyword_result['output'] = "NOTE: remote server not configured to allow remote shutdowns. Your request has been ignored.";
    }
    else {
      //$keyword_result['output'] = "NOTE: remote server shutting/shut down.";
      $keyword_result['output'] = "NOTE: remote server shutdown currently not implemented, so your request has been ignored.";

      //since XML-RPC over PHP is served via a web server (Apache, IIS, etc.) it can't be shut down on its own
      //therefore, we can shut down by shutting down the web server
      //(via shell commands, kill process, WMI, web service call, etc.)

      //Do we want to allow/do that?

      //If yes, in any case, this section here is placeholder for you to add in that code
      //and swap the output message as appropriately above.
    }
    $xmlrpcresponse = new xmlrpcresp(xmlrpc_encode_keyword_result($keyword_result));
    return $xmlrpcresponse;
  }//else all other keywords...

  //execute keyword based on examples from http://en.wikipedia.org/wiki/Reflection_(computer_programming)
  //output will always be empty since we can't redirect echo's and print's in PHP...

  try {
    // with reflection
    $reflector = new ReflectionClass(LIBRARY_NAME);
    $library_instance = $reflector->newInstance();
    $keyword_executor = $reflector->getMethod($keyword_method);
    //per Robot Framework remote library spec, all arguments will stored in an array
    //as the 2nd argument to XML-RPC method call, and first argument is keyword name
    //which we've parsed out of array, so then arguments should be $arg_list[0]
    $result = $keyword_executor->invokeArgs($library_instance,$arg_list[0]);

    // using variable variables syntax
    //$className = LIBRARY_NAME;
    //$library_instance = new $className();
    //$method = $keyword_method;
    //using variable argument list version
    //$result = $library_instance->$method($arg_list[0]);

    if (!is_null($result)) {
      $keyword_result['return'] = $result;
    }
    $xmlrpcresponse = new xmlrpcresp(xmlrpc_encode_keyword_result($keyword_result));
    return $xmlrpcresponse;
  }
  catch (Exception $e) {
    $keyword_result['return']     = "";
    $keyword_result['status']     = "FAIL";
    $keyword_result['output']     = "";
    $keyword_result['error']      = $e->getMessage();
    $keyword_result['traceback']  = $e->getTraceAsString();
    $xmlrpcresponse = new xmlrpcresp(xmlrpc_encode_keyword_result($keyword_result));
    return $xmlrpcresponse;
  }
}

function get_keyword_arguments($xmlrpcmsg) {
  $keyword_name = $xmlrpcmsg->getParam(0)->scalarVal();
  $reflector = new ReflectionClass(LIBRARY_NAME);
  $keyword = $reflector->getMethod($keyword_name);
  $kw_params = $keyword->getParameters(); //array of ReflectionParameter objects
  $num_args = count($kw_params);
  $keyword_arguments = new xmlrpcval(array(), "array");
  for ($i = 0; $i < $num_args; $i++) {
    $keyword_arguments->addScalar($kw_params[$i]->name);
  }
  $xmlrpcresponse = new xmlrpcresp($keyword_arguments);
  return $xmlrpcresponse;
}

function get_keyword_documentation($xmlrpcmsg) {
  $keyword_name = $xmlrpcmsg->getParam(0)->scalarVal();
  $reflector = new ReflectionClass(LIBRARY_NAME);
  $keyword = $reflector->getMethod($keyword_name);
  $phpkwdoc = $keyword->getDocComment();

  //clean up formatting of documentation (e.g. remove CRLF, tabs, and the PHP doc comment identifiers "/**...*...*/")
  $phpkwdoc = preg_replace("/[\010]/", "\n", $phpkwdoc);
  $phpkwdoc = preg_replace("/[\013]/", "", $phpkwdoc);
  $phpkwdoc = preg_replace("/\s{2,}/", "", $phpkwdoc);
  $phpkwdoc = preg_replace("/\/\*\*/", "", $phpkwdoc);
  $phpkwdoc = preg_replace("/\*\//", "", $phpkwdoc);
  $phpkwdoc = preg_replace("/\*\s/", "", $phpkwdoc);

  $keyword_documentation = new xmlrpcval($phpkwdoc, "string");
  $xmlrpcresponse = new xmlrpcresp($keyword_documentation);
  return $xmlrpcresponse;
}

$svr = new xmlrpc_server(
  array(
    "get_keyword_names" => array( // XML-RPC function/method name
      "function" => "get_keyword_names", // php function name of the XML-RPC function/method
    ),
    "run_keyword"  => array(
      "function" => "run_keyword",
    ),
    "get_keyword_arguments"  => array(
      "function" => "get_keyword_arguments",
    ),
    "get_keyword_documentation"  => array(
      "function" => "get_keyword_documentation",
    ),
  )
);
