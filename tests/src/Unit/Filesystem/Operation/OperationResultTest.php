<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationResult;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OperationResult.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationResult
 */
class OperationResultTest extends TestCase {

  /**
   * A mock FileOperationInterface instance.
   */
  private FileOperationInterface $operation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->operation = $this->createMock(FileOperationInterface::class);
  }

  /**
   * Tests that each status result reports correct state for all predicates.
   *
   * @dataProvider resultStateProvider
   */
  public function testResultReportsCorrectState(OperationStatus $status, string $message, bool $isSuccess, bool $isSkipped, bool $isFailed): void {
    $result = new OperationResult($status, $message, $this->operation);
    $this->assertSame($isSuccess, $result->isSuccess());
    $this->assertSame($isSkipped, $result->isSkipped());
    $this->assertSame($isFailed, $result->isFailed());
    $this->assertSame($status, $result->getStatus());
    $this->assertSame($message, $result->getMessage());
  }

  /**
   * Tests that getOperation() returns the original operation instance.
   */
  public function testGetOperationReturnsOriginalInstance(): void {
    $result = new OperationResult(OperationStatus::Success, 'done', $this->operation);
    $this->assertSame($this->operation, $result->getOperation());
  }

  /**
   * Tests that exactly one status predicate is true per result.
   */
  public function testExactlyOneStatusIsTrue(): void {
    foreach ([OperationStatus::Success, OperationStatus::Skipped, OperationStatus::Failed] as $status) {
      $result = new OperationResult($status, 'msg', $this->operation);
      $trueCount = (int) $result->isSuccess() + (int) $result->isSkipped() + (int) $result->isFailed();
      $this->assertSame(1, $trueCount, "Expected exactly one status predicate to be TRUE for $status->name");
    }
  }

  /**
   * Data provider for testResultReportsCorrectState().
   *
   * Each case covers a different OperationStatus and its expected predicate
   * values.
   *
   * @return array<string, array{
   *   status: OperationStatus,
   *   message: string,
   *   isSuccess: bool,
   *   isSkipped: bool,
   *   isFailed: bool,
   *   }>
   */
  public static function resultStateProvider(): array {
    return [
      'Success result reports isSuccess=true, others false' => [
        'status' => OperationStatus::Success,
        'message' => 'File copied.',
        'isSuccess' => TRUE,
        'isSkipped' => FALSE,
        'isFailed' => FALSE,
      ],
      'Skipped result reports isSkipped=true, others false' => [
        'status' => OperationStatus::Skipped,
        'message' => 'Already up to date.',
        'isSuccess' => FALSE,
        'isSkipped' => TRUE,
        'isFailed' => FALSE,
      ],
      'Failed result reports isFailed=true, others false' => [
        'status' => OperationStatus::Failed,
        'message' => 'Permission denied.',
        'isSuccess' => FALSE,
        'isSkipped' => FALSE,
        'isFailed' => TRUE,
      ],
    ];
  }

}
