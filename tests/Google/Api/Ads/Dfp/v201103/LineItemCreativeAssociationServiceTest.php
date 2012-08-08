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
require_once 'Google/Api/Ads/Common/Util/MediaUtils.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for LineItemCreativeAssociationService.
 * @backupStaticAttributes disabled
 */
class LineItemCreativeAssociationServiceTest
    extends PHPUnit_Framework_TestCase {
  private $version = 'v201103';
  private $user;
  private $service;

  private static $companyId;
  private static $creative1;
  private static $creative2;
  private static $creative3;
  private static $lineItemId;
  private static $lica1;
  private static $lica2;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service =
        $this->user->getLineItemCreativeAssociationService($this->version);

    if (!isset(LineItemCreativeAssociationServiceTest::$creative1)
        || !isset(LineItemCreativeAssociationServiceTest::$creative2)
        || !isset(LineItemCreativeAssociationServiceTest::$creative3)) {
      $companyService = $this->user->getCompanyService($this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);
      LineItemCreativeAssociationServiceTest::$companyId = $company->id;

      $imageData = MediaUtils::getBase64Data(dirname(__FILE__)
          . '/../../../../../../test_data/medium_rectangle.jpg');

      $creativeService = $this->user->getCreativeService($this->version);
      $creatives = array();
      for ($i = 0; $i < 3; $i++) {
        $creative = new ImageCreative();
        $creative->name = 'Image Creative #' . uniqid();
        $creative->advertiserId =
            LineItemCreativeAssociationServiceTest::$companyId;
        $creative->destinationUrl = 'http://google.com';
        $creative->imageName = 'medium_square.jpg';
        $creative->imageByteArray = $imageData;
        $creative->size = new Size(300, 250);
        array_push($creatives, $creative);
      }

      $creatives = $creativeService->createCreatives($creatives);
      LineItemCreativeAssociationServiceTest::$creative1 = $creatives[0];
      LineItemCreativeAssociationServiceTest::$creative2 = $creatives[1];
      LineItemCreativeAssociationServiceTest::$creative3 = $creatives[2];
    }

    if (!isset(LineItemCreativeAssociationServiceTest::$lineItemId)) {
      $userService = $this->user->getUserService($this->version);
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

      $orderService = $this->user->getOrderService($this->version);
      $order = new Order();
      $order->advertiserId =
          LineItemCreativeAssociationServiceTest::$companyId;
      $order->currencyCode = 'USD';
      $order->name = 'Order #' . uniqid();
      $order->traffickerId = $traffickerId;
      $order = $orderService->createOrder($order);

      $networkService = $this->user->getNetworkService($this->version);
      $network = $networkService->getCurrentNetwork();
      $rootAdUnitId = $network->effectiveRootAdUnitId;

      $inventoryService = $this->user->getInventoryService($this->version);
      $adUnit = new AdUnit();
      $adUnit->name = 'Ad_Unit_' . uniqid();
      $adUnit->parentId = $rootAdUnitId;
      $adUnit->sizes = array(new Size(300, 250));
      $adUnit->description = 'Ad unit description.';
      $adUnit->targetWindow = 'BLANK';
      $adUnit = $inventoryService->createAdUnit($adUnit);

      $placementService = $this->user->getPlacementService($this->version);
      $placement = new Placement();
      $placement->name = "Placement_" . uniqid();
      $placement->description = "Description.";
      $placement->targetedAdUnitIds = array($adUnit->id);
      $placement = $placementService->createPlacement($placement);

      $lineItemService = $this->user->getLineItemService($this->version);
      $lineItem = new LineItem();
      $lineItem->name = 'Line item #' . uniqid();
      $lineItem->orderId = $order->id;
      $lineItem->targeting = new Targeting();
      $lineItem->targeting->inventoryTargeting =
          new InventoryTargeting(NULL, NULL, array($placement->id));
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

      LineItemCreativeAssociationServiceTest::$lineItemId = $lineItem->id;
    }
  }

  /**
   * Test whether we can create a line item creative association.
   */
  public function testCreateLineItemCreativeAssociation() {
    $lica = new LineItemCreativeAssociation();
    $lica->lineItemId = LineItemCreativeAssociationServiceTest::$lineItemId;
    $lica->creativeId = LineItemCreativeAssociationServiceTest::$creative1->id;

    $testLica = $this->service->createLineItemCreativeAssociation($lica);
    LineItemCreativeAssociationServiceTest::$lica1 = $lica;

    // Assert the default status.
    $this->assertEquals('ACTIVE', $testLica->status);

    // Set the generated fields.
    $lica->status = $testLica->status;
    $lica->startDateTimeType = $testLica->startDateTimeType;

    $this->assertEquals($lica, $testLica);
  }

  /**
   * Test whether we can create a list of line item creative associations.
   */
  public function testCreateLineItemCreativeAssociations() {
    $lica1 = new LineItemCreativeAssociation();
    $lica1->lineItemId = LineItemCreativeAssociationServiceTest::$lineItemId;
    $lica1->creativeId = LineItemCreativeAssociationServiceTest::$creative2->id;

    $lica2 = new LineItemCreativeAssociation();
    $lica2->lineItemId = LineItemCreativeAssociationServiceTest::$lineItemId;
    $lica2->creativeId = LineItemCreativeAssociationServiceTest::$creative3->id;

    $licas = array($lica1, $lica2);

    $testLicas = $this->service->createLineItemCreativeAssociations($licas);

    LineItemCreativeAssociationServiceTest::$lica1 = $testLicas[0];
    LineItemCreativeAssociationServiceTest::$lica2 = $testLicas[1];

    // Assert the default status.
    $this->assertEquals('ACTIVE', $testLicas[0]->status);
    $this->assertEquals('ACTIVE', $testLicas[1]->status);

    // Set the generated fields.
    $licas[0]->status = $testLicas[0]->status;
    $licas[0]->startDateTimeType = $testLicas[0]->startDateTimeType;
    $licas[1]->status = $testLicas[1]->status;
    $licas[1]->startDateTimeType = $testLicas[1]->startDateTimeType;

    $this->assertEquals($licas[0], $testLicas[0]);
    $this->assertEquals($licas[1], $testLicas[1]);
  }

  /**
   * Test whether we can fetch an existing line item creative association.
   */
  public function testGetLineItemCreativeAssociation() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica1)) {
      $this->testCreateLineItemCreativeAssociation();
    }

    $testLica = $this->service->getLineItemCreativeAssociation(
        LineItemCreativeAssociationServiceTest::$lica1->lineItemId,
        LineItemCreativeAssociationServiceTest::$lica1->creativeId);

    // TODO(api.arogal): bug 2047999.
    LineItemCreativeAssociationServiceTest::$lica1->
        manualCreativeRotationWeight = $testLica->manualCreativeRotationWeight;

    $this->assertEquals(LineItemCreativeAssociationServiceTest::$lica1,
        $testLica);
  }

  /**
   * Test whether we can fetch a list of existing line item creative
   * associations that match given statement.
   */
  public function testGetLineItemCreativeAssociationsByStatement() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica1)) {
      $this->testCreateLineItemCreativeAssociation();
    }

    $filterStatement = new Statement('WHERE lineItemId = '
        . LineItemCreativeAssociationServiceTest::$lica1->lineItemId
        . ' AND creativeId = '
        . LineItemCreativeAssociationServiceTest::$lica1->creativeId
        . ' LIMIT 1');
    $page = $this->service->getLineItemCreativeAssociationsByStatement(
        $filterStatement);

    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(LineItemCreativeAssociationServiceTest::$lica1,
        $page->results[0]);
  }

  /**
   * Test whether we can update a line item creative association.
   */
  public function testUpdateLineItemCreativeAssociation() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica1)) {
      $this->testCreateLineItemCreativeAssociation();
    }

    $lica = clone LineItemCreativeAssociationServiceTest::$lica1;
    $lica->destinationUrl = 'http://news.google.com';

    $testLica = $this->service->updateLineItemCreativeAssociation($lica);

    $this->assertEquals($lica, $testLica);

    LineItemCreativeAssociationServiceTest::$lica1 = $lica;
  }

  /**
   * Test whether we can update a line item creative associations.
   */
  public function testUpdateLineItemCreativeAssociations() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica1)
        || !isset(LineItemCreativeAssociationServiceTest::$lica2)) {
      $this->testCreateLineItemCreativeAssociations();
    }

    $lica1 = clone LineItemCreativeAssociationServiceTest::$lica1;
    $lica1->destinationUrl = 'http://docs.google.com';

    $lica2 = clone LineItemCreativeAssociationServiceTest::$lica2;
    $lica2->destinationUrl = 'http://docs.google.com';

    $testLicas = $this->service->updateLineItemCreativeAssociations(
        array($lica1, $lica2));

    $this->assertEquals($lica1, $testLicas[0]);
    $this->assertEquals($lica2, $testLicas[1]);

    LineItemCreativeAssociationServiceTest::$lica1 = $lica1;
    LineItemCreativeAssociationServiceTest::$lica2 = $lica2;
  }

  /**
   * Test whether we can deactivate line item creative associations.
   */
  public function testPerformLineItemCreativeAssociationAction() {
    if (!isset(LineItemCreativeAssociationServiceTest::$lica1)) {
      $this->testCreateLineItemCreativeAssociations();
    }

    $action = new DeactivateLineItemCreativeAssociations();
    $filterStatement = new Statement('WHERE lineItemId = '
        . LineItemCreativeAssociationServiceTest::$lica1->lineItemId
        . ' AND creativeId = '
        . LineItemCreativeAssociationServiceTest::$lica1->creativeId
        . ' LIMIT 1');

    $result = $this->service->performLineItemCreativeAssociationAction($action,
        $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testLica = $this->service->getLineItemCreativeAssociation(
        LineItemCreativeAssociationServiceTest::$lica1->lineItemId,
        LineItemCreativeAssociationServiceTest::$lica1->creativeId);

    $this->assertEquals('INACTIVE', $testLica->status);
  }
}
