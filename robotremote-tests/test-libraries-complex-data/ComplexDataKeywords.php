<?php
class ComplexDataKeywords {

  public function list_int($listInt) {
    foreach ($listInt as $int) {
      if (!is_int($int)) {
        throw new Exception("Not a list of int: '".$int."' is of type ".gettype($int));
      }
    }
    return $listInt;
  }

  public function list_string($listString) {
    foreach ($listString as $string) {
      if (!is_string($string)) {
        throw new Exception("Not a list of string: '".$int."' is of type ".gettype($int));
      }
    }
    return $listString;
  }

  public function dictionary_string($dictionary) {
    foreach ($dictionary as $key => $value) {
      if (!is_string($key) || !is_string($value)) {
        throw new Exception("Not a dictionary of string to string: '".$key."'=>'".$value
          ."' is of type ".gettype($key)."=>".gettype($value));
      }
    }
    return $dictionary;
  }

  public function dictionary_int($dictionary) {
    foreach ($dictionary as $key => $value) {
      if (!is_string($key) || !is_int($value)) {
        throw new Exception("Not a dictionary of string to int: '".$key."'=>'".$value
          ."' is of type ".gettype($key)."=>".gettype($value));
      }
    }
    return $dictionary;
  }

}
