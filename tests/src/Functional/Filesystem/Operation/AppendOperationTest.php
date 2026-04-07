<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\AppendOperation;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Functional test for AppendOperation.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\AppendOperation
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\BaseOperation
 */
class AppendOperationTest extends TestCase {

  /**
   * A temporary directory for test fixtures.
   */
  private string $tmpDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->tmpDir = sys_get_temp_dir() . '/drs_append_test_' . uniqid('', TRUE);
    mkdir($this->tmpDir, 0777, TRUE);
  }

  /**
   * Tests append behaviour when the payload uses the 'content' key.
   *
   * Covers success (new content), skip (already present), and trim-based skip.
   *
   * @dataProvider appendContentKeyPayloadProvider
   */
  public function testAppendWithContentKeyPayload(string $initialContent, string $appendContent, OperationStatus $expectedStatus, string $expectedFileContent): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, $initialContent);

    $op = new AppendOperation($dest, [OperationKey::Content->value => $appendContent]);
    $result = $op->execute();

    $this->assertSame($expectedStatus, $result->getStatus());

    if ($expectedStatus === OperationStatus::Success) {
      $this->assertTrue($result->isSuccess());
      $this->assertSame(sprintf('Appended content to %s', $dest), $result->getMessage());
    }
    else {
      $this->assertTrue($result->isSkipped());
      $this->assertSame(sprintf('Content already present in — %s', $dest), $result->getMessage());
    }

    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests append behaviour when the payload is a plain string file path.
   *
   * Covers both success (content not yet present) and skip (already present).
   *
   * @dataProvider appendStringPathPayloadProvider
   */
  public function testAppendWithStringPathPayload(string $initialDestContent, string $sourceContent, OperationStatus $expectedStatus, string $expectedFileContent): void {
    $dest = $this->tmpDir . '/dest.txt';
    $source = $this->tmpDir . '/source.txt';
    file_put_contents($dest, $initialDestContent);
    file_put_contents($source, $sourceContent);

    $op = new AppendOperation($dest, $source);
    $result = $op->execute();

    $this->assertSame($expectedStatus, $result->getStatus());

    if ($expectedStatus === OperationStatus::Success) {
      $this->assertTrue($result->isSuccess());
      $this->assertSame(sprintf('Appended content to %s', $dest), $result->getMessage());
    }
    else {
      $this->assertTrue($result->isSkipped());
      $this->assertSame(sprintf('Content already present in — %s', $dest), $result->getMessage());
    }

    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests appending from a 'path' key in associative array payload.
   */
  public function testAppendFromPathKeyPayload(): void {
    $dest = $this->tmpDir . '/dest.txt';
    $source = $this->tmpDir . '/source.txt';
    file_put_contents($dest, 'initial');
    file_put_contents($source, ' extra');
    $op = new AppendOperation($dest, [OperationKey::Path->value => $source]);
    $result = $op->execute();
    $this->assertTrue($result->isSuccess());
    $this->assertSame(sprintf('Appended content to %s', $dest), $result->getMessage());
    $this->assertSame('initial extra', file_get_contents($dest));
  }

  /**
   * Tests append behaviour when the payload is a list of content entries.
   *
   * Covers both full append (all entries new) and partial skip (some already
   * present). In both cases the overall result is Success since at least one
   * entry was written.
   *
   * @dataProvider appendListPayloadProvider
   */
  public function testAppendWithListPayload(string $initialContent, OperationStatus $expectedStatus, string $expectedFileContent): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, $initialContent);

    $op = new AppendOperation($dest, [
      [OperationKey::Content->value => ' one'],
      [OperationKey::Content->value => ' two'],
    ]);
    $result = $op->execute();

    $this->assertSame($expectedStatus, $result->getStatus());
    $this->assertTrue($result->isSuccess());
    $this->assertSame(sprintf('Appended content to %s', $dest), $result->getMessage());
    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests that the result refers back to the originating operation.
   */
  public function testResultHasOperationReference(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'initial');
    $op = new AppendOperation($dest, [OperationKey::Content->value => ' new']);
    $result = $op->execute();
    $this->assertSame($op, $result->getOperation());
  }

  /**
   * Tests that a missing destination file returns a Failed result.
   */
  public function testAppendReturnsFailedWhenDestinationMissing(): void {
    $dest = $this->tmpDir . '/missing.txt';
    $op = new AppendOperation($dest, [OperationKey::Content->value => 'hello']);
    $result = $op->execute();
    $this->assertTrue($result->isFailed());
    $this->assertSame(OperationStatus::Failed, $result->getStatus());
    $this->assertSame(
      sprintf('Destination file must exist and be readable: %s', $dest),
      $result->getMessage()
    );
  }

  /**
   * Tests that validate() is called and throws when destination is missing.
   */
  public function testValidateThrowsWhenDestinationMissing(): void {
    $dest = $this->tmpDir . '/missing.txt';
    $op = new AppendOperation($dest, [OperationKey::Content->value => 'hello']);
    $this->expectException(\AssertionError::class);
    $op->validate();
  }

  /**
   * Tests that an invalid payload (no 'path' or 'content' key) returns Failed.
   *
   * BaseOperation::getContentFromPayload() throws \Exception when the payload
   * contains neither key. execute() catches it and
   * returns OperationStatus::Failed.
   */
  public function testAppendReturnsFailedForInvalidPayloadKey(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'initial');
    $op = new AppendOperation($dest, ['unknownkey' => 'value']);
    $result = $op->execute();
    $this->assertTrue($result->isFailed());
    $this->assertSame(OperationStatus::Failed, $result->getStatus());
    $this->assertStringContainsString("'path' or 'content'", $result->getMessage());
  }

  /**
   * Data provider for testAppendWithContentKeyPayload().
   *
   * Each case covers a different combination of initial file content and
   * expected outcome when using the 'content' key as the payload.
   *
   * @return array<string, array{
   *   initialContent: string,
   *   appendContent: string,
   *   expectedStatus: OperationStatus,
   *   expectedFileContent: string,
   *   }>
   */
  public static function appendContentKeyPayloadProvider(): array {
    return [
      'new content is appended successfully' => [
        'initialContent' => 'initial',
        'appendContent' => ' appended',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'initial appended',
      ],
      'already-present content is skipped' => [
        'initialContent' => 'initial appended',
        'appendContent' => ' appended',
        'expectedStatus' => OperationStatus::Skipped,
        'expectedFileContent' => 'initial appended',
      ],
      'content wrapped in newlines is skipped (trim check)' => [
        'initialContent' => 'existing content',
        'appendContent' => "\nexisting content\n",
        'expectedStatus' => OperationStatus::Skipped,
        'expectedFileContent' => 'existing content',
      ],
    ];
  }

  /**
   * Data provider for testAppendWithStringPathPayload().
   *
   * Each case covers a different combination of initial destination content and
   * expected outcome when the payload is a plain string file path.
   *
   * @return array<string, array{
   *   initialDestContent: string,
   *   sourceContent: string,
   *   expectedStatus: OperationStatus,
   *   expectedFileContent: string,
   *   }>
   */
  public static function appendStringPathPayloadProvider(): array {
    return [
      'source content is appended when not yet present' => [
        'initialDestContent' => 'initial',
        'sourceContent' => ' from source',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'initial from source',
      ],
      'source content is skipped when already present' => [
        'initialDestContent' => 'initial from source',
        'sourceContent' => ' from source',
        'expectedStatus' => OperationStatus::Skipped,
        'expectedFileContent' => 'initial from source',
      ],
    ];
  }

  /**
   * Data provider for testAppendWithListPayload().
   *
   * Each case covers a different initial file state and expected outcome when
   * the payload is a list of content entries.
   *
   * @return array<string, array{
   *   initialContent: string,
   *   expectedStatus: OperationStatus,
   *   expectedFileContent: string,
   *   }>
   */
  public static function appendListPayloadProvider(): array {
    return [
      'all entries appended when none are present' => [
        'initialContent' => 'initial',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'initial one two',
      ],
      'partial skip — one entry already present, other is appended' => [
        'initialContent' => 'initial one',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'initial one two',
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
