<?php

namespace PhpRobotRemoteServer;

class KeywordStore {

	private $keywordsDirectory;

	public function collectKeywords($keywordsDirectory) {
		// Every php file inside $directory folder will be added.
		// Put your PHP class file(s) into that $directory folder.
		$directory = $keywordsDirectory;
		if (is_dir($directory)) {
		  $files = scandir($directory);
		  foreach ($files as $file) {
		    if (!in_array($file, array('.', '..'))) {
		      $file_infos = new \SplFileInfo($file);
		      if ('php' === $file_infos->getExtension()) {
		        require_once $directory . '/' . $file;
		      }
		    }
		  }
		}

		// Alternatively, instead of using constants above,
		// you could retrofit/modify the PHP code here to take in
		// the needed values via HTTP GET query string parameters
		// or read from a INI/config file or database query.
	}

	private function getReflector() {
		# PHP class name that will be used as Robot Framework keyword library
		return new \ReflectionClass('ExampleLibrary');
	}

	private function getAllKeywordMethods() {
		$reflector = $this->getReflector();
		$allKeywordNames = $this->getReflector()->getMethods();
		// $allKeywordNames->addScalar("stop_remote_server"); TODO if we are to implement this keyword so that it is accessible from tests....
		return $allKeywordNames;
	}

	private function getKeywordMethod($keywordName) {
		$reflector = $this->getReflector();
		$keyword = $reflector->getMethod($keywordName);
		return $keyword;
	}

	private function getKeywordExecutorInstance($keywordName) {
		$reflector = $this->getReflector();
	    $libraryInstance = $reflector->newInstance();
		return $libraryInstance;
	}

	public function getKeywordNames() {
	  $keywords = $this->getAllKeywordMethods();
	  $keywordNames = array();
	  foreach ($keywords as $keyword) {
	    $keywordNames[] = $keyword->name;
	  }
	  return $keywordNames;
	}

	public function execKeyword($keywordName, $keywordArgs) {
	    $libraryInstance = $this->getKeywordExecutorInstance($keywordName);
		$keywordExecutor = $this->getKeywordMethod($keywordName);
	    $result = $keywordExecutor->invokeArgs($libraryInstance, $keywordArgs);
	    return $result;
	}

	public function getKeywordArguments($keywordName) {
	  $keyword = $this->getKeywordMethod($keywordName);
	  // Array of ReflectionParameter objects.
	  $keywordParams = $keyword->getParameters();
	  $keywordParamNames = array();
	  foreach ($keywordParams as $keywordParam) {
	    $keywordParamNames[] = $keywordParam->name;
	  }
	  return $keywordParamNames;
	}

	public function getKeywordDocumentation($keywordName) {
	  $keyword = $this->getKeywordMethod($keywordName);
	  $phpkwdoc = $keyword->getDocComment();

	  // Clean up formatting of documentation
	  // (e.g. remove CRLF, tabs, and the PHP doc comment identifiers "/**...*/")
	  $phpkwdoc = preg_replace("/[\010]/", "\n", $phpkwdoc);
	  $phpkwdoc = preg_replace("/[\013]/", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\s{2,}/", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\/\*\*/", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\*\//", "", $phpkwdoc);
	  $phpkwdoc = preg_replace("/\*\s/", "", $phpkwdoc);

	  return $phpkwdoc;
	}

}
