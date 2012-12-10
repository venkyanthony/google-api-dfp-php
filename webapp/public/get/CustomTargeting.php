<?php
/**
 * This panel lists the custom targeting keys and values in the account. They
 * are displayed as nested HTML lsits.
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
 * Maps key IDs to child values.
 * @param array $values an array of values
 * @return array a map from key ID to an array of child values
 */
function MapKeysToValues(array $values) {
  $map = array();
  foreach ($values as $value) {
    if (isset($value->customTargetingKeyId)) {
      $map[$value->customTargetingKeyId][] = $value;
    }
  }
  return $map;
}

/**
 * Prints an array of keys and map of values as nested HTML lists.
 * @param array $keys an array of keys
 * @param array $valuesMap a map from key ID to an array of values
 */
function PrintKeysAndValuesList(array $keys, array $valuesMap) {
  print '<ul>';
  foreach ($keys as $key) {
    print '<li>' . $key->name . ' (' . $key->id . ')';
    DisplayUtils::PrintDetailsLink('[details]', 'customtargetingkey-'
        . $key->id);
    DisplayUtils::PrintDetails($key, 'customtargetingkey-' . $key->id);
    if (isset($valuesMap[$key->id])) {
      print '<ul>';
      foreach ($valuesMap[$key->id] as $value) {
        print '<li>' . $value->name . ' (' . $value->id . ')';
        DisplayUtils::PrintDetailsLink('[details]', 'customtargetingvalue'
            . $value->id);
        DisplayUtils::PrintDetails($value, 'customtargetingvalue' . $value->id);
        print '</li>';
      }
      print '</ul>';
    }
    print '</li>';
  }
  print '</ul>';
}

try {
  // Load the service user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  $keys = array();
  $values = array();

  // Create service.
  $customTargetingService = $user->GetService('CustomTargetingService');

  // Get filter text.
  $filterText = WebUtils::GetParamOrEmptyString($_POST, 'filterText');

  // Get display style.
  $displayStyle = WebUtils::GetParamOrEmptyString($_POST, 'displayStyle');

  // Get type override.
  $typeOverride = WebUtils::GetParamOrEmptyString($_POST, 'typeOverride');

  if (empty($typeOverride)) {
    PrintKeysAndValues($customTargetingService);
  } else if ($typeOverride == 'key') {
    PrintKeys($customTargetingService, $filterText);
  } else if ($typeOverride == 'value') {
    PrintValues($customTargetingService, $filterText);
  } else {
    DisplayUtils::PrintErrorMessage('Invalid typeOverride.');
  }
} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}

function PrintKeysAndValues($customTargetingService) {
  // Get all keys.
  $keys = ServiceUtils::GetAllObjects($customTargetingService,
    'getCustomTargetingKeysByStatement');
  if (sizeof($keys) > 0) {
    // Get all values.
    $keyIds = array_map(create_function('$key', 'return $key->id;'), $keys);
    $filterStatementText = sprintf("%s (%s)",
        'WHERE customTargetingKeyId IN',
        implode(',', $keyIds)
    );
    $values = ServiceUtils::GetAllObjects($customTargetingService,
        'getCustomTargetingValuesByStatement', $filterStatementText);
    // Map key IDs to child values.
    $valuesMap = MapKeysToValues($values);
    // Output HTML.
    PrintKeysAndValuesList($keys, $valuesMap);
  } else {
    // Display no results message.
    DisplayUtils::PrintInfoMessage('No results found.');
  }
}

function PrintKeys($customTargetingService, $filterText) {
  print '<b>Keys</b>';
  // Get all keys that match the filter.
  $keys = $filterText == ''
      ? ServiceUtils::GetAllObjects($customTargetingService,
          'getCustomTargetingKeysByStatement', $filterText)
      : ServiceUtils::GetSomeObjects($customTargetingService,
          'getCustomTargetingKeysByStatement', $filterText);
  if (sizeof($keys) > 0) {
    // Output HTML.
    DisplayUtils::PrintNameAndIdList($keys, 'keys');
  } else {
    DisplayUtils::PrintInfoMessage('No results found.');
  }
}

function PrintValues($customTargetingService, $filterText) {
  print '<b>Values</b>';

  // Get values from database using proper filter/WHERE statement
  $values = ServiceUtils::GetSomeObjects($customTargetingService,
      'getCustomTargetingValuesByStatement', $filterText);

  if (sizeof($values) > 0) {
    // Output HTML.
    DisplayUtils::PrintNameAndIdList($values, 'values');
  } else {
    DisplayUtils::PrintInfoMessage('No results found.');
  }
}
