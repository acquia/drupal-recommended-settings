<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Settings;
use Drush\Attributes as Cli;

/**
 * The DRS Drush commands.
 */
class DrsDrushCommands extends BaseDrushCommands {

  /**
   * Generates the settings.php for given site.
   */
  #[CLI\Command(name: 'drs:init:settings')]
  public function initSite(): void {
    try {
      $settings = new Settings($this->getConfigValue("docroot"), $this->getConfigValue("drush.uri"));
      $settings->generate();
      if (!$this->output()->isQuiet()) {
        $this->print(
          sprintf("Settings generated successfully for site '%s'.", $this->getConfigValue("drush.uri"))
        );
      }
    }
    catch (SettingsException $e) {
      if (!$this->output()->isQuiet()) {
        $this->print($e->getMessage(), "error", "red");
        if ($this->output()->isVerbose()) {
          $this->io()->writeln($e->getTraceAsString());
        }
      }
    }
  }

}
