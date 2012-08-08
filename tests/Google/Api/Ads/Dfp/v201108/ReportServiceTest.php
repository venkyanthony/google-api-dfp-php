<?php
/**
 * Functional tests for ReportService.
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
 * Functional tests for ReportService.
 * @backupStaticAttributes disabled
 */
class ReportServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $reportJob;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('ReportService', $this->version);
  }

  /**
   * Test whether we can run a report job.
   */
  public function testRunReportJob() {
    // Create report job.
    $reportJob = new ReportJob();

    // Create report query.
    $reportQuery = new ReportQuery();
    $reportQuery->dateRangeType = 'LAST_MONTH';
    $reportQuery->dimensions = array('ORDER');
    $reportQuery->columns = array('AD_SERVER_IMPRESSIONS', 'AD_SERVER_CLICKS',
        'AD_SERVER_CTR', 'AD_SERVER_REVENUE', 'AD_SERVER_AVERAGE_ECPM');
    $reportJob->reportQuery = $reportQuery;

    // Run report job.
    $reportJob = $this->service->runReportJob($reportJob);

    $this->assertGreaterThan(0, $reportJob->id);
    $this->assertTrue($reportJob->reportJobStatus == 'IN_PROGRESS' ||
        $reportJob->reportJobStatus == 'COMPLETED');

    ReportServiceTest::$reportJob = $reportJob;
  }

  /**
   * Test whether we can get a report job.
   */
  public function testGetReportJob() {
    if (!isset(ReportServiceTest::$reportJob)) {
      $this->testRunReportJob();
    }

    $reportJob =
        $this->service->getReportJob(ReportServiceTest::$reportJob->id);

    $this->assertEquals(ReportServiceTest::$reportJob->id, $reportJob->id);
  }

  /**
   * Test whether we can get a report download URL.
   */
  public function testGetReportDownloadURL() {
    if (!isset(ReportServiceTest::$reportJob)) {
      $this->testRunReportJob();
    }

    $reportJob = ReportServiceTest::$reportJob;
    while ($reportJob->reportJobStatus == 'IN_PROGRESS') {
      sleep(5);
      $reportJob = $this->service->getReportJob($reportJob->id);
    }
    $this->assertEquals('COMPLETED', $reportJob->reportJobStatus);

    $reportDownloadURL =
        $this->service->getReportDownloadURL($reportJob->id, 'CSV');
    $this->assertTrue(isset($reportDownloadURL));
  }
}
