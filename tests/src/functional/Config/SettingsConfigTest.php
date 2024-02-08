<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Config;

use Acquia\Drupal\RecommendedSettings\Config\SettingsConfig;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;

class SettingsConfigTest extends FunctionalBaseTest {

  protected string $file;

  protected function setUp(): void {
    parent::setUp();
    $this->file = $this->getFixtureDirectory() . "/" . $this->randomFileName() . ".txt";
  }

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

  public function testGetMethod(): void {
    $settings_config = new SettingsConfig();
    $settings_config->set("a.b.c", "true");
    $settings_config->set("d", 'This should be boolean ${a.b.c} value.');
    $this->assertEquals($settings_config->get("d"), 'This should be boolean 1 value.');
  }

  public function testReplaceFileVariables(): void {
    $content = <<<Content
My name is '\${name.firstname} \${name.lastname}'.
I live in city: '\${country.state.city.name}' of state: '\${country.state.name}' in country: '\${country.name}'.
Content;
    $this->createConfigFile($content);
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

  protected function tearDown(): void {
    parent::tearDown();
    @unlink($this->file);
  }

  protected function randomFileName(): string {
    return substr(
      str_shuffle(
        str_repeat(
          $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(5 / strlen($x)),
        )
      ), 1, 5);
  }

  protected function createConfigFile(string $content): void {
    $config = fopen($this->file, "w") or die("Unable to open file!");
    fwrite($config, $content);
    fclose($config);
  }

}
