<?php
/**
 * This page lists the ad units in the account as a heirarchical structure
 * using nested lists.  The HTML is printed to the response, which is
 * included in index.php via asynchronous JavaScript requests.
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
 * Maps ad unit ids to child ad units as an associative array.
 * @param array $adUnits an array of ad units
 * @return array a map from ad unit id to an array of child ad units
 */
function MapAdUnits(array $adUnits) {
  $treeMap = array();
  foreach ($adUnits as $adUnit) {
    if (isset($adUnit->parentId)) {
      $treeMap[$adUnit->parentId][] = $adUnit;
    } else {
      $treeMap['root'][] = $adUnit;
    }
  }
  return $treeMap;
}

/**
 * Prints an ad unit map as a tree using nested HTML lists.
 * @param array $treeMap a map from ad unit id to an array of child ad units
 */
function PrintAdUnitTree(array $treeMap) {
  print '<ul>';
  if (isset($treeMap['root'])) {
    PrintAdUnitSubTree($treeMap['root'][0], $treeMap);
  }
  print '</ul>';
}

/**
 * Prints the sub-tree for an ad unit and its children as nested HTML lists.
 * @param AdUnit $adUnit the root ad unit of the sub-tree
 * @param array $treeMap a map from ad unit id to an array of child ad units
 */
function PrintAdUnitSubTree(AdUnit $adUnit, array $treeMap) {
  print '<li>' . $adUnit->name . ' (' . $adUnit->id . ')';
  DisplayUtils::PrintDetailsLink('[details]', 'adunit-' . $adUnit->id);
  DisplayUtils::PrintDetails($adUnit, 'adunit-' . $adUnit->id);
  if (isset($treeMap[$adUnit->id])) {
    print '<ul>';
    foreach ($treeMap[$adUnit->id] as $childAdUnit) {
      PrintAdUnitSubTree($childAdUnit, $treeMap);
    }
    print '</ul>';
  }
  print '</li>';
}

/**
 * Prints the list of ad units in an HTML list.
 * @param array $adUnits the array of ad units
 */
function PrintAdUnitList(array $adUnits) {
  foreach ($adUnits as $adUnit) {
    print '<li>' . $adUnit->name . ' (' . $adUnit->id . ')';
    DisplayUtils::PrintDetailsLink('[details]', $adUnit->id);
    DisplayUtils::PrintDetails($adUnit, $adUnit->id);
    print '</li>';
  }
}

try {
  // Load the service user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  // Get filter text.
  $filterText = WebUtils::GetParamOrEmptyString($_POST, 'filterText');

  // Get display style.
  $displayStyle = WebUtils::GetParamOrEmptyString($_POST, 'displayStyle');

  if (!empty($filterText)) {
    $filterText = str_replace('\\', '', $filterText);
  }

  // Create service.
  $inventoryService = $user->GetService('InventoryService');

  // Get all ad units.
  $adUnits = empty($filterText)
      ? ServiceUtils::GetAllObjects($inventoryService, 'getAdUnitsByStatement',
          $filterText)
      : ServiceUtils::GetSomeObjects($inventoryService, 'getAdUnitsByStatement',
          $filterText);

  if (sizeof($adUnits) > 0) {
    if ($displayStyle == 'default') {
      // Map ad unit ids to child ad units.
      $treeMap = MapAdUnits($adUnits);
      // Output HTML.
      PrintAdUnitTree($treeMap);
    } else if ($displayStyle == 'list') {
      DisplayUtils::PrintNameAndIdList($adUnits, 'ad_unit');
    } else {
      DisplayUtils::PrintErrorMessage("Invalid display style.");
    }
  } else {
    // Display no results message.
    DisplayUtils::PrintInfoMessage('No results found.');
  }
} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}
