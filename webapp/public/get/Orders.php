<?php
/**
 * This panel lists the orders and line items in the account. They are displayed
 * as nested HTML lsits.
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
 * @subpackage webapp
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once '../../lib/DisplayUtils.php';
require_once '../../lib/ServiceUserManager.php';
require_once '../../lib/WebUtils.php';
require_once 'Google/Api/Ads/Dfp/Util/ServiceUtils.php';

/**
 * Maps order IDs to child line items.
 * @param array $lineItems an array of line items
 * @return array a map from order ID to an array of child line items
 */
function MapOrdersToLineItems(array $lineItems) {
  $lineItemsMap = array();
  foreach ($lineItems as $lineItem) {
    if (isset($lineItem->orderId)) {
      $lineItemsMap[$lineItem->orderId][] = $lineItem;
    }
  }
  return $lineItemsMap;
}

/**
 * Prints an array of orders and map of line items as nested HTML lists.
 * @param array $orders an array of orders
 * @param array $lineItemsMap a map from order ID to an array of line items
 */
function PrintOrderAndLineItemList(array $orders, array $lineItemsMap) {
  print '<ul>';
  foreach ($orders as $order) {
    print '<li>' . $order->name . ' (' . $order->id . ')';
    DisplayUtils::PrintDetailsLink('[details]', 'order-' . $order->id);
    DisplayUtils::PrintDetails($order, 'order-' . $order->id);
    if (isset($lineItemsMap[$order->id])) {
      print '<ul>';
      foreach ($lineItemsMap[$order->id] as $lineItem) {
        print '<li>' . $lineItem->name . ' (' . $lineItem->id . ')';
        DisplayUtils::PrintDetailsLink('[details]', 'lineitem-' . $lineItem->id);
        DisplayUtils::PrintDetails($lineItem, 'lineitem-' . $lineItem->id);
        print '</li>';
      }
      print '</ul>';
    }
    print '</li>';
  }
  print '</ul>';
}

/**
 * Prints an array of orders and map of line items as nested HTML lists.
 * @param array $orders an array of orders
 * @param array $lineItemsMap a map from order ID to an array of line items
 */
function PrintOrderOrLineItemList(array $lineItemsOrOrders) {
  print '<ul>';
  foreach ($lineItemsOrOrders as $lineItemOrOrder) {
    print '<li>' . $lineItemOrOrder->name . ' (' . $lineItemOrOrder->id . ')';
    DisplayUtils::PrintDetailsLink('[details]', $lineItemOrOrder->id);
    DisplayUtils::PrintDetails($lineItemOrOrder, $lineItemOrOrder->id);
    print '</li>';
  }
  print '</ul>';
}

try {
  // Load the service user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  // Create services.
  $orderService = $user->GetService('OrderService');
  $lineItemService = $user->GetService('LineItemService');
  $orders = array();
  $lineItems = array();

  // Get filter text.
  $filterText = WebUtils::GetParamOrEmptyString($_POST, 'filterText');

  // Get display style.
  $displayStyle = WebUtils::GetParamOrEmptyString($_POST, 'displayStyle');

  // Get type override.
  $typeOverride = WebUtils::GetParamOrEmptyString($_POST, 'typeOverride');

  if ($filterText != '') {
    $filterText = str_replace('\\', '', $filterText);
    //$filterText = 'WHERE ' . $filterText;
  }

  if ($typeOverride == 'order') {
    // Get all orders.
    $orders = $filterText == ''
        ? ServiceUtils::GetAllObjects($orderService, 'getOrdersByStatement',
            $filterText)
        : ServiceUtils::GetSomeObjects($orderService, 'getOrdersByStatement',
            $filterText);
  } else if ($typeOverride == 'lineitem') {
    // Get all line items.
    $lineItems = $filterText == ''
        ? ServiceUtils::GetAllObjects($lineItemService,
            'getLineItemsByStatement', $filterText)
        : ServiceUtils::GetSomeObjects($lineItemService,
            'getLineItemsByStatement', $filterText);
  } else {
    // Get all orders.
    $orders = $filterText == ''
        ? ServiceUtils::GetAllObjects($orderService,
            'getOrdersByStatement', $filterText)
        : ServiceUtils::GetSomeObjects($orderService,
            'getOrdersByStatement', $filterText);
    // Get all line items.
    $lineItems = $filterText == ''
        ? ServiceUtils::GetAllObjects($lineItemService,
            'getLineItemsByStatement', $filterText)
        : ServiceUtils::GetSomeObjects($lineItemService,
            'getLineItemsByStatement', $filterText);
  }

  if ($displayStyle == 'default') {
    if (sizeof($orders) > 0) {
      // Map order IDs to child line items.
      $lineItemsMap = MapOrdersToLineItems($lineItems);
      // Output HTML.
      PrintOrderAndLineItemList($orders, $lineItemsMap);
    } else {
      // Display no results message.
      DisplayUtils::PrintInfoMessage('No results found.');
    }
  } else if ($displayStyle == 'list') {
    if (sizeof($orders) == 0 && sizeof($lineItems) == 0) {
      // Display no results message.
      DisplayUtils::PrintInfoMessage('No results found.');
    } else {
      if ($typeOverride == 'order') {
        print '<b>Orders</b>';
        // Output HTML.
        DisplayUtils::PrintNameAndIdList($orders, 'order');
      } else if ($typeOverride == 'lineitem') {
        print '<b>LineItems</b>';
        // Output HTML.
        DisplayUtils::PrintNameAndIdList($lineItems, 'lineItem');
      } else {
        // Output HTML.
        DisplayUtils::PrintNameAndIdList(array_merge($lineItems, $orders), 'orders & items');
      }
    }
  } else {
    // Display error message.
    DisplayUtils::PrintErrorMessage('Invalid display style');
  }
} catch (Exception $e) {
  // Display error message.
  DisplayUtils::PrintErrorMessage($e->getMessage());
}
