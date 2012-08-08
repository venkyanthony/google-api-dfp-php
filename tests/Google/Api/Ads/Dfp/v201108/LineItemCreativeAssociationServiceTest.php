<?php
/**
 * Functional tests for LineItemCreativeAssociationService.
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
 * Functional tests for LineItemCreativeAssociationService.
 * @backupStaticAttributes disabled
 */
class LineItemCreativeAssociationServiceTest
    extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $companyId;
  private static $creative;
  private static $lineItemId;
  private static $lica;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service =
        $this->user->GetService('LineItemCreativeAssociationService', $this->version);

    if (!isset(LineItemCreativeAssociationServiceTest::$creative)) {
      $companyService = $this->user->GetService('CompanyService', $this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);
      LineItemCreativeAssociationServiceTest::$companyId = $company->id;

      $imageData = MediaUtils::getBase64Data(dirname(__FILE__)
          . '/../../../../../../test_data/medium_rectangle.jpg');

      $creativeService = $this->user->GetService('CreativeService', $this->version);
      $creative = new ImageCreative();
      $creative->name = 'Image Creative #' . uniqid();
      $creative->advertiserId =
          LineItemCreativeAssociationServiceTest::$companyId;
      $creative->destinationUrl = 'http://google.com';
      $creative->imageName = 'medium_square.jpg';
      $creative->imageByteArray = $imageData;
      $creative->size = new Size(300, 250);
      $creatives = $creativeService->createCreatives(array($creative));
      LineItemCreativeAssociationServiceTest::$creative = $creatives[0];
    }

    if (!isset(LineItemCreativeAssociationServiceTest::$lineItemId)) {
      $userService = $this->user->GetService('UserService', $this->version);
      $filterStatement = new Statement('ORDER BY name LIMIT 500');
      $page = $userService->getUsersByStatement($filterStatement);

      foreach ($page->results as $user) {
        switch ($user->roleName) {
          case 'Trafficker':
            if (!isset($this->traffickerId)) {
              $traffickerId = $user->id;
            }
            break;
        }
      }

      $orderService = $this->user->GetService('OrderService', $this->version);
      $order = new Order();
      $order->advertiserId =
          LineItemCreativeAssociationServiceTest::$companyId;
      $order->currencyCode = 'USD';
      $order->name = 'Order #' . uniqid();
      $order->traffickerId = $traffickerId;
      $order = $orderService->createOrder($order);

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

      $lineItemService = $this->user->GetService('LineItemService', $this->version);
      $lineItem = new LineItem();
      $lineItem->name = 'Line item #' . uniqid();
      $lineItem->orderId = $order->id;
      $lineItem->targeting = new Targeting();
      $lineItem->targeting->inventoryTargeting =
          new InventoryTargeting(NULL, NULL, array($placement->id));
      $lineItem->creativePlaceholders = array(
          new CreativePlaceholder(new Size(300, 250)),
          new CreativePlaceholder(new Size(120, 600)));
      $lineItem->lineItemType = 'STANDARD';
      $lineItem->startDateTimeType = 'IMMEDIATELY';
      $lineItem->endDateTime =
          DateTimeUtils::GetDfpDateTime(new DateTime('+1 week'));
      $lineItem->costType = 'CPM';
      $lineItem->costPerUnit = new Money('USD', 2000000);
      $lineItem->unitsBought = 500000;
      $lineItem->unitType = 'IMPRESSIONS';
      $lineItem = $lineItemService->createLineItem($lineItem);

      LineItemCreativeAssociationServiceTest::$lineItemId = $lineItem->id;
    }
  }

  /**
   * Test whether we can create a line item creative association.
   */
  public function testCreateLineItemCreativeAssociation() {
    $lica = new LineItemCreativeAssociation();
    $lica->lineItemId = LineItemCreativeAssociationServiceTest::$lineItemId;
    $lica->creativeId = LineItemCreativeAssociationServiceTest::$creative->id;

    $testLica = $this->service->createLineItemCreativeAssociation($lica);
    LineItemCreativeAssociationServiceTest::$lica = $lica;

    // Assert the default status.
    $this->assertEquals('ACTIVE', $testLica->status);

    // Set the generated fields.
    $lica->status = $testLica->status;
    $lica->startDateTimeType = $testLica->startDateTimeType;

    $this->assertEquals($lica, $testLica);
  }

  /**
   * Test whether we can fetch an existing line item creative association.
   */
  public function testGetLineItemCreativeAssociation() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica)) {
      $this->testCreateLineItemCreativeAssociation();
    }

    $testLica = $this->service->getLineItemCreativeAssociation(
        LineItemCreativeAssociationServiceTest::$lica->lineItemId,
        LineItemCreativeAssociationServiceTest::$lica->creativeId);

    // TODO(api.arogal): bug 2047999.
    LineItemCreativeAssociationServiceTest::$lica->
        manualCreativeRotationWeight = $testLica->manualCreativeRotationWeight;

    $this->assertEquals(LineItemCreativeAssociationServiceTest::$lica,
        $testLica);
  }

  /**
   * Test whether we can fetch a list of existing line item creative
   * associations that match given statement.
   */
  public function testGetLineItemCreativeAssociationsByStatement() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica)) {
      $this->testCreateLineItemCreativeAssociation();
    }

    $filterStatement = new Statement('WHERE lineItemId = '
        . LineItemCreativeAssociationServiceTest::$lica->lineItemId
        . ' AND creativeId = '
        . LineItemCreativeAssociationServiceTest::$lica->creativeId
        . ' LIMIT 1');
    $page = $this->service->getLineItemCreativeAssociationsByStatement(
        $filterStatement);

    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(LineItemCreativeAssociationServiceTest::$lica,
        $page->results[0]);
  }

  /**
   * Test whether we can update a line item creative association.
   */
  public function testUpdateLineItemCreativeAssociation() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica)) {
      $this->testCreateLineItemCreativeAssociation();
    }

    $lica = clone LineItemCreativeAssociationServiceTest::$lica;
    $lica->destinationUrl = 'http://news.google.com';

    $testLica = $this->service->updateLineItemCreativeAssociation($lica);

    $this->assertEquals($lica, $testLica);

    LineItemCreativeAssociationServiceTest::$lica = $lica;
  }

  /**
   * Test whether we can deactivate line item creative associations.
   */
  public function testPerformLineItemCreativeAssociationAction() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica)) {
      $this->testCreateLineItemCreativeAssociations();
    }

    $action = new DeactivateLineItemCreativeAssociations();
    $filterStatement = new Statement('WHERE lineItemId = '
        . LineItemCreativeAssociationServiceTest::$lica->lineItemId
        . ' AND creativeId = '
        . LineItemCreativeAssociationServiceTest::$lica->creativeId
        . ' LIMIT 1');

    $result = $this->service->performLineItemCreativeAssociationAction($action,
        $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testLica = $this->service->getLineItemCreativeAssociation(
        LineItemCreativeAssociationServiceTest::$lica->lineItemId,
        LineItemCreativeAssociationServiceTest::$lica->creativeId);

    $this->assertEquals('INACTIVE', $testLica->status);
  }
}
