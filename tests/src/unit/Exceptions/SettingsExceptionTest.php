<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Exceptions;

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the ArrayManipulator class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
 */
class SettingsExceptionTest extends TestCase {

  public function testSettingsException(): void {
    $exception = new SettingsException("Test Exception.");
    $this->assertEquals(
      "Test Exception." . PHP_EOL . " For troubleshooting guidance and support, see https://github.com/acquia/drupal-recommended-settings",
      $exception->getMessage(),
    );
  }

}
