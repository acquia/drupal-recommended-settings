<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Config;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Config\SettingsConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;
use Acquia\Drupal\RecommendedSettings\Tests\Traits\FileCreationTrait;

/**
 * Functional test for the SettingsConfig class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Config\SettingsConfig
 */
class SettingsConfigTest extends FunctionalTestBase {

  use FileCreationTrait;

  /**
   * Holds the path to file.
   */
  protected string $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->file = $this->getProjectRoot() . "/" . RandomString::string(5) . ".txt";
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

    $settings_config->set("a.b.c", "false");
    $settings_config->set("d", 'This should be boolean ${a.b.c} value.');
    $this->assertEquals($settings_config->get("d"), 'This should be boolean  value.');

    $settings_config->set("e", 'This is ${f} test.');
    $this->assertEquals($settings_config->get("e"), 'This is ${f} test.');
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
