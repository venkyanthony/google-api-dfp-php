<?php
/**
 * Functional tests for CustomTargetingService.
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
 * Functional tests for CustomTargetingService.
 * @backupStaticAttributes disabled
 */
class CustomTargetingServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201103';
  private $user;
  private $service;

  private static $valueKeyId;
  private static $key;
  private static $value;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->getCustomTargetingService($this->version);

    if (!isset(CustomTargetingServiceTest::$valueKeyId)) {
      $key = new CustomTargetingKey();
      $key->name = 'valuekey-' . uniqid();
      $key->displayName = 'Value Key';
      $key->type = 'PREDEFINED';
      $keys = $this->service->createCustomTargetingKeys(array($key));
      CustomTargetingServiceTest::$valueKeyId = $keys[0]->id;
    }
  }

  /**
   * Test whether we can create a custom targeting keys.
   */
  public function testCreateCustomTargetingKeys() {
    $key = new CustomTargetingKey();
    $key->name = 'key-' . uniqid();
    $key->displayName = 'Key';
    $key->type = 'PREDEFINED';

    $keys = $this->service->createCustomTargetingKeys(array($key));

    // Set the generated fields.
    $key->id = $keys[0]->id;

    $this->assertEquals($key, $keys[0]);

    CustomTargetingServiceTest::$key = $key;
  }

  /**
   * Test whether we can fetch a list of existing custom targeting keys that
   * match given statement.
   */
  public function testGetCustomTargetingKeysByStatement() {
    if (!isset(CustomTargetingServiceTest::$key)) {
      $this->testCreateCustomTargetingKeys();
    }

    $filterStatementStatement = new Statement('WHERE id = '
        . CustomTargetingServiceTest::$key->id
        . ' ORDER BY name LIMIT 1');
    $page = $this->service->getCustomTargetingKeysByStatement(
        $filterStatementStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(CustomTargetingServiceTest::$key,
        $page->results[0]);
  }

  /**
   * Test whether we can update a custom targeting key.
   */
  public function testUpdateCustomTargetingKeys() {
    if (!isset(CustomTargetingServiceTest::$key)) {
      $this->testCreateCustomTargetingKeys();
    }

    $key = clone CustomTargetingServiceTest::$key;
    $key->displayName .= ' Extra';

    $keys = $this->service->updateCustomTargetingKeys(array($key));

    $this->assertEquals($key, $keys[0]);

    CustomTargetingServiceTest::$key = $keys[0];
  }

  /**
   * Test whether we can perform an action on custom targeting keys.
   */
  public function testPerformCustomTargetingKeyAction() {
    if (!isset(CustomTargetingServiceTest::$key)) {
      $this->testCreateCustomTargetingKeys();
    }

    $action = new DeleteCustomTargetingKeys();
    $filterStatement = new Statement('WHERE id = '
        . CustomTargetingServiceTest::$key->id . ' LIMIT 1');

    $result = $this->service->performCustomTargetingKeyAction($action,
        $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    CustomTargetingServiceTest::$key = NULL;
  }

  /**
   * Test whether we can create a custom targeting values.
   */
  public function testCreateCustomTargetingValues() {
    $value = new CustomTargetingValue();
    $value->customTargetingKeyId = CustomTargetingServiceTest::$valueKeyId;
    $value->name = 'value-' . uniqid();
    $value->displayName = 'Value';
    $value->matchType = 'EXACT';

    $values = $this->service->createCustomTargetingValues(array($value));

    // Set the generated fields.
    $value->id = $values[0]->id;

    $this->assertEquals($value, $values[0]);

    CustomTargetingServiceTest::$value = $value;
  }

  /**
   * Test whether we can fetch a list of existing custom targeting values that
   * match given statement.
   */
  public function testGetCustomTargetingValuesByStatement() {
    if (!isset(CustomTargetingServiceTest::$value)) {
      $this->testCreateCustomTargetingValues();
    }

    $filterStatementStatement = new Statement('WHERE customTargetingKeyId = '
        . CustomTargetingServiceTest::$value->customTargetingKeyId
        . ' AND id = '
        . CustomTargetingServiceTest::$value->id
        . ' ORDER BY name LIMIT 1');
    $page = $this->service->getCustomTargetingValuesByStatement(
        $filterStatementStatement);
    $this->assertTrue(isset($page->results));
    $this->assertEquals(1, sizeof($page->results));
    $this->assertEquals(CustomTargetingServiceTest::$value,
        $page->results[0]);
  }

  /**
   * Test whether we can update a custom targeting value.
   */
  public function testUpdateCustomTargetingValues() {
    if (!isset(CustomTargetingServiceTest::$value)) {
      $this->testCreateCustomTargetingValues();
    }

    $value = clone CustomTargetingServiceTest::$value;
    $value->displayName .= ' Extra';

    $values = $this->service->updateCustomTargetingValues(array($value));

    $this->assertEquals($value, $values[0]);

    CustomTargetingServiceTest::$value = $values[0];
  }

  /**
   * Test whether we can perform an action on custom targeting values.
   */
  public function testPerformCustomTargetingValueAction() {
    if (!isset(CustomTargetingServiceTest::$value)) {
      $this->testCreateCustomTargetingValues();
    }

    $action = new DeleteCustomTargetingValues();
    $filterStatement = new Statement('WHERE customTargetingKeyId = '
        . CustomTargetingServiceTest::$value->customTargetingKeyId
        . ' AND id = '
        . CustomTargetingServiceTest::$value->id
        . ' LIMIT 1');

    $result = $this->service->performCustomTargetingValueAction($action,
        $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    CustomTargetingServiceTest::$value = NULL;
  }
}
