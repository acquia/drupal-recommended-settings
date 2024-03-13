<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Common;

use Acquia\Drupal\RecommendedSettings\Common\StringManipulator;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the StringManipulator class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\StringManipulator
 */
class StringManipulatorTest extends TestCase {

  /**
   * Tests trimEndingLines() method.
   */
  public function testTrimEndingLines(): void {
    $actual = <<<Actual
This is input text
Line1
Line2



Actual;

    $expected = <<<Expected
This is input text
Line1
Line2
Expected;
    $actual = StringManipulator::trimEndingLines($actual, 3);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Tests trimStartingLines() method.
   */
  public function testTrimStartingLines(): void {
    $actual = <<<Actual


This is input text
Line1
Line2
Actual;

    $expected = <<<Expected
This is input text
Line1
Line2
Expected;
    $actual = StringManipulator::trimStartingLines($actual, 2);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Tests convertStringToMachineSafe() method.
   *
   * @param string $actual
   *   An actual input string.
   * @param string $expected
   *   An expected output string.
   *
   * @dataProvider convertStringToMachineSafeDataProvider
   */
  public function testConvertStringToMachineSafe(string $actual, string $expected): void {
    $actual = StringManipulator::convertStringToMachineSafe($actual);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests convertStringToPrefix() method.
   *
   * @param string $actual
   *   An actual input string.
   * @param string $expected
   *   An expected output string.
   *
   * @dataProvider convertStringToPrefixDataProvider
   */
  public function testConvertStringToPrefix(string $actual, string $expected): void {
    $actual = StringManipulator::convertStringToPrefix($actual);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests commandConvert() method.
   */
  public function testCommandConvert(): void {
    $actual = "./vendor/bin/drush site:install minimal --uri=site1 --yes";
    $actual = StringManipulator::commandConvert($actual);
    $this->assertEquals([
      "./vendor/bin/drush",
      "site:install",
      "minimal",
      "--uri=site1",
      "--yes",
    ], $actual);
  }

  /**
   * The dataProvider for method: convertStringToMachineSafe().
   *
   * @return \string[][]
   *   Returns data provider
   */
  public static function convertStringToMachineSafeDataProvider(): array {
    return [
      ["Acquia CMS Common" , "acquia_cms_common"],
      ['Acquia-CMS/Common[1]' , "acquia_cms_common_1"],
      ['111Acquia CMS Common' , "_11acquia_cms_common"],
      ['-11Acquia CMS Common' , "_11acquia_cms_common"],
      ['--Acquia CMS Common' , "__acquia_cms_common"],
      ['`~!@#$Acquia%^&*()+<>?;:"{}' , "acquia"],
    ];
  }

  /**
   * The dataProvider for method: convertStringToPrefix().
   *
   * @return \string[][]
   *   Returns data provider.
   */
  public static function convertStringToPrefixDataProvider(): array {
    return [
      ["Acquia", "A"],
      ["acquia", "A"],
      ["   acquia", "A"],
    ];
  }

}
