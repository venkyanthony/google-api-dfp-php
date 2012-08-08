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
 * @subpackage v201104
 * @category   WebServices
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
  private $version = 'v201104';
  private $user;
  private $service;

  private static $adUnitId1;
  private static $adUnitId2;
  private static $adUnitId3;
  private static $adUnitId4;
  private static $placement1;
  private static $placement2;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getPlacementService($this->version);

    if (!isset(PlacementServiceTest::$adUnitId1)
        || !isset(PlacementServiceTest::$adUnitId2)) {
      $networkService = $this->user->getNetworkService($this->version);
      $network = $networkService->getCurrentNetwork();
      $rootAdUnitId = $network->effectiveRootAdUnitId;

      $inventoryService = $this->user->getInventoryService($this->version);

      $adUnit1 = new AdUnit();
      $adUnit1->name = "Ad_Unit_" . uniqid();
      $adUnit1->parentId = $rootAdUnitId;
      $adUnit1->sizes = array(new Size(300, 250));
      $adUnit1->description = 'Ad unit description.';
      $adUnit1->targetWindow = 'BLANK';

      $adUnit2 = new AdUnit();
      $adUnit2->name = "Ad_Unit_" . uniqid();
      $adUnit2->parentId = $rootAdUnitId;
      $adUnit2->sizes = array(new Size(300, 250));
      $adUnit2->description = 'Ad unit description.';
      $adUnit2->targetWindow = 'BLANK';

      $adUnit3 = new AdUnit();
      $adUnit3->name = "Ad_Unit_" . uniqid();
      $adUnit3->parentId = $rootAdUnitId;
      $adUnit3->sizes = array(new Size(300, 250));
      $adUnit3->description = 'Ad unit description.';
      $adUnit3->targetWindow = 'BLANK';

      $adUnit4 = new AdUnit();
      $adUnit4->name = "Ad_Unit_" . uniqid();
      $adUnit4->parentId = $rootAdUnitId;
      $adUnit4->sizes = array(new Size(300, 250));
      $adUnit4->description = 'Ad unit description.';
      $adUnit4->targetWindow = 'BLANK';

      $adUnits = $inventoryService->createAdUnits(array($adUnit1,
          $adUnit2, $adUnit3, $adUnit4));
      PlacementServiceTest::$adUnitId1 = $adUnits[0]->id;
      PlacementServiceTest::$adUnitId2 = $adUnits[1]->id;
      PlacementServiceTest::$adUnitId3 = $adUnits[2]->id;
      PlacementServiceTest::$adUnitId4 = $adUnits[3]->id;
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

    PlacementServiceTest::$placement1 = $testPlacement;
  }

  /**
   * Test whether we can create placements.
   */
  public function testCreatePlacements() {
    $placement1 = new Placement();
    $placement1->name = "Placement_" . uniqid();
    $placement1->description = "Description.";
    $placement1->targetedAdUnitIds = array(PlacementServiceTest::$adUnitId1,
        PlacementServiceTest::$adUnitId2);

    $placement2 = new Placement();
    $placement2->name = "Placement_" . uniqid();
    $placement2->description = "Description.";
    $placement2->targetedAdUnitIds = array(PlacementServiceTest::$adUnitId1,
        PlacementServiceTest::$adUnitId2);

    $testPlacements =
        $this->service->createPlacements(array($placement1, $placement2));

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacements[0]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacements[0]->targetedAdUnitIds));
    $placement1->targetedAdUnitIds = $testPlacements[0]->targetedAdUnitIds;

    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacements[1]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacements[1]->targetedAdUnitIds));
    $placement2->targetedAdUnitIds = $testPlacements[1]->targetedAdUnitIds;

    // Set the generated fields.
    $placement1->id = $testPlacements[0]->id;
    $placement1->placementCode = $testPlacements[0]->placementCode;
    $placement1->status = $testPlacements[0]->status;
    $placement1->isAdSenseTargetingEnabled =
        $testPlacements[0]->isAdSenseTargetingEnabled;
    $placement1->adSenseTargetingLocale =
        $testPlacements[0]->adSenseTargetingLocale;
    $placement1->targetingSiteName = $testPlacements[0]->targetingSiteName;
    $placement1->targetingAdLocation = $testPlacements[0]->targetingAdLocation;
    $placement1->SiteTargetingInfoType =
        $testPlacements[0]->SiteTargetingInfoType;

    $placement2->id = $testPlacements[1]->id;
    $placement2->placementCode = $testPlacements[1]->placementCode;
    $placement2->status = $testPlacements[1]->status;
    $placement2->isAdSenseTargetingEnabled =
        $testPlacements[1]->isAdSenseTargetingEnabled;
    $placement2->adSenseTargetingLocale =
        $testPlacements[1]->adSenseTargetingLocale;
    $placement2->targetingSiteName = $testPlacements[1]->targetingSiteName;
    $placement2->targetingAdLocation = $testPlacements[1]->targetingAdLocation;
    $placement2->SiteTargetingInfoType =
        $testPlacements[1]->SiteTargetingInfoType;

    $this->assertEquals($testPlacements[0], $placement1);
    $this->assertEquals($testPlacements[1], $placement2);

    PlacementServiceTest::$placement1 = $testPlacements[0];
    PlacementServiceTest::$placement2 = $testPlacements[1];
  }

  /**
   * Test whether we can fetch an existing placement.
   */
  public function testGetPlacement() {
    if (!isset(PlacementServiceTest::$placement1)) {
      $this->testCreatePlacement();
    }

    $testPlacement =
        $this->service->getPlacement(PlacementServiceTest::$placement1->id);

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacement->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacement->targetedAdUnitIds));
    $testPlacement->targetedAdUnitIds =
        PlacementServiceTest::$placement1->targetedAdUnitIds;

    $this->assertEquals(PlacementServiceTest::$placement1, $testPlacement);
  }

  /**
   * Test whether we can fetch a list of existing ad units that match given
   * statement.
   */
  public function testGetPlacementsByStatement() {
    if (!isset(PlacementServiceTest::$placement1)) {
      $this->testCreatePlacement();
    }

    $filterStatement = new Statement("WHERE id = "
        . PlacementServiceTest::$placement1->id . " ORDER BY name LIMIT 1");
    $page = $this->service->getPlacementsByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $page->results[0]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $page->results[0]->targetedAdUnitIds));
    $page->results[0]->targetedAdUnitIds =
        PlacementServiceTest::$placement1->targetedAdUnitIds;

    $this->assertEquals(PlacementServiceTest::$placement1, $page->results[0]);

    PlacementServiceTest::$placement1 = $page->results[0];
  }

  /**
   * Test whether we can update a placement.
   */
  public function testUpdatePlacement() {
    if (!isset(PlacementServiceTest::$placement1)) {
      $this->testCreatePlacement();
    }

    $placement = clone PlacementServiceTest::$placement1;
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

    PlacementServiceTest::$placement1 = $placement;
  }

  /**
   * Test whether we can update placements.
   */
  public function testUpdatePlacements() {
    if (!isset(PlacementServiceTest::$placement1)
        || !isset(PlacementServiceTest::$placement2)) {
      $this->testCreatePlacements();
    }

    $placement1 = clone PlacementServiceTest::$placement1;
    $placement1->targetedAdUnitIds[] = PlacementServiceTest::$adUnitId4;

    $placement2 = clone PlacementServiceTest::$placement2;
    $placement2->targetedAdUnitIds[] = PlacementServiceTest::$adUnitId4;

    $testPlacements =
        $this->service->updatePlacements(array($placement1, $placement2));

    // Order of ad units is not preserved.
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacements[0]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacements[0]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId4,
        $testPlacements[0]->targetedAdUnitIds));
    $placement1->targetedAdUnitIds = $testPlacements[0]->targetedAdUnitIds;

    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId1,
        $testPlacements[1]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId2,
        $testPlacements[1]->targetedAdUnitIds));
    $this->assertTrue(in_array(PlacementServiceTest::$adUnitId4,
        $testPlacements[1]->targetedAdUnitIds));
    $placement2->targetedAdUnitIds = $testPlacements[1]->targetedAdUnitIds;

    $this->assertEquals($placement1, $testPlacements[0]);
    $this->assertEquals($placement2, $testPlacements[1]);

    PlacementServiceTest::$placement1 = $placement1;
    PlacementServiceTest::$placement2 = $placement2;
  }

  /**
   * Test whether we can deactivate placements.
   */
  public function testPerformPlacementAction() {
    if (!isset(PlacementServiceTest::$placement1)) {
      $this->testCreatePlacement();
    }

    $action = new DeactivatePlacements();
    $filterStatement = new Statement("WHERE id = "
        . PlacementServiceTest::$placement1->id . " LIMIT 1");

    $result = $this->service->performPlacementAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testPlacement =
        $this->service->getPlacement(PlacementServiceTest::$placement1->id);

    $this->assertEquals('INACTIVE', $testPlacement->status);
  }
}
