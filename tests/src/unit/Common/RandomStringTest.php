<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Common;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the RandomStringTest class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\RandomString
 */
class RandomStringTest extends TestCase {

  /**
   * Checks if method id called.
   */
  protected bool $called = FALSE;

  /**
   * Tests string() method.
   */
  public function testString(): void {
    $string = RandomString::string();
    $this->assertIsString($string);
    $this->assertSame(8, strlen($string));

    $alphabets = 'abcdefghijklmnopqrstuvwxyz';
    $capital_alphabets = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $digits = '0123456789';

    $string = RandomString::string(8, TRUE, NULL, $alphabets);
    $this->assertMatchesAllRegularExpression('/[a-z]/', $string);

    $string = RandomString::string(10, TRUE, NULL, $capital_alphabets);
    $this->assertMatchesAllRegularExpression('/[A-Z]/', $string);

    $string = RandomString::string(15, TRUE, NULL, $alphabets . $capital_alphabets);
    $this->assertSame(15, strlen($string));
    $this->assertMatchesAllRegularExpression('/[a-zA-Z]/', $string);

    $string = RandomString::string(5, TRUE, NULL, $digits);
    $this->assertSame(5, strlen($string));
    $this->assertMatchesAllRegularExpression('/[0-9]/', $string);

    RandomString::string(5, TRUE, \Closure::fromCallable([$this, 'validateDigits']), $digits);
    $this->assertTrue($this->called);
  }

  /**
   * Tests RuntimeException from method string().
   */
  public function testRandomStringException(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Unable to generate a unique random name");
    // With given two characters, we can generate only four unique characters
    // ie 11, 21, 22, & 12. Then exception should appear
    RandomString::string(2, TRUE, NULL, '12');
    RandomString::string(2, TRUE, NULL, '12');
    RandomString::string(2, TRUE, NULL, '12');
    RandomString::string(2, TRUE, NULL, '12');
    RandomString::string(2, TRUE, NULL, '12');
  }

  /**
   * Assets the regular expression with global modifier.
   *
   * @param string $pattern
   *   Given input pattern.
   * @param string $string
   *   Given input string to match.
   */
  private function assertMatchesAllRegularExpression(string $pattern, string $string): void {
    preg_match_all($pattern, $string, $matches);
    $this->assertEquals(str_split($string), $matches[0]);
  }

  /**
   * Function to validate digits.
   *
   * @param string $string
   *   Generated random string.
   */
  protected function validateDigits(string $string): bool {
    $this->called = TRUE;
    $this->assertIsNumeric($string);
    return TRUE;
  }

}
