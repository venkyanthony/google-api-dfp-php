<?php
/**
 * This panel lists the LICAs in the account as an HTML table.
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
 * Prints an array of line item creative associations as an HTML table.
 * @param array $licas an array of line item creative associations
 */
function PrintLicaTable(array $licas) {
  print '<table>';
  print '<tr><th>Line Item ID</th><th>Creative ID</th><th>Status</th><th>Details</th></tr>';
  foreach ($licas as $lica) {
    print '<tr><td>' . $lica->lineItemId . '</td><td>' . $lica->creativeId
        . '</td><td>' . $lica->status . '</td><td>';
    $reference = 'lica-' . $lica->lineItemId . '-' . $lica->creativeId;
    DisplayUtils::PrintDetailsLink('[details]', $reference);
    print '</td></tr>';
    DisplayUtils::PrintDetails($lica, $reference);
  }
  print '</table>';
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
  $licaService = $user->GetLineItemCreativeAssociationService();
  $licaService = $user->GetService('LineItemCreativeAssociationService');

  // Get all licas.
  $licas = $filterText == ''
      ? ServiceUtils::GetAllObjects($licaService,
          'getLineItemCreativeAssociationsByStatement', $filterText)
      : ServiceUtils::GetSomeObjects($licaService,
          'getLineItemCreativeAssociationsByStatement', $filterText);

  if (sizeof($licas) > 0) {
    // Output HTML.
    PrintLicaTable($licas);
  } else {
    // Display no results message.
    DisplayUtils::PrintInfoMessage('No results found.');
  }
} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}
