<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Exceptions\InvalidMappingException;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\AppendOperation;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\CopyOperation;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationHandler;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\PrependOperation;
use Consolidation\Config\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Functional test for FileOperationHandler.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationHandler
 */
class FileOperationHandlerTest extends TestCase {

  /**
   * A temporary directory for test fixtures.
   */
  private string $tmpDir;

  /**
   * A minimal Config instance.
   */
  private Config $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->tmpDir = sys_get_temp_dir() . '/drs_handler_test_' . uniqid('', TRUE);
    mkdir($this->tmpDir, 0777, TRUE);
    $this->config = new Config();
  }

  /**
   * Tests that a FALSE value causes destination to be skipped (no operation).
   */
  public function testHandleSkipsFalseEntry(): void {
    $dest = $this->tmpDir . '/dest.txt';
    $handler = new FileOperationHandler($this->config);
    $operations = $handler->handle([$dest => FALSE]);
    $this->assertEmpty($operations);
  }

  /**
   * Tests that a TRUE value throws an AssertionError.
   */
  public function testHandleThrowsForTrueEntry(): void {
    $dest = $this->tmpDir . '/dest.txt';
    $handler = new FileOperationHandler($this->config);
    $this->expectException(\AssertionError::class);
    $handler->handle([$dest => TRUE]);
  }

  /**
   * Tests that an empty string destination throws InvalidMappingException.
   */
  public function testHandleThrowsForEmptyStringDestination(): void {
    $handler = new FileOperationHandler($this->config);
    $this->expectException(InvalidMappingException::class);
    $handler->handle(['' => 'source.txt']);
  }

  /**
   * Tests that a non-local destination path throws InvalidMappingException.
   */
  public function testHandleThrowsForNonLocalDestination(): void {
    $handler = new FileOperationHandler($this->config);
    $this->expectException(InvalidMappingException::class);
    $handler->handle(['ftp://example.com/dest.txt' => 'source.txt']);
  }

  /**
   * Tests that a bare string source produces a single CopyOperation.
   */
  public function testHandleBareStringSourceCreatesCopyOperation(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'content');
    $handler = new FileOperationHandler($this->config);
    $operations = $handler->handle([$dest => $source]);
    $this->assertCount(1, $operations);
    $this->assertInstanceOf(CopyOperation::class, $operations[0]);
    $this->assertSame($dest, $operations[0]->getDestination());
  }

  /**
   * Tests that each operation type key produces the correct operation class.
   *
   * Covers: explicit copy, append, and prepend operation types.
   *
   * @dataProvider operationTypeCreatesCorrectClassProvider
   */
  public function testHandleOperationTypeCreatesCorrectClass(string $fileContent, array $mapping, string $expectedClass): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'content');
    file_put_contents($dest, $fileContent);
    // Inject the runtime source path into copy payloads.
    if (isset($mapping[OperationType::Copy->value])) {
      $mapping[OperationType::Copy->value][OperationKey::Path->value] = $source;
    }
    $handler = new FileOperationHandler($this->config);
    $operations = $handler->handle([$dest => $mapping]);
    $this->assertCount(1, $operations);
    $this->assertInstanceOf($expectedClass, $operations[0]);
    $this->assertSame($dest, $operations[0]->getDestination());
  }

  /**
   * Tests that combined operations produce multiple operation objects.
   */
  public function testHandleCombinedNonDestructiveOperations(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'middle');
    $handler = new FileOperationHandler($this->config);
    $operations = $handler->handle([
      $dest => [
        OperationType::Prepend->value => [OperationKey::Content->value => 'start '],
        OperationType::Append->value  => [OperationKey::Content->value => ' end'],
      ],
    ]);
    $this->assertCount(2, $operations);
    $this->assertInstanceOf(PrependOperation::class, $operations[0]);
    $this->assertInstanceOf(AppendOperation::class, $operations[1]);
  }

  /**
   * Tests that invalid mapping configurations throw the expected exceptions.
   *
   * Covers: unknown operation type, copy after another op (destructive), and
   * unsupported payload key for copy.
   *
   * @dataProvider invalidMappingThrowsProvider
   */
  public function testHandleInvalidMappingThrows(array $mapping, string $expectedException, string $expectedMessagePattern): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'content');
    file_put_contents($dest, 'existing');
    // Inject runtime source path where needed.
    if (isset($mapping[OperationType::Copy->value][OperationKey::Path->value])) {
      $mapping[OperationType::Copy->value][OperationKey::Path->value] = $source;
    }
    $handler = new FileOperationHandler($this->config);
    $this->expectException($expectedException);
    $this->expectExceptionMessageMatches($expectedMessagePattern);
    $handler->handle([$dest => $mapping]);
  }

  /**
   * Tests that handle() returns an empty array for an empty input.
   */
  public function testHandleReturnsEmptyArrayForEmptyInput(): void {
    $handler = new FileOperationHandler($this->config);
    $this->assertSame([], $handler->handle([]));
  }

  /**
   * Tests that multiple destinations each produce their own operations.
   */
  public function testHandleMultipleDestinations(): void {
    $source1 = $this->tmpDir . '/src1.txt';
    $source2 = $this->tmpDir . '/src2.txt';
    $dest1 = $this->tmpDir . '/dest1.txt';
    $dest2 = $this->tmpDir . '/dest2.txt';
    file_put_contents($source1, 'a');
    file_put_contents($source2, 'b');
    $handler = new FileOperationHandler($this->config);
    $operations = $handler->handle([
      $dest1 => $source1,
      $dest2 => $source2,
    ]);
    $this->assertCount(2, $operations);
    $this->assertSame($dest1, $operations[0]->getDestination());
    $this->assertSame($dest2, $operations[1]->getDestination());
  }

  /**
   * Tests that a bare string source that does not exist throws AssertionError.
   *
   * NormalizeSingleOperation() uses assert() to verify the source file exists
   * and is local. A missing source must throw \AssertionError rather than
   * silently producing a broken CopyOperation.
   */
  public function testHandleThrowsAssertionErrorForMissingBareStringSource(): void {
    $dest = $this->tmpDir . '/dest.txt';
    $handler = new FileOperationHandler($this->config);
    $this->expectException(\AssertionError::class);
    $handler->handle([$dest => $this->tmpDir . '/nonexistent_source.txt']);
  }

  /**
   * Tests that a list payload containing a non-array entry throws exception.
   *
   * ValidateOperationKeys() asserts each list entry is a non-empty array.
   * A scalar entry must throw \AssertionError.
   */
  public function testHandleThrowsAssertionErrorForNonArrayListEntry(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'content');
    $handler = new FileOperationHandler($this->config);
    $this->expectException(\AssertionError::class);
    // Append with a list payload where an entry is a plain string,
    // not an array.
    $handler->handle([$dest => [OperationType::Append->value => ['not an array']]]);
  }

  /**
   * Tests payload-level and key-value AssertionErrors thrown by validateKeys().
   *
   * Covers:
   * - Empty non-list payload (assert: must be non-empty array).
   * - Non-string operation key (assert: key must be a string).
   * - 'path' value pointing to non-existent file (assert: must be local file).
   * - 'content' value as empty string (assert: must be non-empty string).
   * - 'overwrite' value as non-bool (assert: must be boolean).
   *
   * @dataProvider validateKeysAssertionProvider
   */
  public function testHandleThrowsAssertionErrorForInvalidKeyOrValue(array $mapping): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'content');
    $handler = new FileOperationHandler($this->config);
    $this->expectException(\AssertionError::class);
    $handler->handle([$dest => $mapping]);
  }

  /**
   * Tests that an append list payload creates one AppendOperation.
   */
  public function testHandleAppendListPayloadCreatesOneOperation(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'base');
    $handler = new FileOperationHandler($this->config);
    $operations = $handler->handle([
      $dest => [
        OperationType::Append->value => [
          [OperationKey::Content->value => ' one'],
          [OperationKey::Content->value => ' two'],
        ],
      ],
    ]);
    $this->assertCount(1, $operations);
    $this->assertInstanceOf(AppendOperation::class, $operations[0]);
  }

  /**
   * Data provider for testHandleOperationTypeCreatesCorrectClass().
   *
   * Each case maps an operation type to its expected concrete class.
   *
   * @return array<string, array{
   *   fileContent: string,
   *   mapping: array<string, mixed>,
   *   expectedClass: class-string,
   *   }>
   */
  public static function operationTypeCreatesCorrectClassProvider(): array {
    return [
      'explicit copy operation creates CopyOperation' => [
        'fileContent' => '',
        'mapping' => [OperationType::Copy->value => []],
        'expectedClass' => CopyOperation::class,
      ],
      'append operation creates AppendOperation' => [
        'fileContent' => 'existing',
        'mapping' => [OperationType::Append->value => [OperationKey::Content->value => ' extra']],
        'expectedClass' => AppendOperation::class,
      ],
      'prepend operation creates PrependOperation' => [
        'fileContent' => 'world',
        'mapping' => [OperationType::Prepend->value => [OperationKey::Content->value => 'hello ']],
        'expectedClass' => PrependOperation::class,
      ],
    ];
  }

  /**
   * Data provider for testHandleInvalidMappingThrows().
   *
   * Each case describes a mapping that must throw, the expected exception,
   * class and a pattern the exception message must match.
   *
   * @return array<string, array{
   *   mapping: array<string, mixed>,
   *   expectedException: class-string,
   *   expectedMessagePattern: string,
   *   }>
   */
  public static function invalidMappingThrowsProvider(): array {
    return [
      'unknown operation type throws InvalidMappingException' => [
        'mapping' => ['unknowntype' => [OperationKey::Content->value => 'data']],
        'expectedException' => InvalidMappingException::class,
        'expectedMessagePattern' => '/invalid operation type/i',
      ],
      'copy after another operation throws InvalidMappingException (destructive)' => [
        'mapping' => [
          OperationType::Append->value => [OperationKey::Content->value => ' extra'],
          OperationType::Copy->value   => [OperationKey::Path->value => '__placeholder__'],
        ],
        'expectedException' => InvalidMappingException::class,
        'expectedMessagePattern' => '/destructive/i',
      ],
      'unsupported payload key for copy throws InvalidMappingException' => [
        'mapping' => [OperationType::Copy->value => [OperationKey::Content->value => 'data']],
        'expectedException' => InvalidMappingException::class,
        'expectedMessagePattern' => '/unsupported operation key/i',
      ],
    ];
  }

  /**
   * Data provider for testHandleThrowsAssertionErrorForInvalidKeyOrValue().
   *
   * Each case triggers a different exception inside validateOperationKeys()
   * or validateKeys() in FileOperationHandler.
   *
   * Note: paths to non-existent files are intentionally hardcoded as strings
   * because the tmpDir is not available in a static provider. The handler's
   * assert will fire before any filesystem access uses the tmpDir value.
   *
   * @return array<string, array{mapping: array<string, mixed>}>
   *   Returns mappings that trigger different assertion errors for
   *   invalid keys or values.
   */
  public static function validateKeysAssertionProvider(): array {
    return [
      'empty non-list payload throws (must be non-empty array)' => [
        'mapping' => [OperationType::Copy->value => []],
      ],
      'path value pointing to non-existent file throws (must be local file)' => [
        'mapping' => [OperationType::Copy->value => [OperationKey::Path->value => '/tmp/drs_no_such_file_' . __CLASS__ . '.txt']],
      ],
      'content value as empty string throws (must be non-empty string)' => [
        'mapping' => [OperationType::Append->value => [OperationKey::Content->value => '']],
      ],
      'overwrite value as non-bool throws (must be boolean)' => [
        'mapping' => [OperationType::Copy->value => [OperationKey::Overwrite->value => 'yes']],
      ],
      'with-placeholder value as non-bool throws (must be boolean)' => [
        'mapping' => [OperationType::Copy->value => [OperationKey::Placeholder->value => 1]],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    (new Filesystem())->remove($this->tmpDir);
  }

}
