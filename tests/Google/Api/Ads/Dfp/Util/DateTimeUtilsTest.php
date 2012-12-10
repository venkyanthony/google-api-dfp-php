<?php
/**
 * Copyright 2011, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    GoogleApiAdsDfp
 * @subpackage Util
 * @category   WebServices
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <eric.koleda@google.com>
 */
error_reporting(E_STRICT | E_ALL);

require_once dirname(__FILE__) . '/../../../../../../src/Google/Api/Ads/Dfp/Util/DateTimeUtils.php';
require_once dirname(__FILE__) . '/../../../../../../src/Google/Api/Ads/Dfp/v201206/OrderService.php';

/**
 * Unit tests for {@link DateTimeUtils}.
 */
class DateTimeUtilsTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    date_default_timezone_set('America/New_York');
  }

  /**
   * Test getting a DfpDateTime from a PHP DateTime.
   * @param DateTime $dateTime the PHP DateTime
   * @param DfpDateTime $expected the expected DfpDateTime
   * @covers DateTimeUtils::ToDfpDateTime
   * @dataProvider DateTimeProvider
   */
  public function testToDfpDateTime(DateTime $dateTime,
      DfpDateTime $expected) {
    $dfpDateTime = DateTimeUtils::ToDfpDateTime($dateTime);
    $this->assertEquals($expected, $dfpDateTime);
  }

  /**
   * Test getting a PHP DateTime from a DfpDateTime.
   * @param DfpDateTime $dfpDateTime the DfpDateTime
   * @param DateTime $expected the expected PHP DateTime
   * @covers DateTimeUtils::FromDfpDateTime
   * @dataProvider DateTimeProvider
   */
  public function testFromDfpDateTime(DateTime $expected,
      DfpDateTime $dfpDateTime) {
    $dateTime = DateTimeUtils::FromDfpDateTime($dfpDateTime);
    $this->assertEquals($expected, $dateTime);
  }

  /**
   * Test getting a DFP Date from a PHP DateTime.
   * @param DateTime $dateTime the PHP DateTime
   * @param Date $expected the expected DFP Date
   * @covers DateTimeUtils::ToDfpDate
   * @dataProvider DateProvider
   */
  public function testToDfpDate(DateTime $dateTime,
      Date $expected) {
    $dfpDate = DateTimeUtils::ToDfpDate($dateTime);
    $this->assertEquals($expected, $dfpDate);
  }

  /**
   * Test getting a PHP DateTime from a DFP Date.
   * @param DateTime $expected the expected PHP DateTime
   * @param Date $dfpDate the DFP Date
   * @covers DateTimeUtils::FromDfpDate
   * @dataProvider DateProvider
   */
  public function testFromDfpDate(DateTime $expected,
      Date $dfpDate) {
    $dateTime = DateTimeUtils::FromDfpDate($dfpDate);
    $this->assertEquals($expected, $dateTime);
  }

  /**
   * Provides PHP DateTime objects along with the corresponding DfpDateTime
   * objects.
   * @return array an array of arrays of PHP DateTime objects and DfpDateTime
   *     objects
   */
  public function DateTimeProvider() {
    $data = array();

    // Complete DateTime.
    $dateTime = new DateTime('1983-06-02T08:30:15',
        new DateTimeZone('Europe/Moscow'));
    $dfpDateTime = new DfpDateTime(new Date(1983, 6, 2), 8, 30, 15,
        'Europe/Moscow');
    $data[] = array($dateTime, $dfpDateTime);

    // Date only DateTime.
    $dateTime =
        new DateTime('1983-06-02', new DateTimeZone('Europe/Moscow'));
    $dfpDateTime = new DfpDateTime(new Date(1983, 6, 2), 0, 0, 0,
        'Europe/Moscow');
    $data[] = array($dateTime, $dfpDateTime);

    // No timezone specified.
    $dateTime = new DateTime('1983-06-02T08:30:15',
        new DateTimeZone(date_default_timezone_get()));
    $dfpDateTime = new DfpDateTime(new Date(1983, 6, 2), 8, 30, 15,
        date_default_timezone_get());
    $data[] = array($dateTime, $dfpDateTime);

    return $data;
  }

  /**
   * Provides PHP DateTime objects along with the corresponding DFP Date
   * objects.
   * @return array an array of arrays of PHP DateTime objects and DFP Date
   *     objects
   */
  public function DateProvider() {
    $data = array();

    $dateTime = new DateTime('1983-06-02',
        new DateTimeZone(date_default_timezone_get()));
    $dfpDate = new Date(1983, 6, 2);
    $data[] = array($dateTime, $dfpDate);

    return $data;
  }
}
