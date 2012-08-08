<?php
/**
 * Functional tests for CompanyService.
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
 * Functional tests for CompanyService.
 * @backupStaticAttributes disabled
 */
class CompanyServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $company;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('CompanyService', $this->version);
  }

  /**
   * Test whether we can create a company.
   */
  public function testCreateCompany() {
    $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');

    $testCompany = $this->service->createCompany($company);

    // Set the generated fields.
    $company->id = $testCompany->id;

    $this->assertEquals($company, $testCompany);

    CompanyServiceTest::$company = $company;
  }

  /**
   * Test whether we can fetch an existing company.
   */
  public function testGetCompany() {
    if (!isset(CompanyServiceTest::$company)) {
      $this->testCreateCompany();
    }

    $testCompany =
        $this->service->getCompany(CompanyServiceTest::$company->id);

    $this->assertEquals(CompanyServiceTest::$company, $testCompany);
  }

  /**
   * Test whether we can fetch a list of existing companies that match given
   * statement.
   */
  public function testGetCompaniesByStatement() {
    if (!isset(CompanyServiceTest::$company)) {
      $this->testCreateCompany();
    }

    $filterStatementStatement = new Statement('WHERE id = '
        . CompanyServiceTest::$company->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getCompaniesByStatement($filterStatementStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(CompanyServiceTest::$company, $page->results[0]);
  }

  /**
   * Test whether we can update a company.
   */
  public function testUpdateCompany() {
    if (!isset(CompanyServiceTest::$company)) {
      $this->testCreateCompany();
    }

    $company = clone CompanyServiceTest::$company;
    $company->name .= ' Corp.';

    $testCompany = $this->service->updateCompany($company);

    $this->assertEquals($company, $testCompany);

    CompanyServiceTest::$company = $company;
  }
}
