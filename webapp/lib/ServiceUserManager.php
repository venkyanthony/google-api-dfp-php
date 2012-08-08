<?php
/**
 * Stores and retrieves instances of DfpUser from the session.
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

error_reporting(E_STRICT | E_ALL);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';

/**
 * Stores and retrieves instances of DfpUser from the session.
 */
class ServiceUserManager {
  /**
   * The ServiceUserManager class is not meant to have any instances.
   * @access private
   */
  private function __construct() {}

  /**
   * Set the service user in the session.
   * @param DfpUser $user the user to set
   */
  public static function SetServiceUser(DfpUser $user) {
    $_SESSION['DFP_USER'] = $user;
  }

  /**
   * Get the service user from the session.
   * @return DfpUser the current user
   */
  public static function GetServiceUser() {
    if (isset($_SESSION['DFP_USER'])) {
      return $_SESSION['DFP_USER'];
    } else {
      return NULL;
    }
  }

  /**
   * Removes the service user from the session.
   */
  public static function RemoveServiceUser() {
    $_SESSION['DFP_USER'] = NULL;
  }
}
