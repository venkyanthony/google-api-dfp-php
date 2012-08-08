<?php
/**
 * Functional tests for LabelService.
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
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

$path = dirname(__FILE__) . '/../../../../../../src';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once 'PHPUnit/Framework.php';

/**
 * Functional tests for LabelService.
 * @backupStaticAttributes disabled
 */
class LabelServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $label;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('LabelService', $this->version);
  }

  /**
   * Test whether we can create a label.
   */
  public function testCreateLabel() {
    $label = new Label();
    $label->name = 'Name ' . uniqid();
    $label->description = 'Description';
    $label->type = 'COMPETITIVE_EXCLUSION';

    $testLabel = $this->service->createLabel($label);

    // Set the generated fields.
    $label->id = $testLabel->id;
    $label->isActive = $testLabel->isActive;

    $this->assertEquals($label, $testLabel);

    LabelServiceTest::$label = $label;
  }

  /**
   * Test whether we can fetch an existing label.
   */
  public function testGetLabel() {
    if (!isset(LabelServiceTest::$label)) {
      $this->testCreateLabel();
    }

    $testLabel =
        $this->service->getLabel(LabelServiceTest::$label->id);

    $this->assertEquals(LabelServiceTest::$label, $testLabel);
  }

  /**
   * Test whether we can fetch a list of existing labels that match given
   * statement.
   */
  public function testGetLabelsByStatement() {
    if (!isset(LabelServiceTest::$label)) {
      $this->testCreateLabel();
    }

    $filterStatementStatement = new Statement('WHERE id = '
        . LabelServiceTest::$label->id . ' ORDER BY name LIMIT 1');
    $page = $this->service->getLabelsByStatement($filterStatementStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(LabelServiceTest::$label, $page->results[0]);
  }

  /**
   * Test whether we can update a label.
   */
  public function testUpdateLabel() {
    if (!isset(LabelServiceTest::$label)) {
      $this->testCreateLabel();
    }

    $label = clone LabelServiceTest::$label;
    $label->description = 'Updated ' . date('Ymd');

    $testLabel = $this->service->updateLabel($label);

    $this->assertEquals($label, $testLabel);

    LabelServiceTest::$label = $label;
  }

  /**
   * Test whether we can deactivate labels.
   */
  public function testPerformLabelAction() {
    if (!isset(LabelServiceTest::$label)) {
      $this->testCreateLabel();
    }

    $action = new DeactivateLabels();
    $filterStatement = new Statement('WHERE id = '
        . LabelServiceTest::$label->id . ' LIMIT 1');

    $result = $this->service->performLabelAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testLabel = $this->service->getLabel(LabelServiceTest::$label->id);

    $this->assertFalse($testLabel->isActive);
  }
}
