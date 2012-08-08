<?php
/**
 * Functional tests for LineItemService.
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
 * @subpackage v201107
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
require_once 'Google/Api/Ads/Dfp/Util/DateTimeUtils.php';
require_once 'Google/Api/Ads/Common/Util/MediaUtils.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for LineItemService.
 * @backupStaticAttributes disabled
 */
class LineItemServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201107';
  private $user;
  private $service;

  private static $orderId;
  private static $placementId;
  private static $creativeId;
  private static $companyId;
  private static $lica;
  private static $lineItem1;
  private static $lineItem2;
  private static $network;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('LineItemService', $this->version);

    if (!isset(LineItemServiceTest::$orderId)) {
      $companyService = $this->user->GetService('CompanyService', $this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);
      LineItemServiceTest::$companyId = $company->id;

      $userService = $this->user->GetService('UserService', $this->version);
      $filterStatement = new Statement('ORDER BY name LIMIT 500');
      $page = $userService->getUsersByStatement($filterStatement);

      $traffickerId = NULL;
      foreach ($page->results as $user) {
        switch ($user->roleName) {
          case 'Trafficker':
            if (!isset($traffickerId)) $traffickerId = $user->id;
            break;
        }
      }

      $orderService = $this->user->GetService('OrderService', $this->version);
      $order = new Order();
      $order->advertiserId = $company->id;
      $order->currencyCode = 'USD';
      $order->name = 'Order #' . uniqid();
      $order->traffickerId = $traffickerId;

      $order = $orderService->createOrder($order);
      LineItemServiceTest::$orderId = $order->id;

      // Approve order.
      $action = new ApproveOrders();
      $filterStatement = new Statement('WHERE id = '
          . LineItemServiceTest::$orderId . ' LIMIT 1');
      $result = $orderService->performOrderAction($action, $filterStatement);
    }

    if (!isset(LineItemServiceTest::$placementId)) {
      $networkService = $this->user->GetService('NetworkService', $this->version);
      $network = $networkService->getCurrentNetwork();
      $rootAdUnitId = $network->effectiveRootAdUnitId;

      $inventoryService = $this->user->GetService('InventoryService', $this->version);
      $adUnit = new AdUnit();
      $adUnit->name = 'Ad_Unit_' . uniqid();
      $adUnit->parentId = $rootAdUnitId;
      $adUnit->sizes = array(new Size(300, 250));
      $adUnit->description = 'Ad unit description.';
      $adUnit->targetWindow = 'BLANK';
      $adUnit = $inventoryService->createAdUnit($adUnit);

      $placementService = $this->user->GetService('PlacementService', $this->version);
      $placement = new Placement();
      $placement->name = "Placement_" . uniqid();
      $placement->description = "Description.";
      $placement->targetedAdUnitIds = array($adUnit->id);
      $placement = $placementService->createPlacement($placement);
      LineItemServiceTest::$placementId = $placement->id;
    }

    if (!isset(LineItemServiceTest::$creativeId)) {
      $creativeService = $this->user->GetService('CreativeService', $this->version);
      $creative = new ImageCreative();
      $creative->name = 'Image Creative #' . uniqid();
      $creative->advertiserId = LineItemServiceTest::$companyId;
      $creative->destinationUrl = 'http://google.com';
      $creative->imageName = 'image.jpg';
      $creative->size = new Size(300, 250);

      $imageData = MediaUtils::getBase64Data(dirname(__FILE__)
          . '/../../../../../../test_data/medium_rectangle.jpg');
      $creative->imageByteArray = $imageData;

      $creative = $creativeService->createCreative($creative);
      LineItemServiceTest::$creativeId = $creative->id;
    }

    if (!isset(LineItemServiceTest::$network)) {
      $networkService = $this->user->GetService('NetworkService', $this->version);
      LineItemServiceTest::$network = $networkService->getCurrentNetwork();
    }
  }

  /**
   * Test whether we can create a line item.
   */
  public function testCreateLineItem() {
    $lineItem = new LineItem();
    $lineItem->name = 'Line item #' . uniqid();
    $lineItem->orderId = LineItemServiceTest::$orderId;
    $lineItem->targeting = new Targeting();
    $lineItem->targeting->inventoryTargeting =  new InventoryTargeting(NULL,
        NULL, array(LineItemServiceTest::$placementId));
    $lineItem->creativeSizes = array(new Size(300, 250), new Size(120, 600));
    $lineItem->lineItemType = 'STANDARD';
    $lineItem->startDateTimeType = 'IMMEDIATELY';
    $lineItem->endDateTime =
        DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));
    $lineItem->costType = 'CPM';
    $lineItem->costPerUnit = new Money('USD', 2000000);
    $lineItem->unitsBought = 500000;
    $lineItem->unitType = 'IMPRESSIONS';
    $lineItem->allowOverbook = TRUE;

    $testLineItem = $this->service->createLineItem($lineItem);

    // Assert defaults.
    $this->assertEquals('ONE_OR_MORE', $testLineItem->roadblockingType);
    $this->assertEquals('EVEN', $testLineItem->creativeRotationType);
    $this->assertEquals('EVENLY', $testLineItem->deliveryRateType);
    $this->assertEquals(new Money('USD', 0), $testLineItem->valueCostPerUnit);

    // Order of creative sizes is not preserved.
    $this->assertTrue(
        in_array(new Size(300,250), $testLineItem->creativeSizes));
    $this->assertTrue(
        in_array(new Size(120,600), $testLineItem->creativeSizes));
    $lineItem->creativeSizes = $testLineItem->creativeSizes;

    // Set the generated fields.
    $lineItem->id = $testLineItem->id;
    $lineItem->budget = $testLineItem->budget;
    $lineItem->status = $testLineItem->status;
    $lineItem->reservationStatus = $testLineItem->reservationStatus;
    $lineItem->LineItemSummaryType = $testLineItem->LineItemSummaryType;
    $lineItem->creativeRotationType = $testLineItem->creativeRotationType;
    $lineItem->deliveryRateType = $testLineItem->deliveryRateType;
    $lineItem->roadblockingType = $testLineItem->roadblockingType;
    $lineItem->duration = $testLineItem->duration;
    $lineItem->valueCostPerUnit = $testLineItem->valueCostPerUnit;
    $lineItem->discount = $testLineItem->discount;
    $lineItem->discountType = $testLineItem->discountType;
    $lineItem->startDateTimeType = $testLineItem->startDateTimeType;
    $lineItem->orderName = $testLineItem->orderName;
    $lineItem->startDateTime = $testLineItem->startDateTime;
    $lineItem->endDateTime->timeZoneID = $testLineItem->endDateTime->timeZoneID;
    $lineItem->allowOverbook = $testLineItem->allowOverbook;
    $lineItem->targeting->technologyTargeting =
        $testLineItem->targeting->technologyTargeting;
    $lineItem->priority = $testLineItem->priority;
    $lineItem->contractedUnitsBought = $testLineItem->contractedUnitsBought;
    $lineItem->lastModifiedByApp = $testLineItem->lastModifiedByApp;

    $this->assertEquals($lineItem, $testLineItem);
    $this->assertEquals(LineItemServiceTest::$network->timeZone,
        $lineItem->endDateTime->timeZoneID);

    LineItemServiceTest::$lineItem1 = $lineItem;
  }

  /**
   * Test whether we can create a list of line items.
   */
  public function testCreateLineItems() {
    $lineItem1 = new LineItem();
    $lineItem1->name = 'Line item #' . uniqid();
    $lineItem1->orderId = LineItemServiceTest::$orderId;
    $lineItem1->targeting = new Targeting();
    $lineItem1->targeting->inventoryTargeting =  new InventoryTargeting(NULL,
        NULL, array(LineItemServiceTest::$placementId));
    $lineItem1->creativeSizes = array(new Size(300, 250), new Size(120, 600));
    $lineItem1->lineItemType = 'STANDARD';
    $lineItem1->startDateTimeType = 'IMMEDIATELY';
    $lineItem1->endDateTime =
        DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));
    $lineItem1->costType = 'CPM';
    $lineItem1->costPerUnit = new Money('USD', 2000000);
    $lineItem1->unitsBought = 500000;
    $lineItem1->unitType = 'IMPRESSIONS';
    $lineItem1->allowOverbook = TRUE;

    $lineItem2 = new LineItem();
    $lineItem2->name = 'Line item #' . uniqid();
    $lineItem2->orderId = LineItemServiceTest::$orderId;
    $lineItem2->targeting = new Targeting();
    $lineItem2->targeting->inventoryTargeting = new InventoryTargeting(NULL,
        NULL, array(LineItemServiceTest::$placementId));
    $lineItem2->creativeSizes = array(new Size(300, 250), new Size(120, 600));
    $lineItem2->lineItemType = 'STANDARD';
    $lineItem2->startDateTimeType = 'IMMEDIATELY';
    $lineItem2->endDateTime =
        DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));
    $lineItem2->costType = 'CPM';
    $lineItem2->costPerUnit = new Money('USD', 2000000);
    $lineItem2->unitsBought = 500000;
    $lineItem2->unitType = 'IMPRESSIONS';
    $lineItem2->allowOverbook = TRUE;

    $testLineItems =
        $this->service->createLineItems(array($lineItem1, $lineItem2));

    // Assert defaults.
    $this->assertEquals('NEEDS_CREATIVES', $testLineItems[0]->status);
    $this->assertEquals('ONE_OR_MORE', $testLineItems[0]->roadblockingType);
    $this->assertEquals('EVEN', $testLineItems[0]->creativeRotationType);
    $this->assertEquals('EVENLY', $testLineItems[0]->deliveryRateType);
    $this->assertEquals(new Money('USD', 0),
        $testLineItems[0]->valueCostPerUnit);

    $this->assertEquals('NEEDS_CREATIVES', $testLineItems[1]->status);
    $this->assertEquals('ONE_OR_MORE', $testLineItems[1]->roadblockingType);
    $this->assertEquals('EVEN', $testLineItems[1]->creativeRotationType);
    $this->assertEquals('EVENLY', $testLineItems[1]->deliveryRateType);
    $this->assertEquals(new Money('USD', 0),
        $testLineItems[1]->valueCostPerUnit);

    // Order of creative sizes is not preserved.
    $this->assertTrue(
        in_array(new Size(300,250), $testLineItems[0]->creativeSizes));
    $this->assertTrue(
        in_array(new Size(120,600), $testLineItems[0]->creativeSizes));
    $lineItem1->creativeSizes = $testLineItems[0]->creativeSizes;

    $this->assertTrue(
        in_array(new Size(300,250), $testLineItems[1]->creativeSizes));
    $this->assertTrue(
        in_array(new Size(120,600), $testLineItems[1]->creativeSizes));
    $lineItem2->creativeSizes = $testLineItems[1]->creativeSizes;

    // Set the generated fields.
    $lineItem1->id = $testLineItems[0]->id;
    $lineItem1->budget = $testLineItems[0]->budget;
    $lineItem1->status = $testLineItems[0]->status;
    $lineItem1->reservationStatus = $testLineItems[0]->reservationStatus;
    $lineItem1->LineItemSummaryType = $testLineItems[0]->LineItemSummaryType;
    $lineItem1->creativeRotationType = $testLineItems[0]->creativeRotationType;
    $lineItem1->deliveryRateType = $testLineItems[0]->deliveryRateType;
    $lineItem1->roadblockingType = $testLineItems[0]->roadblockingType;
    $lineItem1->duration = $testLineItems[0]->duration;
    $lineItem1->valueCostPerUnit = $testLineItems[0]->valueCostPerUnit;
    $lineItem1->discount = $testLineItems[0]->discount;
    $lineItem1->discountType = $testLineItems[0]->discountType;
    $lineItem1->startDateTimeType = $testLineItems[0]->startDateTimeType;
    $lineItem1->orderName = $testLineItems[0]->orderName;
    $lineItem1->startDateTime = $testLineItems[0]->startDateTime;
    $lineItem1->endDateTime->timeZoneID =
        $testLineItems[0]->endDateTime->timeZoneID;
    $lineItem1->allowOverbook = $testLineItems[0]->allowOverbook;
    $lineItem1->targeting->technologyTargeting =
        $testLineItems[0]->targeting->technologyTargeting;
    $lineItem1->priority = $testLineItems[0]->priority;
    $lineItem1->contractedUnitsBought =
        $testLineItems[0]->contractedUnitsBought;
    $lineItem1->lastModifiedByApp = $testLineItems[0]->lastModifiedByApp;

    $lineItem2->id = $testLineItems[1]->id;
    $lineItem2->budget = $testLineItems[1]->budget;
    $lineItem2->status = $testLineItems[1]->status;
    $lineItem2->reservationStatus = $testLineItems[1]->reservationStatus;
    $lineItem2->LineItemSummaryType = $testLineItems[1]->LineItemSummaryType;
    $lineItem2->creativeRotationType = $testLineItems[1]->creativeRotationType;
    $lineItem2->deliveryRateType = $testLineItems[1]->deliveryRateType;
    $lineItem2->roadblockingType = $testLineItems[1]->roadblockingType;
    $lineItem2->duration = $testLineItems[1]->duration;
    $lineItem2->valueCostPerUnit = $testLineItems[1]->valueCostPerUnit;
    $lineItem2->discount = $testLineItems[1]->discount;
    $lineItem2->discountType = $testLineItems[1]->discountType;
    $lineItem2->startDateTimeType = $testLineItems[1]->startDateTimeType;
    $lineItem2->orderName = $testLineItems[1]->orderName;
    $lineItem2->startDateTime = $testLineItems[1]->startDateTime;
    $lineItem2->endDateTime->timeZoneID =
        $testLineItems[1]->endDateTime->timeZoneID;
    $lineItem2->allowOverbook = $testLineItems[1]->allowOverbook;
    $lineItem2->targeting->technologyTargeting =
        $testLineItems[1]->targeting->technologyTargeting;
    $lineItem2->priority = $testLineItems[1]->priority;
    $lineItem2->contractedUnitsBought =
        $testLineItems[1]->contractedUnitsBought;
    $lineItem2->lastModifiedByApp = $testLineItems[1]->lastModifiedByApp;

    $this->assertEquals($lineItem1, $testLineItems[0]);
    $this->assertEquals($lineItem2, $testLineItems[1]);
    $this->assertEquals(LineItemServiceTest::$network->timeZone,
        $lineItem1->endDateTime->timeZoneID);
    $this->assertEquals(LineItemServiceTest::$network->timeZone,
        $lineItem2->endDateTime->timeZoneID);

    LineItemServiceTest::$lineItem1 = $lineItem1;
    LineItemServiceTest::$lineItem2 = $lineItem2;
  }

  /**
   * Test whether we can fetch an existing line item.
   */
  public function testGetLineItem() {
    if (!isset(LineItemServiceTest::$lineItem1)) {
      $this->testCreateLineItem();
    }

    $testLineItem =
        $this->service->getLineItem(LineItemServiceTest::$lineItem1->id);

    $this->assertEquals(LineItemServiceTest::$lineItem1, $testLineItem);
  }

  /**
   * Test whether we can fetch a list of existing line items that match given
   * statement.
   */
  public function testGetLineItemsByStatement() {
    if (!isset(LineItemServiceTest::$lineItem1)) {
      $this->testCreateLineItem();
    }

    $filterStatement = new Statement('WHERE id = '
        . LineItemServiceTest::$lineItem1->id
        . ' AND orderId = ' . LineItemServiceTest::$orderId
        . ' ORDER BY name LIMIT 1');
    $page = $this->service->getLineItemsByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(LineItemServiceTest::$lineItem1, $page->results[0]);
  }

  /**
   * Test whether we can update a line item.
   */
  public function testUpdateLineItem() {
    if (!isset(LineItemServiceTest::$lineItem1)) {
      $this->testCreateLineItem();
    }

    $lineItem = clone LineItemServiceTest::$lineItem1;
    $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
    $lineItem->allowOverbook = TRUE;

    $testLineItem =
        $this->service->updateLineItem($lineItem);

    $lineItem->allowOverbook = $testLineItem->allowOverbook;

    $this->assertEquals($lineItem, $testLineItem);

    LineItemServiceTest::$lineItem1 = $lineItem;
  }

  /**
   * Test whether we can update a list of line items.
   */
  public function testUpdateLineItems() {
    if (!isset(LineItemServiceTest::$lineItem1)
        || !isset(LineItemServiceTest::$lineItem2)) {
      $this->testCreateLineItems();
    }

    $lineItem1 = clone LineItemServiceTest::$lineItem1;
    $lineItem1->creativeRotationType = 'OPTIMIZED';

    $lineItem2 = clone LineItemServiceTest::$lineItem2;
    $lineItem2->creativeRotationType = 'OPTIMIZED';

    $testLineItems =
        $this->service->updateLineItems(array($lineItem1, $lineItem2));

    $lineItem1->allowOverbook = $testLineItems[0]->allowOverbook;
    $lineItem2->allowOverbook = $testLineItems[1]->allowOverbook;

    $this->assertEquals($lineItem1, $testLineItems[0]);
    $this->assertEquals($lineItem2, $testLineItems[1]);

    LineItemServiceTest::$lineItem1 = $lineItem1;
    LineItemServiceTest::$lineItem2 = $lineItem2;
  }

  /**
   * Test whether we can activate line items.
   */
  public function testPerformLineItemAction() {
    if (!isset(LineItemServiceTest::$lineItem1)) {
      $this->testCreateLineItem();
    }

    // A line item must have an associated LICA to be activated.
    if (!isset(LineItemServiceTest::$lica)) {
      $licaService =
          $this->user->GetService('LineItemCreativeAssociationService', $this->version);
      $lica =
          new LineItemCreativeAssociation(LineItemServiceTest::$lineItem1->id,
              LineItemServiceTest::$creativeId);
      LineItemServiceTest::$lica =
          $licaService->createLineItemCreativeAssociation($lica);
    }

    $action = new ActivateLineItems();
    $filterStatement = new Statement('WHERE id = '
        . LineItemServiceTest::$lineItem1->id
        . ' AND orderId = ' . LineItemServiceTest::$orderId
        . ' LIMIT 1');

    $result = $this->service->performLineItemAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testLineItem =
        $this->service->getLineItem(LineItemServiceTest::$lineItem1->id);

    $this->assertEquals('READY', $testLineItem->status);
  }
}
