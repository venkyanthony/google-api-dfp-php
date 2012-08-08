<?php
/**
 * Functional tests for ForecastService.
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
 * @subpackage v201103
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
require_once 'Google/Api/Ads/Dfp/Util/DateTimeUtils.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for ForecastService.
 * @backupStaticAttributes disabled
 */
class ForecastServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201103';
  private $user;
  private $service;

  private static $placementId;
  private static $lineItemId;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getForecastService($this->version);

    if (!isset(ForecastServiceTest::$lineItemId)) {
      // Get trafficker.
      $userService = $this->user->getUserService($this->version);
      $filterStatement = new Statement('ORDER BY name LIMIT 500');
      $page = $userService->getUsersByStatement($filterStatement);

      foreach ($page->results as $user) {
        if ($user->roleName == 'Trafficker') {
          $traffickerId = $user->id;
        }
      }
      $this->assertTrue(isset($traffickerId));

      // Create company.
      $companyService = $this->user->getCompanyService($this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);

      // Create order.
      $orderService = $this->user->getOrderService($this->version);
      $order = new Order();
      $order->advertiserId = $company->id;
      $order->currencyCode = 'USD';
      $order->name = 'Order #' . uniqid();
      $order->traffickerId = $traffickerId;
      $order = $orderService->createOrder($order);

      // Get the effective root ad unit's ID.
      $networkService = $this->user->GetNetworkService($this->version);
      $network = $networkService->getCurrentNetwork();
      $effectiveRootAdUnitId = $network->effectiveRootAdUnitId;

      // Create ad unit.
      $inventoryService = $this->user->getInventoryService($this->version);
      $adUnit = new AdUnit();
      $adUnit->name = 'Ad_Unit_' . uniqid();
      $adUnit->parentId = $effectiveRootAdUnitId;
      $adUnit->sizes = array(new Size(300, 250));
      $adUnit->description = 'Ad unit description.';
      $adUnit->targetWindow = 'BLANK';
      $adUnit = $inventoryService->createAdUnit($adUnit);

      // Create placement.
      $placementService = $this->user->getPlacementService($this->version);
      $placement = new Placement();
      $placement->name = 'Placement_' . uniqid();
      $placement->description = 'Description.';
      $placement->targetedAdUnitIds = array($adUnit->id);
      $placement = $placementService->createPlacement($placement);
      ForecastServiceTest::$placementId = $placement->id;

      // Create line item.
      $lineItemService = $this->user->getLineItemService($this->version);
      $lineItem = new LineItem();
      $lineItem->name = 'Line item #' . uniqid();
      $lineItem->orderId = $order->id;
      $targeting = new Targeting();
      $targeting->inventoryTargeting =
          new InventoryTargeting(NULL, NULL, array($placement->id));
      $lineItem->targeting = $targeting;
      $lineItem->creativeSizes = array(new Size(300, 250), new Size(120, 600));
      $lineItem->lineItemType = 'STANDARD';
      $lineItem->startDateTimeType = 'IMMEDIATELY';
      $lineItem->endDateTime =
          DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));
      $lineItem->costType = 'CPM';
      $lineItem->costPerUnit = new Money('USD', 2000000);
      $lineItem->unitsBought = 500000;
      $lineItem->unitType = 'IMPRESSIONS';
      $lineItem = $lineItemService->createLineItem($lineItem);

      ForecastServiceTest::$lineItemId = $lineItem->id;
    }
  }

  /**
   * Test whether we can get a forecast for a prospective line item.
   */
  public function testGetForecast() {
    // Create prospective line item.
    $lineItem = new LineItem();
    $lineItem->lineItemType = 'SPONSORSHIP';
    $targeting = new Targeting();
    $targeting->inventoryTargeting = new InventoryTargeting(NULL, NULL,
        array(ForecastServiceTest::$placementId));
    $lineItem->targeting = $targeting;

    // Set the size of creatives that can be associated with this line item.
    $lineItem->creativeSizes = array(new Size(300, 250, false));

    // Set the line item's time to be now until the projected end date time.
    $lineItem->startDateTimeType = 'IMMEDIATELY';
    $lineItem->endDateTime =
        DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));

    // Set the line item to use 50% of the impressions.
    $lineItem->unitType = 'IMPRESSIONS';
    $lineItem->unitsBought = 50;

    // Set the cost type to match the unit type.
    $lineItem->costType = 'CPM';

    // Get forecast for line item.
    $forecast = $this->service->getForecast($lineItem);

    $this->assertTrue(isset($forecast->unitType));
    $this->assertGreaterThan(0, $forecast->availableUnits);
    $this->assertGreaterThan(0, $forecast->matchedUnits);
  }

  /**
   * Test whether we can get a forecast for an existing line item.
   */
  public function testGetForecastById() {
    // Get forecast for line item.
    $forecast =
        $this->service->getForecastById(ForecastServiceTest::$lineItemId);

    $this->assertTrue(isset($forecast->unitType));
    $this->assertGreaterThan(0, $forecast->availableUnits);
    $this->assertGreaterThan(0, $forecast->matchedUnits);
  }
}
