<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OperationType enum.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType
 */
class OperationTypeTest extends TestCase {

  /**
   * Tests that each enum case exposes the expected string value.
   *
   * @dataProvider enumValueProvider
   */
  public function testValueAndFromResolution(OperationType $case, string $expectedValue): void {
    $this->assertSame($expectedValue, $case->value);
    $this->assertSame($case, OperationType::from($expectedValue));
  }

  /**
   * Tests that each operation type reports the correct destructive flag.
   *
   * @dataProvider destructiveProvider
   */
  public function testIsDestructive(OperationType $case, bool $expectedDestructive): void {
    $this->assertSame($expectedDestructive, $case->isDestructive());
  }

  /**
   * Tests that each operation type reports the correct supported payload keys.
   *
   * @dataProvider supportedKeysProvider
   */
  public function testSupportedKeys(OperationType $case, array $expectedContains, array $expectedNotContains): void {
    $keys = $case->supportedKeys();
    foreach ($expectedContains as $key) {
      $this->assertContains($key, $keys);
    }
    foreach ($expectedNotContains as $key) {
      $this->assertNotContains($key, $keys);
    }
  }

  /**
   * Data provider for testValueAndFromResolution().
   *
   * @return array<string, array{case: OperationType, expectedValue: string}>
   *   Each case covers a different enum case, verifying that it exposes the
   *   expected string value and that it resolves back correctly via from().
   */
  public static function enumValueProvider(): array {
    return [
      'Copy has value "copy"' => ['case' => OperationType::Copy, 'expectedValue' => 'copy'],
      'Append has value "append"' => ['case' => OperationType::Append, 'expectedValue' => 'append'],
      'Prepend has value "prepend"' => ['case' => OperationType::Prepend, 'expectedValue' => 'prepend'],
    ];
  }

  /**
   * Data provider for testIsDestructive().
   *
   * @return array<string, array{case: OperationType, expectedDestructive: bool}>
   *   Returns each case in its own test, verifying that it reports the
   *   expected destructive flag.
   */
  public static function destructiveProvider(): array {
    return [
      'Copy is destructive' => ['case' => OperationType::Copy, 'expectedDestructive' => TRUE],
      'Append is not destructive' => ['case' => OperationType::Append, 'expectedDestructive' => FALSE],
      'Prepend is not destructive' => ['case' => OperationType::Prepend, 'expectedDestructive' => FALSE],
    ];
  }

  /**
   * Data provider for testSupportedKeys().
   *
   * Each case lists keys that must be present and keys that must be absent
   * for the given operation type.
   *
   * @return array<string, array{
   *   case: OperationType,
   *   expectedContains: string[],
   *   expectedNotContains: string[],
   *   }>
   */
  public static function supportedKeysProvider(): array {
    return [
      'Copy supports path, overwrite, with-placeholder; not content' => [
        'case' => OperationType::Copy,
        'expectedContains' => [OperationKey::Path->value, OperationKey::Overwrite->value, OperationKey::Placeholder->value],
        'expectedNotContains' => [OperationKey::Content->value],
      ],
      'Append supports path, content; not overwrite or with-placeholder' => [
        'case' => OperationType::Append,
        'expectedContains' => [OperationKey::Path->value, OperationKey::Content->value],
        'expectedNotContains' => [OperationKey::Overwrite->value, OperationKey::Placeholder->value],
      ],
      'Prepend supports path, content; not overwrite or with-placeholder' => [
        'case' => OperationType::Prepend,
        'expectedContains' => [OperationKey::Path->value, OperationKey::Content->value],
        'expectedNotContains' => [OperationKey::Overwrite->value, OperationKey::Placeholder->value],
      ],
    ];
  }

}
