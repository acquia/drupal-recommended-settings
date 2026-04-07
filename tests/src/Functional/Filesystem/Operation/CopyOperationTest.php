<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\CopyOperation;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus;
use Consolidation\Config\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Functional test for CopyOperation.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\CopyOperation
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\BaseOperation
 */
class CopyOperationTest extends TestCase {

  /**
   * A temporary directory for test fixtures.
   */
  private string $tmpDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->tmpDir = sys_get_temp_dir() . '/drs_copy_test_' . uniqid('', TRUE);
    mkdir($this->tmpDir, 0777, TRUE);
  }

  /**
   * Tests copy from a bare string source path creates the destination.
   */
  public function testCopyFromStringPayloadCreatesDestination(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'hello');
    $op = new CopyOperation($dest, $source);
    $result = $op->execute();
    $this->assertTrue($result->isSuccess());
    $this->assertSame(OperationStatus::Success, $result->getStatus());
    $this->assertSame(sprintf('Copied %s → %s', $source, $dest), $result->getMessage());
    $this->assertFileExists($dest);
    $this->assertSame('hello', file_get_contents($dest));
  }

  /**
   * Tests that copying is skipped when the destination already exists.
   */
  public function testCopySkipsWhenDestinationExistsWithoutOverwrite(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'new content');
    file_put_contents($dest, 'existing content');
    $op = new CopyOperation($dest, $source);
    $result = $op->execute();
    $this->assertTrue($result->isSkipped());
    $this->assertSame(OperationStatus::Skipped, $result->getStatus());
    $this->assertSame(sprintf('Destination already exists - %s', $dest), $result->getMessage());
    // Destination must be unchanged.
    $this->assertSame('existing content', file_get_contents($dest));
  }

  /**
   * Tests copy behavior when overwrite=TRUE under different destination states.
   *
   * Covers: content differs (Success), content matches (Skipped), destination
   * missing (Success).
   *
   * @dataProvider copyWithOverwriteProvider
   */
  public function testCopyWithOverwrite(string $sourceContent, ?string $destContent, OperationStatus $expectedStatus, string $expectedMessage, string $expectedFileContent): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, $sourceContent);
    if ($destContent !== NULL) {
      file_put_contents($dest, $destContent);
    }
    $op = new CopyOperation($dest, [
      OperationKey::Path->value => $source,
      OperationKey::Overwrite->value => TRUE,
    ]);
    $result = $op->execute();
    $this->assertSame($expectedStatus, $result->getStatus());
    $this->assertSame(sprintf($expectedMessage, $source, $dest), $result->getMessage());
    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests copy behavior when with-placeholder=TRUE under different scenarios.
   *
   * Covers: fresh copy (Success), resolved content already in dest (Skipped),
   * overwrite + placeholder when content differs (Success).
   *
   * @dataProvider copyWithPlaceholderProvider
   */
  public function testCopyWithPlaceholder(string $sourceContent, ?string $destContent, array $payload, OperationStatus $expectedStatus, string $expectedMessage, string $expectedFileContent): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, $sourceContent);
    if ($destContent !== NULL) {
      file_put_contents($dest, $destContent);
    }
    $payload[OperationKey::Path->value] = $source;
    $op = new CopyOperation($dest, $payload);
    $op->setConfig(new Config(['greeting' => 'Hello World']));
    $result = $op->execute();
    $this->assertSame($expectedStatus, $result->getStatus());
    $this->assertSame(sprintf($expectedMessage, $source, $dest), $result->getMessage());
    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests that a failed copy (non-existent source) returns a Failed result.
   */
  public function testCopyReturnsFailedForMissingSource(): void {
    $dest = $this->tmpDir . '/dest.txt';
    $op = new CopyOperation($dest, $this->tmpDir . '/nonexistent.txt');
    $result = $op->execute();
    $this->assertTrue($result->isFailed());
    $this->assertSame(OperationStatus::Failed, $result->getStatus());
    $this->assertStringContainsString('nonexistent.txt', $result->getMessage());
  }

  /**
   * Tests that the result refers back to the originating operation.
   */
  public function testResultHasOperationReference(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, 'content');
    $op = new CopyOperation($dest, $source);
    $result = $op->execute();
    $this->assertSame($op, $result->getOperation());
  }

  /**
   * Tests that placeholder resolution without a config set returns Failed.
   *
   * CopyOperation::resolvePlaceholderContent() calls getConfig(), which returns
   * null when no config has been injected. This causes a TypeError that is
   * caught by execute() and returned as OperationStatus::Failed.
   */
  public function testCopyWithPlaceholderReturnsFailedWhenNoConfigSet(): void {
    $source = $this->tmpDir . '/source.txt';
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($source, '${greeting}');
    $op = new CopyOperation($dest, [
      OperationKey::Path->value => $source,
      OperationKey::Placeholder->value => TRUE,
    ]);
    // No setConfig() call — getConfig() returns null, causing a TypeError.
    $result = $op->execute();
    $this->assertTrue($result->isFailed());
    $this->assertSame(OperationStatus::Failed, $result->getStatus());
    $this->assertNotEmpty($result->getMessage());
  }

  /**
   * Tests that getDestination() and getPayload() return correct values.
   */
  public function testGettersReturnConstructorValues(): void {
    $payload = [
      OperationKey::Path->value => '/source.txt',
      OperationKey::Overwrite->value => TRUE,
    ];
    $op = new CopyOperation('/dest.txt', $payload);
    $this->assertSame('/dest.txt', $op->getDestination());
    $this->assertSame($payload, $op->getPayload());
  }

  /**
   * Data provider for testCopyWithOverwrite().
   *
   * Each case covers a different destination state when overwrite=TRUE.
   *
   * @return array<string, array{
   *   sourceContent: string,
   *   destContent: string|null,
   *   expectedStatus: OperationStatus,
   *   expectedMessage: string,
   *   expectedFileContent: string,
   *   }>
   */
  public static function copyWithOverwriteProvider(): array {
    return [
      'destination content differs — overwrites successfully' => [
        'sourceContent' => 'new content',
        'destContent' => 'old content',
        'expectedStatus' => OperationStatus::Success,
        'expectedMessage' => 'Copied (overwrite) %s → %s',
        'expectedFileContent' => 'new content',
      ],
      'destination content matches source — skipped' => [
        'sourceContent' => 'same content',
        'destContent' => 'same content',
        'expectedStatus' => OperationStatus::Skipped,
        'expectedMessage' => 'Destination already matches source - %2$s',
        'expectedFileContent' => 'same content',
      ],
      'destination does not exist — copies successfully' => [
        'sourceContent' => 'content',
        'destContent' => NULL,
        'expectedStatus' => OperationStatus::Success,
        'expectedMessage' => 'Copied (overwrite) %s → %s',
        'expectedFileContent' => 'content',
      ],
    ];
  }

  /**
   * Data provider for testCopyWithPlaceholder().
   *
   * Each case covers a different scenario when with-placeholder=TRUE.
   * The 'path' key is injected by the test method; only extra options
   * are set here.
   *
   * @return array<string, array{
   *   sourceContent: string,
   *   destContent: string|null,
   *   payload: array<string, mixed>,
   *   expectedStatus: OperationStatus,
   *   expectedMessage: string,
   *   expectedFileContent: string,
   *   }>
   */
  public static function copyWithPlaceholderProvider(): array {
    return [
      'fresh copy with placeholder resolution — success' => [
        'sourceContent' => '${greeting}',
        'destContent' => NULL,
        'payload' => [OperationKey::Placeholder->value => TRUE],
        'expectedStatus' => OperationStatus::Success,
        'expectedMessage' => 'Copied with placeholder resolution %s → %s',
        'expectedFileContent' => 'Hello World',
      ],
      'resolved content already in destination — skipped' => [
        'sourceContent' => '${greeting}',
        'destContent' => 'Hello World',
        'payload' => [OperationKey::Overwrite->value => TRUE, OperationKey::Placeholder->value => TRUE],
        'expectedStatus' => OperationStatus::Skipped,
        'expectedMessage' => 'Resolved content contains the destination content - %s',
        'expectedFileContent' => 'Hello World',
      ],
      'overwrite + placeholder when destination content differs — success' => [
        'sourceContent' => '${greeting}',
        'destContent' => 'old value',
        'payload' => [OperationKey::Overwrite->value => TRUE, OperationKey::Placeholder->value => TRUE],
        'expectedStatus' => OperationStatus::Success,
        'expectedMessage' => 'Copied with placeholder resolution (overwrite) %s → %s',
        'expectedFileContent' => 'Hello World',
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
