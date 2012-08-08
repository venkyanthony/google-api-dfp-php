<?php
/**
 * Functional tests for OrderService.
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
 * Functional tests for OrderService.
 * @backupStaticAttributes disabled
 */
class OrderServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $advertiserId;
  private static $salespersonId;
  private static $traffickerId;
  private static $order;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('OrderService', $this->version);

    if (!isset(OrderServiceTest::$advertiserId)) {
      $companyService = $this->user->GetService('CompanyService', $this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);
      OrderServiceTest::$advertiserId = $company->id;
    }

    if (!isset(OrderServiceTest::$traffickerId)
        || !isset(OrderServiceTest::$salespersonId)) {
      $userService = $this->user->GetService('UserService', $this->version);
      $filterStatement = new Statement('ORDER BY name LIMIT 500');
      $page = $userService->getUsersByStatement($filterStatement);

      foreach ($page->results as $user) {
        switch ($user->roleName) {
          case 'Salesperson':
            if (!isset(OrderServiceTest::$salespersonId)) {
              OrderServiceTest::$salespersonId = $user->id;
            }
            break;
          case 'Trafficker':
            if (!isset(OrderServiceTest::$traffickerId)) {
              OrderServiceTest::$traffickerId = $user->id;
            }
            break;
        }
      }
    }
  }

  /**
   * Test whether we can create an order.
   */
  public function testCreateOrder() {
    $order = new Order();
    $order->advertiserId = OrderServiceTest::$advertiserId;
    $order->currencyCode = 'USD';
    $order->name = 'Order #' . uniqid();
    $order->traffickerId = OrderServiceTest::$traffickerId;

    $testOrder = $this->service->createOrder($order);

    $this->assertEquals($testOrder->status, 'DRAFT');

    // Set the generated fields.
    $order->id = $testOrder->id;
    $order->startDateTime = $testOrder->startDateTime;
    $order->endDateTime = $testOrder->endDateTime;
    $order->status = $testOrder->status;
    $order->creatorId = $testOrder->creatorId;
    $order->totalImpressionsDelivered = $testOrder->totalImpressionsDelivered;
    $order->totalClicksDelivered = $testOrder->totalClicksDelivered;
    $order->totalBudget = $testOrder->totalBudget;
    $order->externalOrderId = $testOrder->externalOrderId;
    $order->lastModifiedByApp = $testOrder->lastModifiedByApp;

    $this->assertEquals($testOrder, $order);

    OrderServiceTest::$order = $testOrder;
  }

  /**
   * Test whether we can fetch an existing order.
   */
  public function testGetOrder() {
    if (!isset(OrderServiceTest::$order)) {
      $this->testCreateOrder();
    }

    $testOrder = $this->service->getOrder(OrderServiceTest::$order->id);

    $this->assertEquals(OrderServiceTest::$order, $testOrder);

    OrderServiceTest::$order = $testOrder;
  }

  /**
   * Test whether we can fetch a list of existing orders that match given
   * statement.
   */
  public function testGetOrdersByStatement() {
    if (!isset(OrderServiceTest::$order)) {
      $this->testCreateOrder();
    }

    $filterStatement = new Statement('WHERE id = '
        . OrderServiceTest::$order->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getOrdersByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(OrderServiceTest::$order, $page->results[0]);
  }

  /**
   * Test whether we can update an order.
   */
  public function testUpdateOrder() {
    if (!isset(OrderServiceTest::$order)) {
      $this->testCreateOrder();
    }

    OrderServiceTest::$order->notes = 'Spoke to advertiser. All is well.';

    $testOrder = $this->service->updateOrder(OrderServiceTest::$order);

    $this->assertEquals(OrderServiceTest::$order, $testOrder);
  }

  /**
   * Test whether we can approve orders.
   */
  public function testPerformOrderAction() {
    if (!isset(OrderServiceTest::$order)) {
      $this->testCreateOrder();
    }

    $action = new ApproveOrders();
    $filterStatement = new Statement('WHERE id = '
        . OrderServiceTest::$order->id . ' LIMIT 1');

    $result = $this->service->performOrderAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testOrder = $this->service->getOrder(OrderServiceTest::$order->id);

    $this->assertEquals('APPROVED', $testOrder->status);
  }
}
