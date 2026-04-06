<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Filesystem\Operation;

use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\AppendOperation;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\CopyOperation;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\FileOperationInterface;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationFactory;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationKey;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationType;
use Acquia\Drupal\RecommendedSettings\Filesystem\Operation\PrependOperation;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OperationFactory.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Filesystem\Operation\OperationFactory
 */
class OperationFactoryTest extends TestCase {

  /**
   * The factory under test.
   */
  private OperationFactory $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->factory = new OperationFactory();
  }

  /**
   * Tests that create() returns the correct operation class.
   *
   * @dataProvider createOperationProvider
   */
  public function testCreateReturnsCorrectOperation(OperationType $type, string $destination, mixed $payload, string $expectedClass): void {
    $op = $this->factory->create($type, $destination, $payload);
    $this->assertInstanceOf($expectedClass, $op);
    $this->assertSame($destination, $op->getDestination());
    $this->assertSame($payload, $op->getPayload());
  }

  /**
   * Tests that operations created by factory implement FileOperationInterface.
   */
  public function testAllCreatedOperationsImplementInterface(): void {
    foreach (OperationType::cases() as $type) {
      $op = $this->factory->create($type, '/dest.php', [OperationKey::Content->value => 'x']);
      $this->assertInstanceOf(FileOperationInterface::class, $op);
    }
  }

  /**
   * Data provider for testCreateReturnsCorrectOperation().
   *
   * Each case covers a different operation type, verifying the correct concrete
   * class is instantiated and that destination and payload are preserved.
   *
   * @return array<string, array{
   *   type: OperationType,
   *   destination: string,
   *   payload: mixed,
   *   expectedClass: class-string,
   *   }>
   */
  public static function createOperationProvider(): array {
    return [
      'Copy operation returns CopyOperation with string payload' => [
        'type' => OperationType::Copy,
        'destination' => '/dest.php',
        'payload' => '/source.php',
        'expectedClass' => CopyOperation::class,
      ],
      'Append operation returns AppendOperation with array payload' => [
        'type' => OperationType::Append,
        'destination' => '/dest.php',
        'payload' => [OperationKey::Content->value => 'appended text'],
        'expectedClass' => AppendOperation::class,
      ],
      'Prepend operation returns PrependOperation with array payload' => [
        'type' => OperationType::Prepend,
        'destination' => '/dest.php',
        'payload' => [OperationKey::Content->value => 'prepended text'],
        'expectedClass' => PrependOperation::class,
      ],
    ];
  }

}
