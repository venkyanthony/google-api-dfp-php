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
 * Functional tests for InventoryService.
 * @backupStaticAttributes disabled
 */
class InventoryServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $rootAdUnitId;
  private static $adUnit;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('InventoryService', $this->version);

    if (!isset($rootAdUnitId)) {
      $networkService = $this->user->GetService('NetworkService', $this->version);
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
    $adUnit->adUnitSizes = array(
        new AdUnitSize(new Size(300, 250, FALSE), 'BROWSER'));
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

    InventoryServiceTest::$adUnit = $testAdUnit;
  }

  /**
   * Test whether we can fetch an existing ad unit.
   */
  public function testGetAdUnit() {
    if (!isset(InventoryServiceTest::$adUnit)) {
      $this->testCreateAdUnit();
    }

    $testAdUnit = $this->service->getAdUnit(InventoryServiceTest::$adUnit->id);

    $this->assertEquals(InventoryServiceTest::$adUnit, $testAdUnit);
  }

  /**
   * Test whether we can fetch a list of existing ad units that match given
   * statment.
   */
  public function testGetAdUnitsByStatement() {
    if (!isset(InventoryServiceTest::$adUnit)) {
      $this->testCreateAdUnits();
    }

    $filterStatement = new Statement('WHERE id = '
        . InventoryServiceTest::$adUnit->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getAdUnitsByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(InventoryServiceTest::$adUnit, $page->results[0]);
  }

  /**
   * Test whether we can update an ad unit.
   */
  public function testUpdateAdUnit() {
    if (!isset(InventoryServiceTest::$adUnit)) {
      $this->testCreateAdUnit();
    }

    $adUnit = clone InventoryServiceTest::$adUnit;
    $adUnit->inheritedAdSenseSettings->value->adSenseEnabled = TRUE;

    $testAdUnit = $this->service->updateAdUnit($adUnit);

    // Order of sizes is not preserved.
    $this->assertEquals($adUnit, $testAdUnit);

    InventoryServiceTest::$adUnit = $adUnit;
  }

  /**
   * Test whether we can deactivate ad units.
   */
  public function testPerformAdUnitAction() {
    if (!isset(InventoryServiceTest::$adUnit)) {
      $this->testCreateAdUnits();
    }

    $action = new DeactivateAdUnits();
    $filterStatement = new Statement('WHERE id = '
        . InventoryServiceTest::$adUnit->id . ' LIMIT 1');

    $result = $this->service->performAdUnitAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testAdUnit = $this->service->getAdUnit(InventoryServiceTest::$adUnit->id);

    $this->assertEquals('INACTIVE', $testAdUnit->status);
  }

  /**
   * Test whether we can get ad unit sizes.
   */
  public function testGetAdUnitSizes() {
    if (!isset(InventoryServiceTest::$adUnit)) {
      $this->testCreateAdUnits();
    }

    $adUnitSizes = $this->service->GetAdUnitSizes();

    $this->assertGreaterThanOrEqual(1, sizeof($adUnitSizes));
    $this->assertNotNull($adUnitSizes[0]->size);
    $this->assertNotNull($adUnitSizes[0]->size->width);
    $this->assertNotNull($adUnitSizes[0]->size->height);
  }
}
