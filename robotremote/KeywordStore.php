<?php

namespace PhpRobotRemoteServer;

class KeywordStore {

	private $classFinder;

	/*
	 * Map (associative array):
	 *
	 * Keyword/method name
	 * => PHP file defining the keyword
	 *  + Class name defining the keyword/method, complete with namespace
	 *  + Arguments of the keyword
	 *  + Documentation of the keyword
	 */
	var $keywords;

    public function __construct(ClassFinder $classFinder = NULL) {
    	if (!$classFinder) {
    		$classFinder = new ClassFinder();
    	}
    	$this->classFinder = $classFinder;
    }

	public function collectKeywords($keywordsDirectory) {
		$files = $this->findFiles($keywordsDirectory);

		$this->keywords = array();
		foreach ($files as $file) {
		  	$this->collectKeywordsFromFile($file);
		}
	}

	function findFiles($directory) {
		$foundFiles = array();
		$this->recursiveFileLookup($directory, $foundFiles);
		return $foundFiles;
	}

	private function recursiveFileLookup($directory, &$foundFiles) {
		if (is_dir($directory)) {
		  	$elements = scandir($directory);
		  	foreach ($elements as $element) {
		  		if ($element === '.' || $element === '..') {
		  			continue;
		  		} else {
			  		$fullPathFile = $directory.'/'.$element;
		  			if (is_dir($fullPathFile)) {
			  			$this->recursiveFileLookup($fullPathFile, $foundFiles);
		  			} else {
				  		$foundFiles[] = $fullPathFile;
		  			}
		  		}
		  	}
		}
	}

	function collectKeywordsFromFile($file) {
		$functionsByClasses = $this->classFinder->findFunctionsByClasses($file);
		foreach ($functionsByClasses as $class => $functions) {
			foreach ($functions as $function => $functionInfo) {
				$rawArguments = $functionInfo['arguments'];
				$rawDocumentation = $functionInfo['documentation'];
				$arguments = $this->cleanUpPhpArguments($rawArguments);
				$documentation = $this->cleanUpPhpDocumentation($rawDocumentation);
				// TODO issue warning if keyword already exists
				$this->keywords[$function] = array(
					'file' => $file,
					'class' => $class,
					'arguments' => $arguments,
					'documentation' => $documentation
				);
			}
		}
	}

	function cleanUpPhpArguments($rawArguments) {
		$result = array();
		foreach ($rawArguments as $rawArgument) {
			$result[] = substr($rawArgument, 1);
		}
		return $result;
	}

	function cleanUpPhpDocumentation($rawDocumentation) {
		$doc = $rawDocumentation;

		// Clean up formatting of documentation
		// (e.g. remove CRLF, tabs, and the PHP doc comment identifiers "/**...*/")
		$doc = preg_replace("/[\010]/", "\n", $doc);
	    $doc = preg_replace("/[\013]/", "", $doc);
	  	$doc = preg_replace("/\s{2,}/", "", $doc);
	  	$doc = preg_replace("/\/\*\*/", "", $doc);
	  	$doc = preg_replace("/\*\//", "", $doc);
	  	$doc = preg_replace("/\*\s/", "", $doc);

		return $doc;
	}

	public function getKeywordNames() {
		$keywordNames = array_keys($this->keywords);

		// $keywordNames->addScalar("stop_remote_server"); TODO if we are to implement this keyword so that it is accessible from tests....
		return $keywordNames;
	}

	public function execKeyword($keywordName, $keywordArgs) {
		$keywordInfo = $this->keywords[$keywordName];
		$fullFunctionName = $keywordInfo['class'].'::'.$keywordName;

		require_once($keywordInfo['file']);
		$result = call_user_func_array($fullFunctionName, $keywordArgs);
	    return $result;
	}

	public function getKeywordArguments($keywordName) {
		return $this->keywords[$keywordName]['arguments'];
	}

	public function getKeywordDocumentation($keywordName) {
		return $this->keywords[$keywordName]['documentation'];
	}

}
