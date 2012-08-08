<?php
/**
 * Functional tests for InventoryService.
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
 * Functional tests for InventoryService.
 * @backupStaticAttributes disabled
 */
class InventoryServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201104';
  private $user;
  private $service;

  private static $rootAdUnitId;
  private static $adUnit1;
  private static $adUnit2;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getInventoryService($this->version);

    if (!isset($rootAdUnitId)) {
      $networkService = $this->user->getNetworkService($this->version);
      $network = $networkService->getCurrentNetwork();
      InventoryServiceTest::$rootAdUnitId = $network->effectiveRootAdUnitId;
    }
  }

  /**
   * Test whether we can create an ad unit.
   */
  public function testCreateAdUnit() {
    $adUnit = new AdUnit();
    $adUnit->name = 'Ad_Unit_' . uniqid();
    $adUnit->parentId = InventoryServiceTest::$rootAdUnitId;
    $adUnit->sizes = array(new Size(300, 250));
    $adUnit->description = 'Ad unit description.';
    $adUnit->targetWindow = 'BLANK';

    $testAdUnit = $this->service->createAdUnit($adUnit);

    // Set the generated fields.
    $adUnit->id = $testAdUnit->id;
    $adUnit->targetWindow = $testAdUnit->targetWindow;
    $adUnit->status = $testAdUnit->status;
    $adUnit->adUnitCode = $testAdUnit->adUnitCode;
    $adUnit->inheritedAdSenseSettings = $testAdUnit->inheritedAdSenseSettings;

    $this->assertEquals($testAdUnit, $adUnit);

    InventoryServiceTest::$adUnit1 = $testAdUnit;
  }

  /**
   * Test whether we can create ad units.
   */
  public function testCreateAdUnits() {
    $adUnit1 = new AdUnit();
    $adUnit1->name = 'Ad_Unit_' . uniqid();
    $adUnit1->parentId = InventoryServiceTest::$rootAdUnitId;
    $adUnit1->sizes = array(new Size(300, 250));
    $adUnit1->description = 'Ad unit description.';
    $adUnit1->targetWindow = 'BLANK';

    $adUnit2 = new AdUnit();
    $adUnit2->name = 'Ad_Unit_' . uniqid();
    $adUnit2->parentId = InventoryServiceTest::$rootAdUnitId;
    $adUnit2->sizes = array(new Size(300, 250));
    $adUnit2->description = 'Ad unit description.';
    $adUnit2->targetWindow = 'BLANK';

    $testAdUnits = $this->service->createAdUnits(array($adUnit1, $adUnit2));

    // Set the generated fields.
    $adUnit1->id = $testAdUnits[0]->id;
    $adUnit1->targetWindow = $testAdUnits[0]->targetWindow;
    $adUnit1->status = $testAdUnits[0]->status;
    $adUnit1->adUnitCode = $testAdUnits[0]->adUnitCode;
    $adUnit1->inheritedAdSenseSettings =
        $testAdUnits[0]->inheritedAdSenseSettings;

    // Set the generated fields.
    $adUnit2->id = $testAdUnits[1]->id;
    $adUnit2->targetWindow = $testAdUnits[1]->targetWindow;
    $adUnit2->status = $testAdUnits[1]->status;
    $adUnit2->adUnitCode = $testAdUnits[1]->adUnitCode;
    $adUnit2->inheritedAdSenseSettings =
        $testAdUnits[1]->inheritedAdSenseSettings;

    $this->assertEquals($adUnit1, $testAdUnits[0]);
    $this->assertEquals($adUnit2, $testAdUnits[1]);

    InventoryServiceTest::$adUnit1 = $testAdUnits[0];
    InventoryServiceTest::$adUnit2 = $testAdUnits[1];
  }

  /**
   * Test whether we can fetch an existing ad unit.
   */
  public function testGetAdUnit() {
    if (!isset(InventoryServiceTest::$adUnit1)) {
      $this->testCreateAdUnit();
    }

    $testAdUnit = $this->service->getAdUnit(InventoryServiceTest::$adUnit1->id);

    $this->assertEquals(InventoryServiceTest::$adUnit1, $testAdUnit);
  }

  /**
   * Test whether we can fetch a list of existing ad units that match given
   * statment.
   */
  public function testGetAdUnitsByStatement() {
    if (!isset(InventoryServiceTest::$adUnit1)) {
      $this->testCreateAdUnits();
    }

    $filterStatement = new Statement('WHERE id = '
        . InventoryServiceTest::$adUnit1->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getAdUnitsByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(InventoryServiceTest::$adUnit1, $page->results[0]);
  }

  /**
   * Test whether we can update an ad unit.
   */
  public function testUpdateAdUnit() {
    if (!isset(InventoryServiceTest::$adUnit1)) {
      $this->testCreateAdUnit();
    }

    $adUnit = clone InventoryServiceTest::$adUnit1;
    $adUnit->inheritedAdSenseSettings->value->adSenseEnabled = TRUE;

    $testAdUnit = $this->service->updateAdUnit($adUnit);

    // Order of sizes is not preserved.
    $this->assertEquals($adUnit, $testAdUnit);

    InventoryServiceTest::$adUnit1 = $adUnit;
  }

  /**
   * Test whether we can update ad units.
   */
  public function testUpdateAdUnits() {
    if (!isset(InventoryServiceTest::$adUnit1)
        || !isset(InventoryServiceTest::$adUnit2)) {
      $this->testCreateAdUnits();
    }

    $adUnit1 = clone InventoryServiceTest::$adUnit1;
    $adUnit1->inheritedAdSenseSettings->value->adSenseEnabled = TRUE;

    $adUnit2 = clone InventoryServiceTest::$adUnit2;
    $adUnit2->inheritedAdSenseSettings->value->adSenseEnabled = TRUE;

    $testAdUnits = $this->service->updateAdUnits(array($adUnit1, $adUnit2));

    $this->assertEquals($adUnit1, $testAdUnits[0]);
    $this->assertEquals($adUnit2, $testAdUnits[1]);

    InventoryServiceTest::$adUnit1 = $adUnit1;
    InventoryServiceTest::$adUnit2 = $adUnit2;
  }

  /**
   * Test whether we can deactivate ad units.
   */
  public function testPerformAdUnitAction() {
    if (!isset(InventoryServiceTest::$adUnit1)) {
      $this->testCreateAdUnits();
    }

    $action = new DeactivateAdUnits();
    $filterStatement = new Statement('WHERE id = '
        . InventoryServiceTest::$adUnit1->id . ' LIMIT 1');

    $result = $this->service->performAdUnitAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testAdUnit = $this->service->getAdUnit(InventoryServiceTest::$adUnit1->id);

    $this->assertEquals('INACTIVE', $testAdUnit->status);
  }
}
