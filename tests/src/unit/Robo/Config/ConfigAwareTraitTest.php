<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Robo\Config;

use Acquia\Drupal\RecommendedSettings\Config\DefaultDrushConfig;
use Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait;
use Drush\Config\DrushConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the ConfigAwareTrait trait.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Robo\Config\ConfigAwareTrait
 */
class ConfigAwareTraitTest extends TestCase {

  use ConfigAwareTrait;

  /**
   * Tests the getConfigValue() for ConfigAwareTrait trait.
   */
  public function testGetConfigValue(): void {
    $this->config = new DrushConfig();
    // Tests that default value for key is always set to /bin.
    $this->assertEquals(
      "/bin",
      $this->getConfigValue("composer.bin"),
    );
    $drush_config = new DrushConfig();
    $drush_config->set('drush.uri', '/var/www/html/acms.prod/vendor/bin');
    $drush_config->set("runtime.project", "/var/www/html/acms.prod");
    $drush_config->set("options.root", "/var/www/html/acms.prod/docroot");
    $drush_config->set("drush.vendor-dir", $drush_config->get("runtime.project") . "/vendor");
    $drush_config->set("options.root", "/var/www/html/acms.prod");
    $default_drush_config = new DefaultDrushConfig($drush_config);
    $this->setConfig($default_drush_config);
    $this->assertEquals("/var/www/html/acms.prod", $this->getConfigValue("repo.root"));
  }

}
