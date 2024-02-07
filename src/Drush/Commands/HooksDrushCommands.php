<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Base hook commands.
 */
class HooksDrushCommands extends DrushCommands {

  /**
   * Generate settings for multisite if env. is local or CloudIDE & not ACSF.
   */
  #[CLI\Hook(type: HookManager::ON_EVENT, target: MultisiteDrushCommands::VALIDATE_GENERATE_SETTINGS)]
  public function isAcquiaEnvironment(): bool {
    return !EnvironmentDetector::isAcsfEnv() && (EnvironmentDetector::isLocalEnv() || EnvironmentDetector::isAhIdeEnv());
  }

}
