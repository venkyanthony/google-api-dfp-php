<?php
/**
 * This example deactivates all active labels. To determine which labels exist,
 * run GetAllLabelsExample.php. This feature is only available to DFP premium
 * solution networks.
 *
 * Tags: LabelService.getLabelsByStatement
 * Tags: LabelService.performLabelAction
 *
 * PHP version 5
 *
 * Copyright 2012, Google Inc. All Rights Reserved.
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
 * @subpackage v201211
 * @category   WebServices
 * @copyright  2012, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 * @author     Paul Rashidi <api.paulrashidi@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

// You can set the include path to src directory or reference
// DfpUser.php directly via require_once.
// $path = '/path/to/dfp_api_php_lib/src';
$path = dirname(__FILE__) . '/../../../src';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';

try {
  // Get DfpUser from credentials in "../auth.ini"
  // relative to the DfpUser.php file's directory.
  $user = new DfpUser();

  // Log SOAP XML request and response.
  $user->LogDefaults();

  // Get the LabelService.
  $labelService = $user->GetService('LabelService', 'v201211');

  // Create statement text to get all active labels.
  $filterStatementText = "WHERE isActive = true";

  $offset = 0;

  do {
    // Create statement to page through results.
    $filterStatement =
        new Statement($filterStatementText . " LIMIT 500 OFFSET " . $offset);

    // Get labels by statement.
    $page = $labelService->getLabelsByStatement($filterStatement);

    // Display results.
    $labelIds = array();
    if (isset($page->results)) {
      foreach ($page->results as $label) {
      printf("A label with ID '%s' and name '%s' will be deactivated.\n",
          $label->id, $label->name);
        $labelIds[] = $label->id;
      }
    }

    $offset += 500;
  } while ($offset < $page->totalResultSetSize);

  print 'Number of labels to be deactivated: ' . sizeof($labelIds) . "\n";

  if (sizeof($labelIds) > 0) {
    // Create action statement.
    $filterStatementText =
        sprintf('WHERE id IN (%s)', implode(',', $labelIds));
    $filterStatement = new Statement($filterStatementText);

    // Create action.
    $action = new DeactivateLabels();

    // Perform action.
    $result = $labelService->performLabelAction($action, $filterStatement);

    // Display results.
    if (isset($result) && $result->numChanges > 0) {
      print 'Number of labels deactivated: ' . $result->numChanges . "\n";
    } else {
      print "No labels were deactivated.\n";
    }
  }
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}