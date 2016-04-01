<?php
/**
 * @file
 * example_library.php
 */

/**
 * This example library is a port of the Python reference version:
 * http://robotframework.googlecode.com/hg/tools/remoteserver/example/examplelibrary.py
 * it is by no means an exact functional port, but close enough.
 */
class ExampleLibrary {

  /**
   * Returns a count of files and folders in the specified directory.
   */
  public function count_items_in_directory($path) {
    // -2 = Subtract for "." and ".."
    $file_count = count(scandir($path)) - 2;
    return $file_count;
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

  }
}
