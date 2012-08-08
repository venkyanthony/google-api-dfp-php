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
require_once 'Google/Api/Ads/Dfp/Util/DateTimeUtils.php';
require_once 'Google/Api/Ads/Common/Util/MediaUtils.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for LineItemService.
 * @backupStaticAttributes disabled
 */
class LineItemServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $orderId;
  private static $placementId;
  private static $creativeId;
  private static $companyId;
  private static $lica;
  private static $lineItem;
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
      $adUnit->adUnitSizes = array(
          new AdUnitSize(new Size(300, 250, FALSE), 'BROWSER'));
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
    $lineItem->creativePlaceholders = array(
        new CreativePlaceholder(new Size(300, 250)));
    $lineItem->lineItemType = 'STANDARD';
    $lineItem->startDateTimeType = 'IMMEDIATELY';
    $lineItem->endDateTime =
        DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));
    $lineItem->costType = 'CPM';
    $lineItem->costPerUnit = new Money('USD', 2000000);
    $lineItem->unitsBought = 500000;
    $lineItem->unitType = 'IMPRESSIONS';
    $lineItem->allowOverbook = TRUE;
    $lineItem->environmentType = 'BROWSER';
    $lineItem->companionDeliveryOption = 'UNKNOWN';

    $testLineItem = $this->service->createLineItem($lineItem);

    // Assert defaults.
    $this->assertEquals('ONE_OR_MORE', $testLineItem->roadblockingType);
    $this->assertEquals('EVEN', $testLineItem->creativeRotationType);
    $this->assertEquals('EVENLY', $testLineItem->deliveryRateType);
    $this->assertEquals(new Money('USD', 0), $testLineItem->valueCostPerUnit);

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
    $lineItem->creativePlaceholders[0]->id =
        $testLineItem->creativePlaceholders[0]->id;

    $this->assertEquals($lineItem, $testLineItem);
    $this->assertEquals(LineItemServiceTest::$network->timeZone,
        $lineItem->endDateTime->timeZoneID);

    LineItemServiceTest::$lineItem = $lineItem;
  }

  /**
   * Test whether we can fetch an existing line item.
   */
  public function testGetLineItem() {
    if (!isset(LineItemServiceTest::$lineItem)) {
      $this->testCreateLineItem();
    }

    $testLineItem =
        $this->service->getLineItem(LineItemServiceTest::$lineItem->id);

    $this->assertEquals(LineItemServiceTest::$lineItem, $testLineItem);
  }

  /**
   * Test whether we can fetch a list of existing line items that match given
   * statement.
   */
  public function testGetLineItemsByStatement() {
    if (!isset(LineItemServiceTest::$lineItem)) {
      $this->testCreateLineItem();
    }

    $filterStatement = new Statement('WHERE id = '
        . LineItemServiceTest::$lineItem->id
        . ' AND orderId = ' . LineItemServiceTest::$orderId
        . ' ORDER BY name LIMIT 1');
    $page = $this->service->getLineItemsByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(LineItemServiceTest::$lineItem, $page->results[0]);
  }

  /**
   * Test whether we can update a line item.
   */
  public function testUpdateLineItem() {
    if (!isset(LineItemServiceTest::$lineItem)) {
      $this->testCreateLineItem();
    }

    $lineItem = clone LineItemServiceTest::$lineItem;
    $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
    $lineItem->allowOverbook = TRUE;

    $testLineItem =
        $this->service->updateLineItem($lineItem);

    $lineItem->allowOverbook = $testLineItem->allowOverbook;

    $this->assertEquals($lineItem, $testLineItem);

    LineItemServiceTest::$lineItem = $lineItem;
  }

  /**
   * Test whether we can activate line items.
   */
  public function testPerformLineItemAction() {
    if (!isset(LineItemServiceTest::$lineItem)) {
      $this->testCreateLineItem();
    }

    // A line item must have an associated LICA to be activated.
    if (!isset(LineItemServiceTest::$lica)) {
      $licaService =
          $this->user->GetService('LineItemCreativeAssociationService', $this->version);
      $lica =
          new LineItemCreativeAssociation(LineItemServiceTest::$lineItem->id,
              LineItemServiceTest::$creativeId);
      LineItemServiceTest::$lica =
          $licaService->createLineItemCreativeAssociation($lica);
    }

    $action = new ActivateLineItems();
    $filterStatement = new Statement('WHERE id = '
        . LineItemServiceTest::$lineItem->id
        . ' AND orderId = ' . LineItemServiceTest::$orderId
        . ' LIMIT 1');

    $result = $this->service->performLineItemAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testLineItem =
        $this->service->getLineItem(LineItemServiceTest::$lineItem->id);

    $this->assertEquals('READY', $testLineItem->status);
  }
}
