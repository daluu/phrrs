<?php

namespace PhpRobotRemoteServer;

class KeywordStore {

	private $keywordCollector;
	private $verbose;
	private $stoppableServer;

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

    public function __construct($verbose = TRUE, KeywordCollector $keywordCollector = NULL) {
    	$this->verbose = $verbose;

    	if (!$keywordCollector) {
    		$keywordCollector = new KeywordCollector();
    	}
    	$this->keywordCollector = $keywordCollector;
    }

    public function setStoppableServer($stoppableServer) {
    	$this->stoppableServer = $stoppableServer;
    }

	public function collectKeywords($keywordsDirectory) {
		$files = $this->findFiles($keywordsDirectory);

		$this->keywords = array();
		foreach ($files as $file) {
		  	$this->collectKeywordsFromFile($file);
		}

		if ($this->verbose) {
			echo("List of defined keywords:\n");
			foreach ($this->keywordReport() as $keyword => $fromFile) {
				echo("- ".$keyword."\t\t--> from: ".$fromFile."\n");
			}
		}
	}

	function findFiles($directory) {
		$foundFiles = array();
		$this->recursiveFileLookup($directory, $foundFiles);
		return $foundFiles;
	}

	private function recursiveFileLookup($path, &$foundFiles) {
		if (is_dir($path)) {
		  	$elements = scandir($path);
		  	foreach ($elements as $element) {
		  		if ($element === '.' || $element === '..') {
		  			continue;
		  		} else {
			  		$fullPathFile = $path.'/'.$element;
		  			$this->recursiveFileLookup($fullPathFile, $foundFiles);
		  		}
		  	}
		} else {
	  		$foundFiles[] = $path;
		}
	}

	function collectKeywordsFromFile($file) {
		if ($this->verbose) {
			echo('Looking for keyword definitions into: '.$file."\n");
		}

		$functionsByClasses = $this->keywordCollector->findFunctionsByClasses($file);
		foreach ($functionsByClasses as $class => $functions) {
			foreach ($functions as $function => $functionInfo) {
				$rawArguments = $functionInfo['arguments'];
				$rawDocumentation = $functionInfo['documentation'];
				$arguments = $this->cleanUpPhpArguments($rawArguments);
				$documentation = $this->cleanUpPhpDocumentation($rawDocumentation);

				if (array_key_exists($function, $this->keywords)) {
					if ($this->verbose) {
						echo("WARNING: keyword '".$function."' already declared in: ".$this->keywords[$function]['file']."\n");
					}
				} else {
					$this->keywords[$function] = array(
						'file' => $file,
						'class' => $class,
						'arguments' => $arguments,
						'documentation' => $documentation
					);
				}
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

	function keywordReport() {
		$keywordReport = array();

		foreach ($this->keywords as $keyword => $keywordInfo) {
			$keywordReport[$keyword] = $keywordInfo['file'];
		}

		return $keywordReport;
	}

	public function getKeywordNames() {
		$keywordNames = array_keys($this->keywords);
		$keywordNames[] = 'stop_remote_server';
		return $keywordNames;
	}

	public function execKeyword($keywordName, $keywordArgs) {
		if ($keywordName == 'stop_remote_server') {
			$this->stoppableServer->stop();
			return;
		}

		$keywordInfo = $this->keywords[$keywordName];
		$fullFunctionName = $keywordInfo['class'].'::'.$keywordName;

		require_once($keywordInfo['file']);
		$result = call_user_func_array($fullFunctionName, $keywordArgs);
	    return $result;
	}

	public function getKeywordArguments($keywordName) {
		if ($keywordName == 'stop_remote_server') {
			return array();
		}

		return $this->keywords[$keywordName]['arguments'];
	}

	public function getKeywordDocumentation($keywordName) {
		if ($keywordName == 'stop_remote_server') {
			return 'Stops the server';
		}

		return $this->keywords[$keywordName]['documentation'];
	}

}
