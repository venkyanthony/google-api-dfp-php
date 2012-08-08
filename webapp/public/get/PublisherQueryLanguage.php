<?php
/**
 * This panel lists the users in the account as an HTML list.
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
 * @author     Jeff Sham <api.shamjeff@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once '../../lib/DisplayUtils.php';
require_once '../../lib/ServiceUserManager.php';
require_once '../../lib/WebUtils.php';
require_once 'Google/Api/Ads/Dfp/Util/ServiceUtils.php';

/**
* Prints the column type and data in a HTML table.
* @param $resultSet The result set to print
*/
function PrintColumns(array $columnTypes, array $rows) {
  print '<table>';
  $columnLabels = array_map(
      create_function('$columnType', 'return $columnType->labelName;'),
          $columnTypes);
  print '<thead>';
  print '<tr><th>' . implode('</th><th>', $columnLabels) . '</th></tr>';
  print '</thead>';
  $i = 0;
  print '<tbody>';
  foreach($rows as $row) {
    $values = array_map(create_function('$value', 'return $value->value;'),
        $row->values);
    print '<tr><td>' . implode('</td><td>', $values) . '</td></tr>';
    $i++;
  }
  print '</tbody>';
  print '</table>';
}

try {
  // Load the service user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  // Get filter text.
  $selectText = WebUtils::GetParamOrEmptyString($_POST, 'filterText');

  if ($selectText != '') {
    $selectText = str_replace('\\', '', $selectText);
    // Create service.
    $pqlService = $user->GetService('PublisherQueryLanguageService');

    //Create select statement.
    $selectStatement = new Statement($selectText);

    // Get using Publisher Query Language.
    $resultSet = $pqlService->select($selectStatement);

    if (isset($resultSet)) {
      // Output HTML.
      PrintColumns($resultSet->columnTypes, $resultSet->rows);
    } else {
      // Display no results message.
      DisplayUtils::PrintInfoMessage('No results found.');
    }
  } else {
    // Display message for no select text passed.
    DisplayUtils::PrintInfoMessage(
        'Open the select statement input to run a PQL query.');
  }
} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}
