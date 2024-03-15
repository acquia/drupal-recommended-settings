<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Helpers;

use Acquia\Drupal\RecommendedSettings\Helpers\HashGenerator;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Composer\IO\IOInterface;

/**
 * Functional test for the HashGenerator class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Helpers\HashGenerator
 */
class HashGeneratorTest extends FunctionalTestBase {

  /**
   * Composer's I/O service.
   */
  protected IOInterface $io;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->io = $this->createMock(IOInterface::class);
  }

  /**
   * Test  HashGenerator::generate().
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function testGenerate(): void {
    HashGenerator::generate($this->getProjectRoot(), $this->io);
    $this->assertFileExists($this->getProjectRoot() . '/salt.txt');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    @unlink($this->getProjectRoot() . '/salt.txt');
  }

}