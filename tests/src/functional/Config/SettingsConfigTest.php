<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\SettingsConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;
use Acquia\Drupal\RecommendedSettings\Tests\Traits\FileCreationTraitTest;
use Acquia\Drupal\RecommendedSettings\Tests\Traits\StringTraitTest;

/**
 * Functional test for the SettingsConfig class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Config\SettingsConfig
 */
class SettingsConfigTest extends FunctionalBaseTest {

  use StringTraitTest;
  use FileCreationTraitTest;

  /**
   * Holds the path to file.
   */
  protected string $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->file = $this->getProjectRoot() . "/" . $this->randomString(5) . ".txt";
  }

  /**
   * Tests the set() method.
   */
  public function testSetMethod(): void {
    $settings_config = new SettingsConfig();
    $settings_config->set("a.b.c", "true");
    $this->assertEquals($settings_config->export(), [
      "a" => [
        "b" => [
          "c" => 1,
        ],
      ],
    ], "The string 'true', 'false' value should resolve to boolean true and false");

    $settings_config->set("d", '${a.b.c}');
    $this->assertEquals($settings_config->export(), [
      "a" => [
        "b" => [
          "c" => 1,
        ],
      ],
      "d" => 1,
    ], "The dollar notation for string 'true', 'false' also should resolve to boolean.");
  }

  /**
   * Tests the get() method.
   */
  public function testGetMethod(): void {
    $settings_config = new SettingsConfig();
    $settings_config->set("a.b.c", "true");
    $settings_config->set("d", 'This should be boolean ${a.b.c} value.');
    $this->assertEquals($settings_config->get("d"), 'This should be boolean 1 value.');
  }

  /**
   * Tests the replaceFileVariables() method.
   */
  public function testReplaceFileVariables(): void {
    $content = <<<Content
My name is '\${name.firstname} \${name.lastname}'.
I live in city: '\${country.state.city.name}' of state: '\${country.state.name}' in country: '\${country.name}'.
Content;
    $this->createFile($this->file, $content);
    $settings_config = new SettingsConfig([
      "name" => [
        "firstname" => "Drupal",
        "lastname" => "Expert",
      ],
      "country" => [
        "name" => "India",
        "state" => [
          "name" => "Maharashtra",
          "city" => [
            "name" => "Pune",
          ],
        ],
      ],
    ]);
    $settings_config->replaceFileVariables($this->file);
    $expectedContent = <<<Content
My name is 'Drupal Expert'.
I live in city: 'Pune' of state: 'Maharashtra' in country: 'India'.
Content;
    $this->assertSame($expectedContent, file_get_contents($this->file));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    @unlink($this->file);
  }

}
