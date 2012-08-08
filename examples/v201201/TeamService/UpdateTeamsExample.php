<?php
/**
 * This example updates teams by adding you to them upto the first 500.
 * To determine which teams exist, run GetAllTeamsExample.java.
 *
 * Tags: TeamService.getTeamsByStatement
 * Tags: TeamService.updateTeams
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
 * @subpackage v201201
 * @category   WebServices
 * @copyright  2012, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
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

  // Get the TeamService.
  $teamService = $user->GetService('TeamService', 'v201201');

  // Get the UserService.
  $userService = $user->GetService('UserService', 'v201201');

  // Get the current user's ID.
  $userId = $userService->getCurrentUser()->id;

  // Create a statement to select first 500 teams
  $filterStatement = new Statement("LIMIT 500");

  // Get teams by statement.
  $page = $teamService->getTeamsByStatement($filterStatement);

  if (isset($page->results)) {
    $teams = $page->results;

    $i = 0;
    // Iterate through the teams returned.
    foreach ($teams as $team) {
      // Update each local team object by appending the current userId.

      // Add the userId only when it didn't already exist in the list.
      if (!isset($team->userIds) || !in_array($userId, $team->userIds)) {
        // User was not already in Team, add them to the userIds list.
        $team->userIds[] = $userId;
      } else {
        // User was already in Team, remove this team from the update list.
        unset($teams[$i]);
      }
      $i++;
    }

    // Reorganize the $teams array.
    $teams = array_values($teams);

    // Update the teams on the server.
    $teams = $teamService->updateTeams($teams);

    // Display results.
    if (isset($teams)) {
      foreach ($teams as $team) {
        print 'A team with ID "' . $team->id
            . '" and name "' . $team->name . "\" was updated.\n";
      }
    } else {
      print "No teams updated.\n";
    }
  } else {
    print "No teams found to update.\n";
  }
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}
