<?php

namespace Example\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Commands\MultisiteDrushCommands;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * An example drush command file.
 */
class ExampleDrushCommands extends DrushCommands {

  /**
   * Do not generate settings.php file to site1.
   */
  #[CLI\Hook(type: HookManager::ON_EVENT, target: MultisiteDrushCommands::VALIDATE_GENERATE_SETTINGS)]
  public function skipQuestionForSite(CommandData $commandData): bool {
    // DO NOT ask question for site: `site1`.
    if ($commandData->input()->getOption("uri") == "site1") {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Display successful message, after settings files are generated/updated.
   */
  #[CLI\Hook(type: HookManager::ON_EVENT, target: MultisiteDrushCommands::POST_GENERATE_SETTINGS)]
  public function showSuccessMessage(CommandData $commandData): void {
    $uri = $commandData->input()->getOption("uri");
    $this->io()->info("The settings.php generated successfully for site `" . $uri . "`.");
  }

}
