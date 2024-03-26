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
    $this->assertEquals(
      "/var/www/html/acms.prod/vendor",
      $this->getConfigValue("composer.bin", "/var/www/html/acms.prod/vendor"),
    );
    $config = new DrushConfig();
    $config->set("runtime.project", "/var/www/html/acms.prod");
    $config->set("options.root", "/var/www/html/acms.prod/docroot");
    $drush_config = new DefaultDrushConfig($config);
    $this->setConfig($drush_config);
    $this->assertEquals("/var/www/html/acms.prod", $this->getConfigValue("repo.root"));
  }

}
