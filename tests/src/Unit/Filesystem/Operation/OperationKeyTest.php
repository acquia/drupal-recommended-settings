<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OperationKey enum.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey
 */
class OperationKeyTest extends TestCase {

  /**
   * Tests that each enum case exposes the expected string value.
   *
   * @dataProvider enumCaseProvider
   */
  public function testValueAndFromResolution(OperationKey $case, string $expectedValue): void {
    $this->assertSame($expectedValue, $case->value);
    $this->assertSame($case, OperationKey::from($expectedValue));
  }

  /**
   * Tests that each enum case returns the expected schema type.
   *
   * @dataProvider schemaTypeProvider
   */
  public function testSchemaDefinitionType(OperationKey $case, string $expectedType): void {
    $schema = $case->getSchemaDefinition();
    $this->assertArrayHasKey('type', $schema);
    $this->assertSame($expectedType, $schema['type']);
  }

  /**
   * Data provider for testValueAndFromResolution().
   *
   * @return array<string, array{case: OperationKey, expectedValue: string}>
   *   Each case covers a different enum case, verifying that it exposes the
   *   expected string value and that it resolves back correctly via from().
   */
  public static function enumCaseProvider(): array {
    return [
      'Overwrite has value "overwrite"' => [
        'case' => OperationKey::Overwrite,
        'expectedValue' => 'overwrite',
      ],
      'Path has value "path"' => [
        'case' => OperationKey::Path,
        'expectedValue' => 'path',
      ],
      'Content has value "content"' => [
        'case' => OperationKey::Content,
        'expectedValue' => 'content',
      ],
      'Placeholder has value "with-placeholder"' => [
        'case' => OperationKey::Placeholder,
        'expectedValue' => 'with-placeholder',
      ],
    ];
  }

  /**
   * Data provider for testSchemaDefinitionType().
   *
   * @return array<string, array{case: OperationKey, expectedType: string}>
   *   Each case covers a different enum case, verifying that its schema
   *   definition includes the expected type.
   */
  public static function schemaTypeProvider(): array {
    return [
      'Overwrite schema type is bool' => [
        'case' => OperationKey::Overwrite,
        'expectedType' => 'bool',
      ],
      'Placeholder schema type is bool' => [
        'case' => OperationKey::Placeholder,
        'expectedType' => 'bool',
      ],
      'Path schema type is file' => [
        'case' => OperationKey::Path,
        'expectedType' => 'file',
      ],
      'Content schema type is string' => [
        'case' => OperationKey::Content,
        'expectedType' => 'string',
      ],
    ];
  }

}
