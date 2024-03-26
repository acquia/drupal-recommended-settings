<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Common;

use Acquia\Drupal\RecommendedSettings\Common\ArrayManipulator;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the ArrayManipulator class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\ArrayManipulator
 */
class ArrayManipulatorTest extends TestCase {

  /**
   * Tests arrayMergeRecursiveDistinct() method.
   *
   * @param array<string> $actual
   *   An array to create input actual data.
   * @param array<string> $expected
   *   An array of expected data.
   *
   * @dataProvider arrayMergeDataProvider
   */
  public function testArrayMergeRecursiveDistinct(array $actual, array $expected): void {
    $actual = ArrayManipulator::arrayMergeRecursiveDistinct($actual[0], $actual[1]);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Tests expandFromDotNotatedKeys() method.
   *
   * @param array<string> $actual
   *   An array to create input actual data.
   * @param array<string> $expected
   *   An array of expected data.
   *
   * @dataProvider expandFromDotNotatedKeysDataProvider
   */
  public function testExpandFromDotNotatedKeys(array $actual, array $expected): void {
    $actual = ArrayManipulator::expandFromDotNotatedKeys($actual);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Tests flattenToDotNotatedKeys() method.
   *
   * @param array<string> $expected
   *   An array of expected data.
   * @param array<string> $actual
   *   An array to create input actual data.
   *
   * @dataProvider expandFromDotNotatedKeysDataProvider
   */
  public function testFlattenToDotNotatedKeys(array $expected, array $actual): void {
    $actual = ArrayManipulator::flattenToDotNotatedKeys($actual);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Tests flattenMultidimensionalArray() method.
   *
   * @param array<string> $expected
   *   An array of expected data.
   * @param string $glue
   *   A string to join array data.
   * @param array<string> $actual
   *   An array to create input actual data.
   *
   * @dataProvider flattenMultidimensionalArrayDataProvider
   */
  public function testFlattenMultidimensionalArray(array $expected, string $glue, array $actual): void {
    $actual = ArrayManipulator::flattenMultidimensionalArray($actual, $glue);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Tests convertArrayToFlatTextArray() method.
   */
  public function testconvertArrayToFlatTextArray(): void {
    $actual = [
      'first' => [
        'second' => [
          'third' => 'fourth',
        ],
        'fifth' => [
          'black', 'white',
        ],
        'sixth' => TRUE,
        'seventh' => FALSE,
      ],
      'eight' => TRUE,
      'ninth' => FALSE,
      'tenth' => 'We are testing that there should be a new line after 60 characters.'
    ];
    $expected = [
      0 => [
        0 => 'first.second.third',
        1 => 'fourth',
      ],
      1 => [
        0 => 'first.fifth.0',
        1 => 'black',
      ],
      2 => [
        0 => 'first.fifth.1',
        1 => 'white',
      ],
      3 => [
        0 => 'first.sixth',
        1 => 'true',
      ],
      4 => [
        0 => 'first.seventh',
        1 => 'false',
      ],
      5 => [
        0 => 'eight',
        1 => 'true',
      ],
      6 => [
        0 => 'ninth',
        1 => 'false',
      ],
      7 => [
        0 => 'tenth',
        1 => "We are testing that there should be a new line after 60\ncharacters.",
      ],
    ];
    $actual = ArrayManipulator::convertArrayToFlatTextArray($actual);
    $this->assertEquals($actual, $expected);
  }

  /**
   * The dataProvider for method: arrayMergeRecursiveDistinct().
   *
   * @return array<string>
   *   Returns data provider.
   */
  public static function arrayMergeDataProvider(): array {
    return [
      [
        [['key' => ['one' => "two"]], ['key' => ['one']]],
        ['key' => ['one' => 'two', 'one']],
      ],
      [
        [
          ["key1" => "val1", "key2" => "val2", "key3" => ["val3" => "another"]],
          ["key1" => ["value1", "value2"], "key3" => "value3", "key4" => ["value3", "value4"]],
        ],
        ["key1" => ["value1", "value2"], "key2" => "val2", "key3" => "value3", "key4" => ["value3", "value4"]],
      ],
    ];
  }

  /**
   * The dataProvider for method: expandFromDotNotatedKeys().
   *
   * @return array<string>
   *   Returns data provider.
   */
  public static function expandFromDotNotatedKeysDataProvider(): array {
    return [
      [
        ['drush.alias' => "self"],
        ['drush' => ['alias' => 'self']],
      ],
      [
        ['drupal.db.database' => 'drupal', 'drupal.db.username' => 'root', 'drupal.db.password' => 'root'],
        ['drupal' => ['db' => ['database' => 'drupal', 'username' => 'root', 'password' => 'root']]],
      ],
    ];
  }

  /**
   * The dataProvider for method: flattenMultidimensionalArray().
   *
   * @return array<string>
   *   Returns data provider.
   */
  public static function flattenMultidimensionalArrayDataProvider(): array {
    return [
      [
        ['drush.alias' => "self"],
        '.',
        ['drush' => ['alias' => 'self']],
      ],
      [
        ['drupal-db-database' => 'drupal', 'drupal-db-username' => 'root', 'drupal-db-password' => 'root'],
        '-',
        ['drupal' => ['db' => ['database' => 'drupal', 'username' => 'root', 'password' => 'root']]],
      ],
    ];
  }

}
