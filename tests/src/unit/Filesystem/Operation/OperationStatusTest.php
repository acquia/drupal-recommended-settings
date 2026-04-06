<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OperationStatus enum.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationStatus
 */
class OperationStatusTest extends TestCase {

  /**
   * Tests that all three cases exist.
   */
  public function testCasesExist(): void {
    $names = array_column(OperationStatus::cases(), 'name');
    $this->assertContains('Success', $names);
    $this->assertContains('Skipped', $names);
    $this->assertContains('Failed', $names);
    $this->assertCount(3, OperationStatus::cases());
  }

  /**
   * Tests that each case is identical to itself.
   *
   * @dataProvider sameCaseProvider
   */
  public function testCaseIsSameAsItself(OperationStatus $case): void {
    $this->assertSame($case, $case);
  }

  /**
   * Tests that distinct cases are not the same.
   *
   * @dataProvider distinctCasePairProvider
   */
  public function testDistinctCasesAreNotSame(OperationStatus $a, OperationStatus $b): void {
    $this->assertNotSame($a, $b);
  }

  /**
   * Data provider for testCaseIsSameAsItself().
   *
   * @return array<string, array{case: OperationStatus}>
   *   Returns each case in its own test, verifying it is identical to itself.
   */
  public static function sameCaseProvider(): array {
    return [
      'Success is same as itself' => ['case' => OperationStatus::Success],
      'Skipped is same as itself' => ['case' => OperationStatus::Skipped],
      'Failed is same as itself'  => ['case' => OperationStatus::Failed],
    ];
  }

  /**
   * Data provider for testDistinctCasesAreNotSame().
   *
   * @return array<string, array{a: OperationStatus, b: OperationStatus}>
   *   Returns pairs of distinct cases, verifying that they are not identical.
   */
  public static function distinctCasePairProvider(): array {
    return [
      'Success is not same as Failed'  => ['a' => OperationStatus::Success, 'b' => OperationStatus::Failed],
      'Success is not same as Skipped' => ['a' => OperationStatus::Success, 'b' => OperationStatus::Skipped],
      'Skipped is not same as Failed'  => ['a' => OperationStatus::Skipped, 'b' => OperationStatus::Failed],
    ];
  }

}
