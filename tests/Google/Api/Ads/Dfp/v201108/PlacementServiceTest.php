<?php
/**
 * Functional tests for PlacementService.
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
 * @subpackage v201108
 * @category   WebServices
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Adam Rogal <api.arogal@gmail.com>
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

$path = dirname(__FILE__) . '/../../../../../../src';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for PlacementService.
 * @backupStaticAttributes disabled
 */
class PlacementServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $adUnitId1;
  private static $adUnitId2;
  private static $adUnitId3;
  private static $placement;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('PlacementService', $this->version);

    if (!isset(PlacementServiceTest::$adUnitId1)
        || !isset(PlacementServiceTest::$adUnitId2)
        || !isset(PlacementServiceTest::$adUnitId3)) {
      $networkService = $this->user->GetService('NetworkService', $this->version);
      $network = $networkService->getCurrentNetwork();
      $rootAdUnitId = $network->effectiveRootAdUnitId;

      $inventoryService = $this->user->GetService('InventoryService', $this->version);

      $adUnit1 = new AdUnit();
      $adUnit1->name = "Ad_Unit_" . uniqid();
      $adUnit1->parentId = $rootAdUnitId;
      $adUnit1->adUnitSizes = array(
          new AdUnitSize(new Size(300, 250, FALSE), 'BROWSER'));
      $adUnit1->description = 'Ad unit description.';
      $adUnit1->targetWindow = 'BLANK';

      $adUnit2 = new AdUnit();
      $adUnit2->name = "Ad_Unit_" . uniqid();
      $adUnit2->parentId = $rootAdUnitId;
      $adUnit2->adUnitSizes = array(
          new AdUnitSize(new Size(300, 250, FALSE), 'BROWSER'));
      $adUnit2->description = 'Ad unit description.';
      $adUnit2->targetWindow = 'BLANK';

      $adUnit3 = new AdUnit();
      $adUnit3->name = "Ad_Unit_" . uniqid();
      $adUnit3->parentId = $rootAdUnitId;
      $adUnit3->adUnitSizes = array(
          new AdUnitSize(new Size(300, 250, FALSE), 'BROWSER'));
      $adUnit3->description = 'Ad unit description.';
      $adUnit3->targetWindow = 'BLANK';

      $adUnits = $inventoryService->createAdUnits(
          array($adUnit1, $adUnit2, $adUnit3));
      PlacementServiceTest::$adUnitId1 = $adUnits[0]->id;
      PlacementServiceTest::$adUnitId2 = $adUnits[1]->id;
      PlacementServiceTest::$adUnitId3 = $adUnits[2]->id;
    }
  }

  /**
   * Test whether we can create a placement.
   */
  public function testCreatePlacement() {
    $placement = new Placement();
    $placement->name = "Placement_" . uniqid();
    $placement->description = "Description.";
    $placement->targetedAdUnitIds = array(PlacementServiceTest::$adUnitId1,
        PlacementServiceTest::$adUnitId2);

    $testPlacement = $this->service->createPlacement($placement);

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacement->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacement->targetedAdUnitIds));
    $placement->targetedAdUnitIds = $testPlacement->targetedAdUnitIds;

    // Set the generated fields.
    $placement->id = $testPlacement->id;
    $placement->placementCode = $testPlacement->placementCode;
    $placement->status = $testPlacement->status;
    $placement->isAdSenseTargetingEnabled =
        $testPlacement->isAdSenseTargetingEnabled;
    $placement->adSenseTargetingLocale = $testPlacement->adSenseTargetingLocale;
    $placement->targetingSiteName = $testPlacement->targetingSiteName;
    $placement->targetingAdLocation = $testPlacement->targetingAdLocation;
    $placement->SiteTargetingInfoType = $testPlacement->SiteTargetingInfoType;

    $this->assertEquals($testPlacement, $placement);

    PlacementServiceTest::$placement = $testPlacement;
  }

  /**
   * Test whether we can fetch an existing placement.
   */
  public function testGetPlacement() {
    if (!isset(PlacementServiceTest::$placement)) {
      $this->testCreatePlacement();
    }

    $testPlacement =
        $this->service->getPlacement(PlacementServiceTest::$placement->id);

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacement->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacement->targetedAdUnitIds));
    $testPlacement->targetedAdUnitIds =
        PlacementServiceTest::$placement->targetedAdUnitIds;

    $this->assertEquals(PlacementServiceTest::$placement, $testPlacement);
  }

  /**
   * Test whether we can fetch a list of existing ad units that match given
   * statement.
   */
  public function testGetPlacementsByStatement() {
    if (!isset(PlacementServiceTest::$placement)) {
      $this->testCreatePlacement();
    }

    $filterStatement = new Statement("WHERE id = "
        . PlacementServiceTest::$placement->id . " ORDER BY name LIMIT 1");
    $page = $this->service->getPlacementsByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $page->results[0]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $page->results[0]->targetedAdUnitIds));
    $page->results[0]->targetedAdUnitIds =
        PlacementServiceTest::$placement->targetedAdUnitIds;

    $this->assertEquals(PlacementServiceTest::$placement, $page->results[0]);

    PlacementServiceTest::$placement = $page->results[0];
  }

  /**
   * Test whether we can update a placement.
   */
  public function testUpdatePlacement() {
    if (!isset(PlacementServiceTest::$placement)) {
      $this->testCreatePlacement();
    }

    $placement = clone PlacementServiceTest::$placement;
    $placement->targetedAdUnitIds[] = PlacementServiceTest::$adUnitId3;

    $testPlacement = $this->service->updatePlacement($placement);

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacement->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacement->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId3,
        $testPlacement->targetedAdUnitIds));
    $placement->targetedAdUnitIds = $testPlacement->targetedAdUnitIds;

    $this->assertEquals($placement, $testPlacement);

    PlacementServiceTest::$placement = $placement;
  }

  /**
   * Test whether we can deactivate placements.
   */
  public function testPerformPlacementAction() {
    if (!isset(PlacementServiceTest::$placement)) {
      $this->testCreatePlacement();
    }

    $action = new DeactivatePlacements();
    $filterStatement = new Statement("WHERE id = "
        . PlacementServiceTest::$placement->id . " LIMIT 1");

    $result = $this->service->performPlacementAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testPlacement =
        $this->service->getPlacement(PlacementServiceTest::$placement->id);

    $this->assertEquals('INACTIVE', $testPlacement->status);
  }
}
