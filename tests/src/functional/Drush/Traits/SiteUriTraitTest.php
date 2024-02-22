<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\functional\Drush\Traits;

use Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait;
use Acquia\Drupal\RecommendedSettings\Helpers\Filesystem;
use Acquia\Drupal\RecommendedSettings\Tests\FunctionalBaseTest;

/**
 * Functional test for the SiteUriTrait trait.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Drush\Traits\SiteUriTrait
 */
class SiteUriTraitTest extends FunctionalBaseTest {
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
  }

  /**
   * Test prepareSiteUri() method.
   */
  public function testPrepareSiteUri(): void {
    $site_uri = $this->prepareSiteUri("http://acquia.com/stage/acquia_cms");
    $this->assertEquals($site_uri, "acquia.com.stage.acquia_cms");

    $site_uri = $this->prepareSiteUri("http://acquia.org:8080/developer/acquia_cms");
    $this->assertEquals($site_uri, "8080.acquia.org.developer.acquia_cms");

    $site_uri = $this->prepareSiteUri("acquia_cms_low_code");
    $this->assertEquals($site_uri, "acquia_cms_low_code");

    $site_uri = $this->prepareSiteUri("https://www.acquia.com/docs");
    $this->assertEquals($site_uri, "www.acquia.com.docs");

    $site_uri = $this->prepareSiteUri("http://acquia.org");
    $this->assertEquals($site_uri, "acquia.org");

    $site_uri = $this->prepareSiteUri("acquia_cms_headless");
    $this->assertEquals($site_uri, "acquia_cms_headless");

    $site_uri = $this->prepareSiteUri("https://dev.acquiacms.com");
    $this->assertEquals($site_uri, "dev.acquiacms.com");
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
    @unlink($this->getDrupalRoot() . "/sites/sites.php");
  }

}
