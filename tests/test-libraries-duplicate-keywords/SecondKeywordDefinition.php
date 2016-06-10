<?php
class SecondKeywordDefinition {

  public function truth_of_life() {
    return 42;
  }

  /**
   * Compare 2 strings. If they are not equal, throws exception.
   */
  public function strings_should_be_equal($str1, $str2) {
    // Skip the echo/print since we can't redirect PHP output
    // and any echo/print may possibly become
    // the HTTP/XML-RPC response sent by the remote library server...

    // echo "Comparing '$str1' to '$str2'\n";
    if ($str1 != $str2) {
      throw new Exception("Given strings are not equal");
      // Don't use die either, as it is same result as echo/print.
      // die ("Given strings are not equal");
    }

    return $str1 == $str2;
  }
}
