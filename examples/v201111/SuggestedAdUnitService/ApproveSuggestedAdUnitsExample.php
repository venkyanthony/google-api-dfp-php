<?php
/**
 * This code example approves all suggested ad units with 50 or more requests.
 * This feature is only available to DFP premium solution networks.
 *
 * Tags: SuggestedAdUnitService
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
 * @subpackage v201111
 * @category   WebServices
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

// You can set the include path to src directory or reference
// DfpUser.php directly via require_once.
// $path = '/path/to/dfp_api_php_lib/src';
$path = dirname(__FILE__) . '/../../../src';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once 'Google/Api/Ads/Dfp/Util/ServiceUtils.php';
require_once 'Google/Api/Ads/Common/Util/MapUtils.php';

try {
  // Get DfpUser from credentials in "../auth.ini"
  // relative to the DfpUser.php file's directory.
  $user = new DfpUser();

  // Log SOAP XML request and response.
  $user->LogDefaults();

  // Get the SuggestedAdUnitService.
  $suggestedAdUnitService =
      $user->GetService('SuggestedAdUnitService', 'v201111');

  // Set the number of requests to 50 or more.
  define('NUMBER_OF_REQUESTS', 50);

  // Create statement text to select all suggested ad units.
  $filterStatementText = "";

  // Get all suggested ad units.
  $allSuggestedAdUnits = ServiceUtils::GetAllObjects($suggestedAdUnitService,
      'getSuggestedAdUnitsByStatement');

  $suggestedAdUnitIds = array();
  $i = 0;
  foreach ($allSuggestedAdUnits as $suggestedAdUnit) {
    if ($suggestedAdUnit->numRequests >= NUMBER_OF_REQUESTS) {
      printf("%d) Suggested ad unit with ID '%s' and number of requests '%d' "
          . "will be approved.\n", $i, $suggestedAdUnit->id,
          $suggestedAdUnit->numRequests);
      $i++;
      $suggestedAdUnitIds[] =  $suggestedAdUnit->id;
    }
  }

  printf("Number of suggested ad units to be approved: %d\n",
      sizeof($suggestedAdUnitIds));

  if (sizeof($suggestedAdUnitIds) > 0) {
    // Create action statement.
    $filterStatementText =
        sprintf('WHERE id IN (%s)', implode(',', $suggestedAdUnitIds));
    $filterStatement = new Statement($filterStatementText);

    // Create action.
    $action = new ApproveSuggestedAdUnit();

    // Perform action.
    $result = $suggestedAdUnitService->performSuggestedAdUnitAction($action,
        $filterStatement);

    // Display results.
    if (isset($result) && $result->numChanges > 0) {
      printf("Number of suggested ad units approved: %d\n",
          $result->numChanges);
    } else {
      print "No suggested ad units were approved.\n";
    }
  }
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}
