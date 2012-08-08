<?php
/**
 * Functional tests for CreativeService.
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
require_once 'Google/Api/Ads/Common/Util/MediaUtils.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for CreativeService.
 * @backupStaticAttributes disabled
 */
class CreativeServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $advertiserId;
  private static $creative;
  private static $medRectImage;
  private static $skyscraperImage;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('CreativeService', $this->version);

    if (!isset(CreativeServiceTest::$medRectImage)
        || !isset(CreativeServiceTest::$imageData2)
        || !isset(CreativeServiceTest::$skyscraperImage)) {
      CreativeServiceTest::$medRectImage =
          MediaUtils::getBase64Data(dirname(__FILE__)
              . '/../../../../../../test_data/medium_rectangle.jpg');
      CreativeServiceTest::$skyscraperImage =
          MediaUtils::getBase64Data(
              dirname(__FILE__)
                  . '/../../../../../../test_data/skyscraper.jpg');
    }

    if (!isset(CreativeServiceTest::$advertiserId)) {
      $companyService = $this->user->GetService('CompanyService', $this->version);
      $company = new Company(NULL, 'Company #' . uniqid(), 'ADVERTISER');
      $company = $companyService->createCompany($company);
      CreativeServiceTest::$advertiserId = $company->id;
    }
  }

  /**
   * Test whether we can create a creative.
   */
  public function testCreateCreative() {
    $creative = new ImageCreative();
    $creative->name = 'Image Creative #' . uniqid();
    $creative->advertiserId = CreativeServiceTest::$advertiserId;
    $creative->destinationUrl = 'http://google.com';
    $creative->imageName = 'image.jpg';
    $creative->imageByteArray = CreativeServiceTest::$medRectImage;
    $creative->size = new Size(300, 250);

    $testCreative = $this->service->createCreative($creative);

    // Assert preview URL was generated.
    $this->assertNotNull($testCreative->previewUrl);

    // Set the generated fields.
    $creative->previewUrl = $testCreative->previewUrl;

    // Set the generated fields.
    $creative->id = $testCreative->id;
    $creative->assetSize = $testCreative->assetSize;
    $creative->CreativeType = $testCreative->CreativeType;
    $creative->imageByteArray = $testCreative->imageByteArray;
    $creative->imageUrl = $testCreative->imageUrl;

    $this->assertEquals($creative, $testCreative);

    CreativeServiceTest::$creative = $creative;
  }

  /**
   * Test whether we can fetch an existing creative.
   */
  public function testGetCreative() {
    if (!isset(CreativeServiceTest::$creative)) {
      $this->testCreateCreative();
    }

    $testCreative =
        $this->service->getCreative(CreativeServiceTest::$creative->id);

    // The URLs may change.
    CreativeServiceTest::$creative->previewUrl = $testCreative->previewUrl;
    CreativeServiceTest::$creative->imageUrl = $testCreative->imageUrl;

    $this->assertEquals(CreativeServiceTest::$creative, $testCreative);
  }

  /**
   * Test whether we can fetch a list of existing creatives that match given
   * statement.
   */
  public function testGetCreativesByStatement() {
    if (!isset(CreativeServiceTest::$creative)) {
      $this->testCreateCreative();
    }

    $filterStatement = new Statement('WHERE id = '
        . CreativeServiceTest::$creative->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getCreativesByStatement($filterStatement);
    $testCreative = $page->results[0];

    // The URLs may change.
    CreativeServiceTest::$creative->previewUrl = $testCreative->previewUrl;
    CreativeServiceTest::$creative->imageUrl = $testCreative->imageUrl;

    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));

    $this->assertEquals(CreativeServiceTest::$creative, $testCreative);
  }

  /**
   * Test whether we can update a creative.
   */
  public function testUpdateCreative() {
    if (!isset(CreativeServiceTest::$creative)) {
      $this->testCreateCreative();
    }

    $creative = clone CreativeServiceTest::$creative;
    $creative->destinationUrl = 'http://news.google.com';

    $testCreative =
        $this->service->updateCreative($creative);

    // The URLs may change.
    $creative->previewUrl = $testCreative->previewUrl;
    $creative->imageUrl = $testCreative->imageUrl;

    $this->assertEquals($creative, $testCreative);

    CreativeServiceTest::$creative = $creative;
  }
}
