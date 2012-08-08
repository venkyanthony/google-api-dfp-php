<?php
/**
 * Functional tests for UserService.
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
 * Functional tests for UserService.
 * @backupStaticAttributes disabled
 */
class UserServiceTest extends PHPUnit_Framework_TestCase {
  private $version = 'v201108';
  private $user;
  private $service;

  private static $userEntity;

  protected function setUp() {
    $authFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_auth.ini';
    $settingsFile =
        dirname(__FILE__) . '/../../../../../../test_data/test_settings.ini';
    $this->user = new DfpUser($authFile, NULL, NULL, NULL, NULL,
        $settingsFile);
    $this->user->LogDefaults();
    $this->service = $this->user->GetService('UserService', $this->version);
  }

  /**
   * Test whether we can fetch an existing user.
   */
  public function testGetUser() {
    if (!isset(UserServiceTest::$userEntity)) {
      $this->testGetUsersByStatement();
    }

    $testUser = $this->service->getUser(UserServiceTest::$userEntity->id);
    $this->assertEquals(UserServiceTest::$userEntity->id, $testUser->id);
  }

  /**
   * Test whether we can fetch a list of existing users that match given
   * statement.
   */
  public function testGetUsersByStatement() {
    $filterStatement = new Statement('ORDER BY name LIMIT 500');
    $page = $this->service->getUsersByStatement($filterStatement);
    $this->assertTrue(isset($page->results));
    $this->assertGreaterThan(0, sizeof($page->results));

    UserServiceTest::$userEntity = $page->results[0];
  }

  /**
   * Test whether we can update a user.
   */
  public function testUpdateUser() {
    if (!isset(UserServiceTest::$userEntity)) {
      $this->testGetUsersByStatement();
    }

    UserServiceTest::$userEntity->preferredLocale = 'fr_FR';
    $testUser = $this->service->updateUser(UserServiceTest::$userEntity);
    $this->assertEquals(UserServiceTest::$userEntity, $testUser);
  }

  /**
   * Test whether we can deactivate users.
   */
  public function testPerformUserAction() {
    if (!isset(UserServiceTest::$userEntity)) {
      $this->testGetUsersByStatement();
    }

    if (UserServiceTest::$userEntity->isActive) {
      $action = new DeactivateUsers();
      $expectedIsActive = false;
    } else {
      $action = new ActivateUsers();
      $expectedIsActive = true;
    }

    $filterStatement = new Statement("WHERE id = " . UserServiceTest::$userEntity->id
        . " LIMIT 1");

    $result = $this->service->performUserAction($action, $filterStatement);

    $this->assertEquals(1, $result->numChanges);

    $testUser = $this->service->updateUser(UserServiceTest::$userEntity);

    $this->assertEquals($expectedIsActive, $testUser->isActive);
  }

  /**
   * Test whether we can fetch the current user.
   */
  public function testGetCurrentUser() {
    $currentUser = $this->service->getCurrentUser();
    $this->assertNotNull($currentUser);
    $this->assertNotNull($currentUser->id);
    $this->assertNotNull($currentUser->email);
    $this->assertNotNull($currentUser->roleName);
  }
}
