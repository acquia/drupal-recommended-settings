<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Functional\Drush\Traits;

use Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait;
use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalTestBase;

/**
 * Functional test for the SiteUriTrait trait.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait
 */
class SiteUriTraitTest extends FunctionalTestBase {
  use SiteUriTrait;

  /**
   * Hods the file_system class object.
   */
  protected Filesystem $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileSystem = new Filesystem();
    $this->setupSite();
  }

  /**
   * Tests the getSitesSubdirFromUri() method.
   */
  public function testGetSitesSubdirFromUri(): void {
    $drupal_root = $this->getDrupalRoot();
    $uri = $this->getSitesSubdirFromUri($drupal_root, "default");
    $this->assertEquals($uri, "default");

    $uri = $this->getSitesSubdirFromUri($drupal_root, "http://dev.acquia_cms.com");
    $this->assertEquals($uri, "dev.acquia_cms");

    $uri = $this->getSitesSubdirFromUri($drupal_root, "https://www.acquia_cms.com");
    $this->assertEquals($uri, "acquia_cms");

    $uri = $this->getSitesSubdirFromUri($drupal_root, "www.acquia.com/qa/acquia_cms");
    $this->assertEquals($uri, "qa.acquia_cms");

    $uri = $this->getSitesSubdirFromUri($drupal_root, "site2");
    $this->assertEquals($uri, "site2");

    $uri = $this->getSitesSubdirFromUri($drupal_root, "http://www.acquia.com");
    // If URI does't exist on sites.php file, fallback to site default.
    $this->assertEquals($uri, "default");

    // If uri makes pattern port.domain.path and it exists in the sites.php
    // then it return the value.
    $uri = $this->getSitesSubdirFromUri($drupal_root, "http://acquia.com/stage/acquia_cms");
    $this->assertEquals($uri, "stage.acquia_cms");

    $uri = $this->getSitesSubdirFromUri($drupal_root, "http://acquia.org:8080/developer/acquia_cms");
    $this->assertEquals($uri, "developer.acquia_cms");

    mkdir($drupal_root . "/sites/site3");
    $uri = $this->getSitesSubdirFromUri($drupal_root, "https://site3");
    $this->assertEquals($uri, "site3");

    rename($drupal_root . "/sites/default", $drupal_root . "/sites/default.back");
    $uri = $this->getSitesSubdirFromUri($drupal_root, "https://site4");
    $this->assertFalse($uri);
  }

  /**
   * Test prepareSiteUri() method.
   *
   * @param string $uri
   *   Testing uri.
   * @param string $dir_key
   *   Site sub directory key.
   *
   * @dataProvider siteUriProvider
   */
  public function testPrepareSiteUri(string $uri, string $dir_key): void {
    $site_uri = $this->prepareSiteUri($uri);
    $this->assertEquals($site_uri, $dir_key);
  }

  /**
   * Data for the testPrepareSiteUri.
   *
   * @return array<string>
   *   List of site uri and site sub directory key.
   */
  public static function siteUriProvider(): array {
    return [
      ['http://acquia.com/stage/acquia_cms', 'acquia.com.stage.acquia_cms'],
      ['http://acquia.org:8080/developer/acquia_cms', '8080.acquia.org.developer.acquia_cms'],
      ['acquia_cms_low_code', 'acquia_cms_low_code'],
      ['https://www.acquia.com/docs', 'www.acquia.com.docs'],
      ['http://acquia.org', 'acquia.org'],
      ['acquia_cms_headless', 'acquia_cms_headless'],
      ['https://dev.acquiacms.com', 'dev.acquiacms.com'],
    ];
  }

  /**
   * Creates the sites.php.
   */
  protected function setupSite(): void {
    $drupal_root = $this->getDrupalRoot();
    $sites_file = $drupal_root . "/sites/sites.php";
    $this->assertFileDoesNotExist($sites_file);
    if (file_exists($drupal_root . "/sites/example.sites.php")) {
      $this->fileSystem->copyFile($drupal_root . "/sites/example.sites.php", $sites_file);
    }
    else {
      touch($sites_file);
      $this->fileSystem->appendToFile($sites_file, "<?php" . PHP_EOL);
    }
    $this->fileSystem->appendToFile($sites_file, PHP_EOL . '$sites["http://dev.acquia_cms.com"] = "dev.acquia_cms";');
    $this->fileSystem->appendToFile($sites_file, PHP_EOL . '$sites["https://www.acquia_cms.com"] = "acquia_cms";');
    $this->fileSystem->appendToFile($sites_file, PHP_EOL . '$sites["www.acquia.com/qa/acquia_cms"] = "qa.acquia_cms";');
    $this->fileSystem->appendToFile($sites_file, PHP_EOL . '$sites["acquia.com.stage.acquia_cms"] = "stage.acquia_cms";');
    $this->fileSystem->appendToFile($sites_file, PHP_EOL . '$sites["8080.acquia.org.developer.acquia_cms"] = "developer.acquia_cms";');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    @rmdir($this->getDrupalRoot() . "/sites/site3");
    @unlink($this->getDrupalRoot() . "/sites/sites.php");
    @unlink($this->getDrupalRoot() . "/sites/site3");
    @rename($this->getDrupalRoot() . "/sites/default.back", $this->getDrupalRoot() . "/sites/default");
  }

}
