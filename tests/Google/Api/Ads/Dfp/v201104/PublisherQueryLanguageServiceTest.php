<?php
/**
 * Functional tests for PublisherQueryLanguageService.
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
 * @subpackage v201104
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
 * Functional tests for PublisherQueryLanguageService.
 * @backupStaticAttributes disabled
 */
class PublisherQueryLanguageServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201104';
  private $user;
  private $service;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getPublisherQueryLanguageService($this->version);
  }

  /**
   * Test whether we can select countries.
   */
  public function testSelectCountries() {
    $selectStatement =
        new Statement('SELECT * FROM Country WHERE targetable = true LIMIT 10');
    $resultSet = $this->service->select($selectStatement);

    $this->assertTrue(isset($resultSet));
    $this->assertGreaterThanOrEqual(1, sizeof($resultSet->columnTypes));
    $this->assertEquals(10, sizeof($resultSet->rows));
    $this->assertEquals(sizeof($resultSet->columnTypes),
        sizeof($resultSet->rows[0]->values));
  }

  /**
   * Test whether we can select regions.
   */
  public function testSelectRegions() {
    $selectStatement =
        new Statement('SELECT * FROM Region WHERE targetable = true LIMIT 10');
    $resultSet = $this->service->select($selectStatement);

    $this->assertTrue(isset($resultSet));
    $this->assertGreaterThanOrEqual(1, sizeof($resultSet->columnTypes));
    $this->assertEquals(10, sizeof($resultSet->rows));
    $this->assertEquals(sizeof($resultSet->columnTypes),
        sizeof($resultSet->rows[0]->values));
  }

  /**
   * Test whether we can select metros.
   */
  public function testSelectMetros() {
    $selectStatement =
        new Statement('SELECT * FROM Metro WHERE targetable = true LIMIT 10');
    $resultSet = $this->service->select($selectStatement);

    $this->assertTrue(isset($resultSet));
    $this->assertGreaterThanOrEqual(1, sizeof($resultSet->columnTypes));
    $this->assertEquals(10, sizeof($resultSet->rows));
    $this->assertEquals(sizeof($resultSet->columnTypes),
        sizeof($resultSet->rows[0]->values));
  }

  /**
   * Test whether we can select cities.
   */
  public function testSelectCities() {
    $selectStatement =
        new Statement('SELECT * FROM City WHERE targetable = true LIMIT 10');
    $resultSet = $this->service->select($selectStatement);

    $this->assertTrue(isset($resultSet));
    $this->assertGreaterThanOrEqual(1, sizeof($resultSet->columnTypes));
    $this->assertEquals(10, sizeof($resultSet->rows));
    $this->assertEquals(sizeof($resultSet->columnTypes),
        sizeof($resultSet->rows[0]->values));
  }
}
