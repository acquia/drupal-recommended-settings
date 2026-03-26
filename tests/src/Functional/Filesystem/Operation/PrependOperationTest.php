<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\PrependOperation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Functional test for PrependOperation.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\PrependOperation
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\BaseOperation
 */
class PrependOperationTest extends TestCase {

  /**
   * A temporary directory for test fixtures.
   */
  private string $tmpDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->tmpDir = sys_get_temp_dir() . '/drs_prepend_test_' . uniqid('', TRUE);
    mkdir($this->tmpDir, 0777, TRUE);
  }

  /**
   * Tests prepend behaviour when the payload uses the 'content' key.
   *
   * Covers success (new content), skip (already present), and trim-based skip.
   *
   * @dataProvider prependContentKeyPayloadProvider
   */
  public function testPrependWithContentKeyPayload(string $initialContent, string $prependContent, OperationStatus $expectedStatus, string $expectedFileContent): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, $initialContent);

    $op = new PrependOperation($dest, [OperationKey::Content->value => $prependContent]);
    $result = $op->execute();

    $this->assertSame($expectedStatus, $result->getStatus());

    if ($expectedStatus === OperationStatus::Success) {
      $this->assertTrue($result->isSuccess());
      $this->assertSame(sprintf('Prepended content to %s', $dest), $result->getMessage());
    }
    else {
      $this->assertTrue($result->isSkipped());
      $this->assertSame(sprintf('Content already present in — %s', $dest), $result->getMessage());
    }

    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests prepend behaviour when the payload is a plain string file path.
   *
   * Covers both success (content not yet present) and skip (already present).
   *
   * @dataProvider prependStringPathPayloadProvider
   */
  public function testPrependWithStringPathPayload(string $initialDestContent, string $sourceContent, OperationStatus $expectedStatus, string $expectedFileContent): void {
    $dest = $this->tmpDir . '/dest.txt';
    $source = $this->tmpDir . '/source.txt';
    file_put_contents($dest, $initialDestContent);
    file_put_contents($source, $sourceContent);

    $op = new PrependOperation($dest, $source);
    $result = $op->execute();

    $this->assertSame($expectedStatus, $result->getStatus());

    if ($expectedStatus === OperationStatus::Success) {
      $this->assertTrue($result->isSuccess());
      $this->assertSame(sprintf('Prepended content to %s', $dest), $result->getMessage());
    }
    else {
      $this->assertTrue($result->isSkipped());
      $this->assertSame(sprintf('Content already present in — %s', $dest), $result->getMessage());
    }

    $this->assertSame($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests prepending from a 'path' key in associative array payload.
   */
  public function testPrependFromPathKeyPayload(): void {
    $dest = $this->tmpDir . '/dest.txt';
    $source = $this->tmpDir . '/source.txt';
    file_put_contents($dest, 'world');
    file_put_contents($source, 'hello ');
    $op = new PrependOperation($dest, [OperationKey::Path->value => $source]);
    $result = $op->execute();
    $this->assertTrue($result->isSuccess());
    $this->assertSame(sprintf('Prepended content to %s', $dest), $result->getMessage());
    $this->assertSame('hello world', file_get_contents($dest));
  }

  /**
   * Tests prepend behaviour when the payload is a list of content entries.
   *
   * Covers both full prepend (all entries new) and partial skip (some already
   * present). In both cases the overall result is Success since at least one
   * entry was written.
   *
   * @dataProvider prependListPayloadProvider
   */
  public function testPrependWithListPayload(string $initialContent, OperationStatus $expectedStatus, string $expectedFileContent): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, $initialContent);

    $op = new PrependOperation($dest, [
      [OperationKey::Content->value => 'start '],
      [OperationKey::Content->value => 'middle '],
    ]);
    $result = $op->execute();

    $this->assertSame($expectedStatus, $result->getStatus());
    $this->assertTrue($result->isSuccess());
    $this->assertSame(sprintf('Prepended content to %s', $dest), $result->getMessage());
    $this->assertStringContainsString($expectedFileContent, file_get_contents($dest));
  }

  /**
   * Tests that the result refers back to the originating operation.
   */
  public function testResultHasOperationReference(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'world');
    $op = new PrependOperation($dest, [OperationKey::Content->value => 'hello ']);
    $result = $op->execute();
    $this->assertSame($op, $result->getOperation());
  }

  /**
   * Tests that a missing destination file returns a Failed result.
   */
  public function testPrependReturnsFailedWhenDestinationMissing(): void {
    $dest = $this->tmpDir . '/missing.txt';
    $op = new PrependOperation($dest, [OperationKey::Content->value => 'hello']);
    $result = $op->execute();
    $this->assertTrue($result->isFailed());
    $this->assertSame(OperationStatus::Failed, $result->getStatus());
    $this->assertSame(
      sprintf('Destination file must exist and be readable: %s', $dest),
      $result->getMessage()
    );
  }

  /**
   * Tests that validate() throws when destination is missing.
   */
  public function testValidateThrowsWhenDestinationMissing(): void {
    $dest = $this->tmpDir . '/missing.txt';
    $op = new PrependOperation($dest, [OperationKey::Content->value => 'hello']);
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
  public function testPrependReturnsFailedForInvalidPayloadKey(): void {
    $dest = $this->tmpDir . '/dest.txt';
    file_put_contents($dest, 'world');
    $op = new PrependOperation($dest, ['unknownkey' => 'value']);
    $result = $op->execute();
    $this->assertTrue($result->isFailed());
    $this->assertSame(OperationStatus::Failed, $result->getStatus());
    $this->assertStringContainsString("'path' or 'content'", $result->getMessage());
  }

  /**
   * Data provider for testPrependWithContentKeyPayload().
   *
   * Each case covers a different combination of initial file content and
   * expected outcome when using the 'content' key as the payload.
   *
   * @return array<string, array{
   *   initialContent: string,
   *   prependContent: string,
   *   expectedStatus: OperationStatus,
   *   expectedFileContent: string,
   *   }>
   */
  public static function prependContentKeyPayloadProvider(): array {
    return [
      'new content is prepended successfully' => [
        'initialContent' => 'world',
        'prependContent' => 'hello ',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'hello world',
      ],
      'already-present content is skipped' => [
        'initialContent' => 'hello world',
        'prependContent' => 'hello ',
        'expectedStatus' => OperationStatus::Skipped,
        'expectedFileContent' => 'hello world',
      ],
      'content wrapped in newlines is skipped (trim check)' => [
        'initialContent' => 'existing content',
        'prependContent' => "\nexisting content\n",
        'expectedStatus' => OperationStatus::Skipped,
        'expectedFileContent' => 'existing content',
      ],
    ];
  }

  /**
   * Data provider for testPrependWithStringPathPayload().
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
  public static function prependStringPathPayloadProvider(): array {
    return [
      'source content is prepended when not yet present' => [
        'initialDestContent' => 'world',
        'sourceContent' => 'hello ',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'hello world',
      ],
      'source content is skipped when already present' => [
        'initialDestContent' => 'hello world',
        'sourceContent' => 'hello ',
        'expectedStatus' => OperationStatus::Skipped,
        'expectedFileContent' => 'hello world',
      ],
    ];
  }

  /**
   * Data provider for testPrependWithListPayload().
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
  public static function prependListPayloadProvider(): array {
    return [
      'all entries prepended when none are present' => [
        'initialContent' => 'end',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'start middle end',
      ],
      'partial skip — one entry already present, other is prepended' => [
        'initialContent' => 'start end',
        'expectedStatus' => OperationStatus::Success,
        'expectedFileContent' => 'middle ',
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
