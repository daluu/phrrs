<?php

namespace PhpRobotRemoteServer;

/*
 * Originaly taken from:
 * http://stackoverflow.com/questions/7153000/get-class-name-from-file
 * Then grown to support multiple classes, and to return a complex associative
 * array of all the functions associated with each class, with arguments and
 * documentation for each function.
 */
class ClassFinder {

	public function findFunctionsByClasses($file) {
		$fqnClassesFunctions = array();

		$currentNamespace = '';
		$currentClass = '';
		$lastDoc = '';

		$fp = fopen($file, 'r');
		$buffer = '';
		$i = 0;
		while (!feof($fp)) {

		    $buffer .= fread($fp, 512);
		    $tokens = token_get_all($buffer);

		    if (strpos($buffer, '{') === false) continue;

		    for (;$i<count($tokens);$i++) {
		        if ($tokens[$i][0] === T_NAMESPACE) {
		            for ($j=$i+1;$j<count($tokens); $j++) {
		                if ($tokens[$j][0] === T_STRING) {
		                     $currentNamespace .= '\\'.$tokens[$j][1];
		                } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
		                     break;
		                }
		            }
		        }

		        if ($tokens[$i][0] === T_CLASS) {
		            for ($j=$i+1;$j<count($tokens);$j++) {
		                if ($tokens[$j] === '{') {
		                    $currentClass = $tokens[$i+2][1];
		                }
		            }
		        }

   		        if ($tokens[$i][0] === T_DOC_COMMENT) {
   		        	$lastDoc = $tokens[$i][1];
   		       	} 
				
   		        if ($tokens[$i][0] === T_FUNCTION) {
   		        	$arguments = array();
		            for ($j=$i+1;$j<count($tokens);$j++) {
		            	if ($tokens[$j][0] === T_VARIABLE) {
		            		$arguments[] = $tokens[$j][1];
		            	} else if ($tokens[$j] === '{') {
		                    $function = $tokens[$i+2][1];
		                    $fqnClass = $currentNamespace.'\\'.$currentClass;
		                    $fqnClassesFunctions[$fqnClass][$function] = array(
		                    	'arguments' => $arguments,
		                    	'documentation' => $lastDoc);
		                    $lastDoc = '';
		                    break;
		                }
		            }
		        }

		    }
		}

		return $fqnClassesFunctions;
	}

}
