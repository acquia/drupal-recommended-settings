<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Common;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;

/**
 * Functional test for the RandomString class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\RandomString
 */
class RandomStringTest extends FunctionalBaseTest {

  /**
   * Tests RandomString::string()
   *
   * @param $str1
   * @param $str2
   * @dataProvider providerTestString
   */
  public function testString(string $str1, string $str2): void {

    // Check that its string.
    $this->assertIsString($str1);
    $this->assertIsString($str2);

    // Check that string is unique.
    $this->assertNotEquals($str1, $str2);

    // Check that both string has same length.
    $this->assertTrue((bool) strlen($str1), strlen($str2));
  }

  /**
   * Data provider fot testString.
   * @return mixed[]
   */
  public function providerTestString(): array {
    return [
      [
        RandomString::string(8, TRUE, NULL, "abcde"),
        RandomString::string(8, TRUE, NULL, "abcde")
      ],
      [
        RandomString::string(8, TRUE, NULL, "abcde"),
        RandomString::string(8, TRUE, NULL, "abcde")
      ],
      [
        RandomString::string(8, TRUE, NULL, "abcde"),
        RandomString::string(8, TRUE, NULL, "abcde")
      ],
      [
        RandomString::string(8, TRUE, NULL, "abcde"),
        RandomString::string(8, TRUE, NULL, "abcde")
      ]
    ];

  }

}
