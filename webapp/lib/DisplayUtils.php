<?php
/**
 * A utility class to help display information.
 *
 * PHP version 5
 *
 * Copyright 2011, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    GoogleApiAdsDfp
 * @subpackage webapp
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

/**
 * A utility class to help display information.
 */
class DisplayUtils {
  /**
   * The DisplayUtils class is not meant to have any instances.
   * @access private
   */
  private function __construct() {}

  /**
   * Prints an error message.
   * @param string $message the error message
   */
  public static function PrintErrorMessage($message) {
    print '<p class="dfp-error">Error: ' . $message . '</p>';
  }

  /**
   * Prints an info message.
   * @param string $message the info message
   */
  public static function PrintInfoMessage($message) {
    print '<p class="dfp-info">' . $message . '</p>';
  }

  /**
   * Prints an array of objects as an HTML list, displaying name and ID.
   * @param array $objects an array of objects
   * @param string $type the type of objects
   */
  public static function PrintNameAndIdList(array $objects, $type) {
    print '<ul>';
    foreach ($objects as $object) {
      print '<li>' . $object->name . ' (' . $object->id . ')';
      DisplayUtils::PrintDetailsLink("[details]", $type . '-' . $object->id);
      DisplayUtils::PrintDetails($object, $type . '-' . $object->id);
      print '</li>';
    }
    print '</ul>';
  }

  /**
   * Prints a details link using the text and details reference specified.
   * @param string $text the text to display in the link
   * @param string $reference the reference for the details information
   */
  public static function PrintDetailsLink($text, $reference) {
    print '<a href="#" class="dfp-details-link" rel="dfp-details-' . $reference
        . '">' . $text . '</a>';
  }

  /**
   * Prints the details for the object, using the reference specified.
   * @param $object the object to print the details of
   * @param string $reference a reference to use to refer to these details
   *     later.
   */
  public static function PrintDetails($object, $reference) {
    print '<div class="dfp-details" id="dfp-details-' . $reference . '"><pre>';
    print htmlspecialchars(print_r($object, true));
    print '</pre></div>';
  }
}

