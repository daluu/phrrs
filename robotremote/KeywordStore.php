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

	public function getReflector() {
		# PHP class name that will be used as Robot Framework keyword library
		return new \ReflectionClass('ExampleLibrary');
	}

	public function getKeywordNames() {
	  $keywords = $this->getReflector()->getMethods();
	  $keywordNames = array();
	  foreach ($keywords as $keyword) {
	    $keywordNames[] = $keyword->name;
	  }
	  return $keywordNames;
	}

}
