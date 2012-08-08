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
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for OrderService.
 * @backupStaticAttributes disabled
 */
class OrderServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201103';
  private $user;
  private $service;

  private static $advertiserId;
  private static $salespersonId;
  private static $traffickerId;
  private static $order1;
  private static $order2;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getOrderService($this->version);

    if (!isset(OrderServiceTest::$advertiserId)) {
      $companyService = $this->user->getCompanyService($this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);
      OrderServiceTest::$advertiserId = $company->id;
    }

    if (!isset(OrderServiceTest::$traffickerId)
        || !isset(OrderServiceTest::$salespersonId)) {
      $userService = $this->user->getUserService($this->version);
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

    $this->assertEquals($testOrder, $order);

    OrderServiceTest::$order1 = $testOrder;
  }

  /**
   * Test whether we can create orders.
   */
  public function testCreateOrders() {
    $order1 = new Order();
    $order1->advertiserId = OrderServiceTest::$advertiserId;
    $order1->currencyCode = 'USD';
    $order1->name = 'Order #' . uniqid();
    $order1->traffickerId = OrderServiceTest::$traffickerId;

    $order2 = new Order();
    $order2->advertiserId = OrderServiceTest::$advertiserId;
    $order2->currencyCode = 'USD';
    $order2->name = 'Order #' . uniqid();
    $order2->traffickerId = OrderServiceTest::$traffickerId;

    $testOrders = $this->service->createOrders(array($order1, $order2));
    $testOrder1 = $testOrders[0];
    $testOrder2 = $testOrders[1];

    $this->assertEquals($testOrder1->status, 'DRAFT');
    $this->assertEquals($testOrder2->status, 'DRAFT');

    // Set the generated fields.
    $order1->id = $testOrder1->id;
    $order1->startDateTime = $testOrder1->startDateTime;
    $order1->endDateTime = $testOrder1->endDateTime;
    $order1->status = $testOrder1->status;
    $order1->creatorId = $testOrder1->creatorId;
    $order1->totalImpressionsDelivered = $testOrder1->totalImpressionsDelivered;
    $order1->totalClicksDelivered = $testOrder1->totalClicksDelivered;
    $order1->totalBudget = $testOrder1->totalBudget;

    $this->assertEquals($testOrder1, $order1);

    // Set the generated fields.
    $order2->id = $testOrder2->id;
    $order2->startDateTime = $testOrder2->startDateTime;
    $order2->endDateTime = $testOrder2->endDateTime;
    $order2->status = $testOrder2->status;
    $order2->creatorId = $testOrder2->creatorId;
    $order2->totalImpressionsDelivered = $testOrder2->totalImpressionsDelivered;
    $order2->totalClicksDelivered = $testOrder2->totalClicksDelivered;
    $order2->totalBudget = $testOrder2->totalBudget;

    $this->assertEquals($testOrder2, $order2);

    OrderServiceTest::$order1 = $testOrder1;
    OrderServiceTest::$order2 = $testOrder2;
  }

  /**
   * Test whether we can fetch an existing order.
   */
  public function testGetOrder() {
    if (!isset(OrderServiceTest::$order1)) {
      $this->testCreateOrder();
    }

    $testOrder = $this->service->getOrder(OrderServiceTest::$order1->id);

    $this->assertEquals(OrderServiceTest::$order1, $testOrder);

    OrderServiceTest::$order1 = $testOrder;
  }

  /**
   * Test whether we can fetch a list of existing orders that match given
   * statement.
   */
  public function testGetOrdersByStatement() {
    if (!isset(OrderServiceTest::$order1)) {
      $this->testCreateOrder();
    }

    $filterStatement = new Statement('WHERE id = '
        . OrderServiceTest::$order1->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getOrdersByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(OrderServiceTest::$order1, $page->results[0]);
  }

  /**
   * Test whether we can update an order.
   */
  public function testUpdateOrder() {
    if (!isset(OrderServiceTest::$order1)) {
      $this->testCreateOrder();
    }

    OrderServiceTest::$order1->notes = 'Spoke to advertiser. All is well.';

    $testOrder = $this->service->updateOrder(OrderServiceTest::$order1);

    $this->assertEquals(OrderServiceTest::$order1, $testOrder);
  }

  /**
   * Test whether we can update a list of orders.
   */
  public function testUpdateOrders() {
    if (!isset(OrderServiceTest::$order1)
        || !isset(OrderServiceTest::$order2)) {
      $this->testCreateOrders();
    }

    OrderServiceTest::$order1->notes = 'Spoke to advertiser. All is not well.';
    OrderServiceTest::$order2->notes = 'Spoke to advertiser. All is not well.';

    $testOrders = $this->service->updateOrders(
        array(OrderServiceTest::$order1, OrderServiceTest::$order2));
    $testOrder1 = $testOrders[0];
    $testOrder2 = $testOrders[1];

    $this->assertEquals(OrderServiceTest::$order1, $testOrder1);
    $this->assertEquals(OrderServiceTest::$order2, $testOrder2);
  }

  /**
   * Test whether we can approve orders.
   */
  public function testPerformOrderAction() {
    if (!isset(OrderServiceTest::$order1)) {
      $this->testCreateOrder();
    }

    $action = new ApproveOrders();
    $filterStatement = new Statement('WHERE id = '
        . OrderServiceTest::$order1->id . ' LIMIT 1');

    $result = $this->service->performOrderAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testOrder = $this->service->getOrder(OrderServiceTest::$order1->id);

    $this->assertEquals('APPROVED', $testOrder->status);
  }
}
