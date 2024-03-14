<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit;

use Acquia\Drupal\RecommendedSettings\Settings;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;

class SettingsUnitTest extends FunctionalTestBase {

  /**
   * The path to settings for testing.
   */
  protected string $drupalRoot;

  /**
   * The site machine_name.
   */
  protected string $siteName;

  /**
   * The recommended settings object.
   */
  protected Settings $settings;

  /**
   * Set up test environmemt.
   */
  public function setUp(): void {
    $this->drupalRoot = $this->getDrupalRoot();
    $this->siteName = "site1";
    $this->settings = new Settings($this->drupalRoot, $this->siteName);
  }

  /**
   * Test copies the default site specific setting files.
   */
  public function testCopySiteSettings(): void {
    $method = $this->getReflectionMethod(Settings::class, "copySiteSettings");
    $is_files_copied = $method->invoke($this->settings);
    $this->assertTrue($is_files_copied);
    // Assert that default.includes.settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/' . $this->siteName . '/settings/default.includes.settings.php');
    // Assert that default.local.settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/' . $this->siteName . '/settings/default.local.settings.php');
    $files = new \FilesystemIterator($this->drupalRoot . '/sites/' . $this->siteName . '/settings', \FilesystemIterator::SKIP_DOTS);
    $this->assertEquals(2, iterator_count($files));
  }

  /**
   * Test copies the default global specific setting files.
   */
  public function testGlobalSiteSettings(): void {
    $method = $this->getReflectionMethod(Settings::class, "copyGlobalSettings");
    $is_files_copied = $method->invoke($this->settings);
    $this->assertTrue($is_files_copied);
    // Assert that default.global.settings.php file exist.
    $this->assertFileExists($this->drupalRoot . '/sites/settings/default.global.settings.php');
    $files = new \FilesystemIterator($this->drupalRoot . '/sites/settings', \FilesystemIterator::SKIP_DOTS);
    $this->assertEquals(1, iterator_count($files));
  }

  /**
   * Test AppendIfMatchesCollect Method.
   */
  public function testAppendIfMatchesCollect(): void {
    mkdir($this->drupalRoot . '/sites/' . $this->siteName);
    $settings_file = $this->drupalRoot . '/sites/' . $this->siteName . '/settings.php';
    touch($settings_file);
    $method = $this->getReflectionMethod(Settings::class, "appendIfMatchesCollect");
    $pattern = '#vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php#';
    $content = 'require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php"';
    $append_if_matches_data = $method->invokeArgs($this->settings, [
      $settings_file, $pattern, $content, FALSE,
    ]);
    $this->assertNull($append_if_matches_data);
    // Assert that settings.php file exist.
    $this->assertFileExists($settings_file);
    // Assert that settings.php file content matches exist.
    $this->assertEquals($content, file_get_contents($settings_file));
    // Again call method and it shouldn't append the content if already exist.
    $append_if_matches_data = $method->invokeArgs($this->settings, [
      $settings_file, $pattern, $content, FALSE,
    ]);
    $this->assertEquals($content, file_get_contents($settings_file));
    unlink($settings_file);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    // Remove all testing files.
    @unlink($this->drupalRoot . "/sites/settings/default.global.settings.php");
    @unlink($this->drupalRoot . "/sites/" . $this->siteName . "/settings/default.local.settings.php");
    @unlink($this->drupalRoot . "/sites/" . $this->siteName . "/settings/default.includes.settings.php");
    @rmdir($this->drupalRoot . "/sites/" . $this->siteName . "/settings");
    @rmdir($this->drupalRoot . "/sites/" . $this->siteName);
  }

}
