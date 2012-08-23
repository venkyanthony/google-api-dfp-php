<?php
/**
 * This example gets all creative templates.
 *
 * Tags: CreativeTemplateService.getCreativeTemplatesByStatement
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
 * @subpackage v201208
 * @category   WebServices
 * @copyright  2012, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Adam Rogal <api.arogal@gmail.com>
 * @author     Eric Koleda <api.ekoleda@gmail.com>
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

  // Get the CreativeTemplateService.
  $creativeTemplateService =
      $user->GetService('CreativeTemplateService', 'v201208');

  // Set defaults for page and statement.
  $page = new CreativeTemplatePage();
  $filterStatement = new Statement();
  $offset = 0;

  do {
    // Create a statement to get all creative templates.
    $filterStatement->query = 'LIMIT 500 OFFSET ' . $offset;

    // Get creative templates by statement.
    $page = $creativeTemplateService->getCreativeTemplatesByStatement(
        $filterStatement);

    // Display results.
    if (isset($page->results)) {
      $i = $page->startIndex;
      foreach ($page->results as $creativeTemplate) {
        printf("%d) Creative template with ID '%s', name '%s', and type '%s' "
            ."was found.\n", $i, $creativeTemplate->id, $creativeTemplate->name,
            $creativeTemplate->type);
        $i++;
      }
    }

    $offset += 500;
  } while ($offset < $page->totalResultSetSize);

  print 'Number of results found: ' . $page->totalResultSetSize . "\n";
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}
