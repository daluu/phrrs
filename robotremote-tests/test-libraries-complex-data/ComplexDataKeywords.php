<?php
class ComplexDataKeywords {

  public function list_int($listInt) {
    foreach ($listInt as $int) {
      if (!is_int($int)) {
        throw new Exception("Not a list of int");
      }
    }
    return $listInt;
  }

  public function list_string($listString) {
    var_dump($listString);
    foreach ($listString as $string) {
      if (!is_string($string)) {
        throw new Exception("Not a list of string");
      }
    }
    return $listInt;
  }

  // TODO list of other stuff
  // TODO nested lists
  // TODO dictionnaries
  // TODO dictionnaries of lists
  // TODO lists of dictionnaries
  // TODO nested dictionnaries

}
