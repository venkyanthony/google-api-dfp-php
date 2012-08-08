<?php
/**
 * This panel lists the creatives in the account as an HTML list.
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
require_once '../../lib/WebUtils.php';
require_once 'Google/Api/Ads/Dfp/Util/ServiceUtils.php';

/**
 * Prints an array of creatives as an HTML list.
 * @param array $creatives an array of creatives
 */
function PrintCreativeList(array $creatives) {
  print '<ul>';
  foreach ($creatives as $creative) {
    print '<li><a href="' . $creative->previewUrl . '" target="_blank">'
        . $creative->name . '</a> (' . $creative->id . ')';
    DisplayUtils::PrintDetailsLink('[details]', 'creative-' . $creative->id);
    DisplayUtils::PrintDetails($creative, 'creative-' . $creative->id);
    print '</li>';
  }
  print '</ul>';
}

try {
  // Load the service user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  // Get filter text.
  $filterText = WebUtils::GetParamOrEmptyString($_POST, 'filterText');

  if ($filterText != '') {
    $filterText = str_replace('\\', '', $filterText);
  }

  // Create service.
  $creativeService = $user->GetService('CreativeService');

  // Get all creatives.
  $creatives = $filterText == ''
      ? ServiceUtils::GetAllObjects($creativeService,
          'getCreativesByStatement', $filterText)
      : ServiceUtils::GetSomeObjects($creativeService,
          'getCreativesByStatement', $filterText);

  if (sizeof($creatives) > 0) {
    // Output HTML.
    PrintCreativeList($creatives);
  } else {
    // Display no results message.
    DisplayUtils::PrintInfoMessage('No results found.');
  }

} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}
