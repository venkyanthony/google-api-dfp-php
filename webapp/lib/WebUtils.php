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
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     David Kay <api.dyk@gmail.com>
 */


/**
 * A utility class to help deal with GET/POST.
 */
class WebUtils {

  /**
   * Fetches a parameter from the HTTP request, or returns an empty string in
   * the case that the parameter is not present in the HTTP request.
   * @param string $httpMethod String containing one of: GET, POST, PUT, DELETE
   * @param string $paramName the name of the parameter we wish to fetch
   */
  public static function GetParamOrEmptyString($httpMethod, $paramName) {
    return array_key_exists($paramName, $httpMethod)
        ? $httpMethod[$paramName]
        : '';
  }

}

?>
