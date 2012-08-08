<?php
/**
 * Functional tests for NetworkService.
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
 * Functional tests for NetworkService.
 * @backupStaticAttributes disabled
 */
class NetworkServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201103';
  private $user;
  private $service;
  private $serviceNoNetworkCode;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getNetworkService($this->version);

    // Create an instance without the network code set.
    $this->user->SetNetworkCode(NULL);
    $this->serviceNoNetworkCode =
        $this->user->getNetworkService($this->version);
  }

  /**
   * Test whether we can get all networks.
   */
  public function testGetAllNetworks() {
    $networks = $this->serviceNoNetworkCode->getAllNetworks();

    $this->assertGreaterThanOrEqual(1, sizeof($networks));
    foreach ($networks as $network) {
      $this->assertTrue(isset($network->id));
      $this->assertTrue(isset($network->displayName));
      $this->assertTrue(isset($network->networkCode));
      $this->assertTrue(isset($network->propertyCode));
    }
  }

  /**
   * Test whether we can get the current network.
   */
  public function testGetCurrentNetwork() {
    $network = $this->service->getCurrentNetwork();

    $this->assertTrue(isset($network->id));
    $this->assertTrue(isset($network->displayName));
    $this->assertTrue(isset($network->networkCode));
    $this->assertTrue(isset($network->propertyCode));
    $this->assertTrue(isset($network->timeZone));
    $this->assertTrue(isset($network->currencyCode));
    $this->assertTrue(isset($network->effectiveRootAdUnitId));
  }
}
