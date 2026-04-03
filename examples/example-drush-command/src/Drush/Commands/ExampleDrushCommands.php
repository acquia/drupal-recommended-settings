<?php

namespace Example\Drush\Commands;

use Acquia\Drupal\RecommendedSettings\Drush\Commands\MultisiteDrushCommands;
use Acquia\Drupal\RecommendedSettings\Drush\Commands\SettingsDrushCommands;
use Acquia\Drupal\RecommendedSettings\Event\PreSettingsFileGenerateEvent;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Robo\ResultData;

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

  /**
   * Skip settings.php generation if current environment is CI environment.
   */
  #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: SettingsDrushCommands::SETTINGS_COMMAND)]
  public function validate(): ?ResultData {
    $isCI = getenv('CI');
    if (!$isCI) {
      return NULL;
    }
    $this->io()->info("Skip settings.php generation for CI environment.");
    return new ResultData(ResultData::EXITCODE_OK);
  }

  /**
   * Alter the file operations.
   */
  #[CLI\Hook(type: HookManager::ON_EVENT, target: PreSettingsFileGenerateEvent::NAME)]
  public function alterSettingsOperations(PreSettingsFileGenerateEvent $event): void {
    $operations = $event->getOperations();

    // 1. Skip a file entirely.
    $operations['${docroot}/sites/${site}/settings/local.settings.php'] = FALSE;

    // 2. Change the source file for a copy operation.
    $operations['${docroot}/sites/${site}/settings.php'] = [
      'copy' => [
        'path' => '${drs.root}/assets/settings.php',
      ],
    ];

    // 3. Add a new custom settings and overwrite if content differs.
    $operations['${docroot}/sites/${site}/custom.settings.php'] = [
      'copy' => [
        'path'      => '${drs.root}/assets/custom.settings.php',
        'overwrite' => TRUE,
      ],
    ];

    // 4. Copy with placeholder resolution enabled.
    $operations['${docroot}/sites/${site}/placeholders.settings.php'] = [
      'copy' => [
        'path' => '${drs.root}/assets/placeholders.settings.php',
        'with-placeholder' => TRUE,
      ],
    ];

    // 5. Append content from another file and from an inline string.
    $operations['${docroot}/sites/${site}/settings.php']['append'][] = [
      'path' => '${drs.root}/assets/additional.settings.php',
    ];
    $operations['${docroot}/sites/${site}/settings.php']['append'][] = [
      'content' => "// Added by my module.\n",
    ];

    $event->setOperations($operations);
  }

}
