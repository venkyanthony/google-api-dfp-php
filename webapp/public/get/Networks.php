<?php
/**
 * This panel lists the networks the user has access to.
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
require_once '../../lib/DisplayUtils.php';
require_once '../../lib/ServiceUserManager.php';
require_once 'Google/Api/Ads/Dfp/Util/ServiceUtils.php';

try {
  // Load the service user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  // Create service.
  $networkService = $user->GetService('NetworkService');

  // Get all networks.
  $user->SetNetworkCode(NULL);
  $networks = $networkService->getAllNetworks();

  if (sizeof($networks) > 0) {
    // Output HTML.
    print '<ul>';
    foreach ($networks as $network) {
      $user->SetNetworkCode($network->networkCode);
      $networkService = $user->GetNetworkService();
      $network = $networkService->getCurrentNetwork();
      print '<li>' . $network->displayName . ' (' . $network->networkCode . ')';
      DisplayUtils::PrintDetailsLink("[details]", 'network-'
          . $network->networkCode);
      DisplayUtils::PrintDetails($network, 'network-' . $network->networkCode);
      print '</li>';
    }
    print '</ul>';
  } else {
    // Display no results message.
    DisplayUtils::PrintInfoMessage('No results found.');
  }
} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}
