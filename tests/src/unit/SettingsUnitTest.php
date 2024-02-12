<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\unit;

use Acquia\Drupal\RecommendedSettings\Settings;

class SettingsUnitTest extends DrsPhpUnitBase {

  /**
   * The path to settings for testing.
   */
  protected string $drupalRoot;

  /**
   * The recommended settings object.
   */
  protected Settings $settings;

  /**
   * Set up test environmemt.
   */
  public function setUp(): void {
    $this->drupalRoot = dirname(__FILE__, 3) . "/fixtures/project/docroot";
    $this->settings = new Settings($this->drupalRoot, "default");
    $this->setClass($this->settings);
  }

  /**
   * Test copies the default site specific setting files.
   */
  public function testCopySiteSettings(): void {
    $getSettingsMethod = $this->getClassMethod("copySiteSettings");
    $actualSettingsData = $getSettingsMethod->invoke($this->settings);
    $this->assertTrue($actualSettingsData);
    // Assert that default.includes.settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/default/settings/default.includes.settings.php');
    // Assert that default.local.settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/default/settings/default.local.settings.php');
  }

  /**
   * Test copies the default global specific setting files.
   */
  public function testGlobalSiteSettings(): void {
    $getSettingsMethod = $this->getClassMethod("copyGlobalSettings");
    $actualGlobalSettingsData = $getSettingsMethod->invoke($this->settings);
    $this->assertTrue($actualGlobalSettingsData);
    // Assert that default.global.settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/settings/default.global.settings.php');
  }

  /**
   * Test AppendIfMatchesCollect Method.
   */
  public function testAppendIfMatchesCollect(): void {
    $getAppendIfMatchesCollectMethod = $this->getClassMethod("appendIfMatchesCollect");
    $file = $this->drupalRoot . '/sites/default/settings.php';
    $pattern = '#vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php#';
    $content = 'require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php"';
    $appendIfMatchesCollectData = $getAppendIfMatchesCollectMethod->invokeArgs($this->settings, [
      $file, $pattern, $content, FALSE,
    ]);
    $this->assertNull($appendIfMatchesCollectData);
    // Assert that settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/default/settings.php');
    // Assert that settings.php file content matches exist.
    $this->assertEquals($content, file_get_contents($this->drupalRoot . '/sites/default/settings.php'));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    // Remove all testing files.
    unlink($this->drupalRoot . "/sites/default/settings.php");
    unlink($this->drupalRoot . "/sites/settings/default.global.settings.php");
    unlink($this->drupalRoot . "/sites/default/settings/default.local.settings.php");
    unlink($this->drupalRoot . "/sites/default/settings/default.includes.settings.php");
  }

}
