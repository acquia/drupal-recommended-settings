<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Common;

use Acquia\Drupal\RecommendedSettings\Common\ArrayManipulator;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;

/**
 * Functional test for the ArrayManipulatorTest class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\ArrayManipulatorTest
 */
class ArrayManipulatorTest extends FunctionalBaseTest {

  /**
   * Tests ArrayManipulator::arrayMergeRecursiveExceptEmpty().
   *
   * @param $array1
   * @param $array2
   * @param $expected_array
   * @dataProvider providerTestArrayMergeRecursiveDistinct
   */
  public function testArrayMergeRecursiveDistinct(
    array $array1,
    array $array2,
    array $expected_array
  ): void {
    $this->assertEquals(ArrayManipulator::arrayMergeRecursiveDistinct($array1,
      $array2), $expected_array);
  }

  /**
   * Provides values to testArrayMergeRecursiveDistinct().
   *
   * @return mixed[]
   */
  public function providerTestArrayMergeRecursiveDistinct(): array {
    $array1 = [
      'modules' => [
        'local' => [
          'enable' => ['test'],
        ],
        'ci' => [
          'uninstall' => ['shield'],
        ],
        'prod' => [
          'enable' => ['shield','test'],
        ]
      ],
      'test_vars' => [
        'DTT_BASE_URL' => 'http://127.0.0.1:8080',
        'DTT_MINK_DRIVER_ARGS' => '["chrome", {"chrome": {"switches": ["headless"]}}, "http://127.0.0.1:4444"]',
      ],
    ];

    // Create array2 from array1 with different test_vars value.
    $array2 = $array1;
    $array2['test_vars'] = [
      'DTT_MINK_DRIVER_ARGS' => '["chrome", null, "http://localhost:4444"]',
    ];

    // Build expected array with unique key.
    $expected = $array2;
    $expected['test_vars'] = [
      'DTT_BASE_URL' => 'http://127.0.0.1:8080',
      'DTT_MINK_DRIVER_ARGS' => '["chrome", null, "http://localhost:4444"]',
    ];
    return [[$array1, $array2, $expected]];
  }

  /**
   * * Tests ArrayManipulator::expandFromDotNotatedKeys().
   *
   * @param $subject
   * @param $expected
   * @dataProvider providerDotNotatedKeys
   */
  public function testExpandFromDotNotatedKeys(array $subject, array $expected): void {
    $this->assertEquals($expected, ArrayManipulator::expandFromDotNotatedKeys($subject));
  }

  /**
   * * Tests ArrayManipulator::flattenToDotNotatedKeys().
   *
   * @param $expected
   * @param $subject
   * @dataProvider providerDotNotatedKeys
   */
  public function testFlattenToDotNotatedKeys(array $expected, array $subject): void {
    $this->assertEquals($expected, ArrayManipulator::flattenToDotNotatedKeys($subject));
  }

  /**
   * Provider to ExpandFromDotNotatedKeys() & testFlattenToDotNotatedKeys().
   *
   * @return mixed[]
   *   An array of test values to test.
   */
  public function providerDotNotatedKeys(): array {
    return [
          [['first.second' => 'value'], ['first' => ['second' => 'value']]],
      ];
  }

  /**
   * Tests ArrayManipulator::convertArrayToFlatTextArray().
   */
  public function testConvertArrayToFlatTextArray(): void {
    $array = [
          'first' => [
              'second' => [
                  'third' => 'fourth',
              ],
              'fifth' => ['black', 'white'],
              'sixth' => TRUE,
          ],
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
      ];
    $this->assertEquals($expected, ArrayManipulator::convertArrayToFlatTextArray($array));
  }

}
