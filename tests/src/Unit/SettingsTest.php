<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Config\DefaultConfig;
use Acquia\Drupal\RecommendedSettings\Event\PreSettingsFileGenerateEvent;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Settings;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SettingsTest extends TestCase {

  /**
   * The recommended settings object.
   */
  protected Settings $settings;

  /**
   * The path to the project root directory.
   */
  protected string $projectRoot;

  /**
   * The symfony file-system object.
   */
  protected Filesystem $fileSystem;

  /**
   * Set up test environment.
   */
  public function setUp(): void {
    $this->projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . RandomString::string(5, TRUE, NULL, 'abcdefghijklmnopqrstuvwxyz');
    $docroot = $this->projectRoot . '/docroot';
    mkdir($docroot . '/sites/default', 0777, TRUE);
    $this->fileSystem = new Filesystem();
    $this->fileSystem->dumpFile($docroot . '/sites/default/default.settings.php', "<?php");
    $this->fileSystem->dumpFile($this->projectRoot . '/composer.json', '{}');
  }

  /**
   * Test that the file is created.
   */
  public function testFileIsCreated(): void {
    $docroot = $this->projectRoot . '/docroot';
    $config = new DefaultConfig($docroot);
    $settings = new Settings();
    $this->fileSystem->chmod($docroot . '/sites/default/default.settings.php', 0777);
    $this->fileSystem->chmod($docroot . "/sites/default", 0655);
    $settings->setConfig($config);
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);
    // Assert that settings/default.global.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->projectRoot . '/docroot/sites/settings/default.global.settings.php'));
    // Assert that settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->projectRoot . '/docroot/sites/default/settings.php'));
    // Assert that settings.php file has content.
    $content = <<<CONTENT
<?php
require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";
/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `acquia-recommended.settings.php`. See Acquia's documentation for more detail.
 *
 * @link https://docs.acquia.com/
 */

CONTENT;
    $this->assertEquals($content, file_get_contents($this->projectRoot . '/docroot/sites/default/settings.php'));

    // Assert that default.includes.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->projectRoot . '/docroot/sites/default/settings/default.includes.settings.php'));
    // Assert that default.local.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->projectRoot . '/docroot/sites/default/settings/default.local.settings.php'));
    // Assert that local.settings.php file exist.
    $this->assertTrue($this->fileSystem->exists($this->projectRoot . '/docroot/sites/default/settings/local.settings.php'));
    // Get the local.settings.php file content.
    $localSettings = file_get_contents($this->projectRoot . '/docroot/sites/default/settings/local.settings.php');
    // Verify database credentials.
    $this->assertStringContainsString("db_name = 'drs'", $localSettings, "The local.settings.php doesn't contains the 'drs' database.");
    $this->assertStringContainsString("'username' => 'drupal'", $localSettings, "The local.settings.php doesn't contains the 'drupal' username.");
    $this->assertStringContainsString("'password' => 'drupal'", $localSettings, "The local.settings.php doesn't contains the 'drupal' password.");
    $this->assertStringContainsString("'host' => 'localhost'", $localSettings, "The local.settings.php doesn't contains the 'localhost' host.");
    $this->assertStringContainsString("'port' => '3306'", $localSettings, "The local.settings.php doesn't contains the '3306' port.");
  }

  public function testExpectSettingsException(): void {
    $docroot = $this->projectRoot . '/docroot';
    $config = new DefaultConfig($docroot);
    $settings = new Settings();
    $this->expectException(SettingsException::class);
    $this->expectExceptionMessage(
      sprintf('Failed to copy "%s" because file does not exist.', "$docroot/sites/default/default.settings.php"),
    );
    unlink($docroot . "/sites/default/default.settings.php");
    $settings->setConfig($config);
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);
  }

  /**
   * Test that the deprecation message is triggered.
   *
   * @ignoreDeprecations
   */
  #[IgnoreDeprecations]
  public function testTriggerDeprecationMessage() {
    set_error_handler(function ($errno, $errstr) {
      $this->assertSame("Since acquia/drupal-recommended-settings:1.1.3: Creating an object by passing (\$drupal_root, \$site) arguments is deprecated and will cause an error in 1.2.0.", $errstr);
    }, \E_USER_DEPRECATED);
    $docroot = $this->projectRoot . '/docroot';
    new Settings($docroot);
    restore_error_handler();
  }

  /**
   * Test that inline operations defined in composer.json are merged and run.
   */
  public function testCombineProjectOperationsWithInlineOperations(): void {
    $docroot = $this->projectRoot . '/docroot';
    $extraDest = $docroot . '/sites/default/settings/extra.settings.php';

    $this->fileSystem->dumpFile(
      $this->projectRoot . '/composer.json',
      json_encode([
        'extra' => [
          'drupal-recommended-settings' => [
            'operations' => [
              $extraDest => $docroot . '/sites/default/default.settings.php',
            ],
          ],
        ],
      ])
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);

    $this->assertTrue($this->fileSystem->exists($extraDest));
  }

  /**
   * Test that an operations-file path is loaded when operations key is absent.
   */
  public function testCombineProjectOperationsWithOperationsFile(): void {
    $docroot = $this->projectRoot . '/docroot';
    $extraDest = $docroot . '/sites/default/settings/extra-from-file.settings.php';
    $relativeOperationsFile = 'custom-operations.json';
    $absoluteOperationsFile = $this->projectRoot . '/' . $relativeOperationsFile;

    $this->fileSystem->dumpFile(
      $absoluteOperationsFile,
      json_encode([$extraDest => $docroot . '/sites/default/default.settings.php'])
    );
    $this->fileSystem->dumpFile(
      $this->projectRoot . '/composer.json',
      json_encode([
        'extra' => [
          'drupal-recommended-settings' => [
            'operations-file' => $relativeOperationsFile,
          ],
        ],
      ])
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);

    $this->assertTrue($this->fileSystem->exists($extraDest));
  }

  /**
   * Test non-empty inline operations causes operations-file to be ignored.
   */
  public function testCombineProjectOperationsInlineOperationsTakePrecedenceOverFile(): void {
    $docroot = $this->projectRoot . '/docroot';
    $inlineDest = $docroot . '/sites/default/settings/inline-extra.settings.php';
    $fileDest = $docroot . '/sites/default/settings/file-extra.settings.php';
    $relativeOperationsFile = 'custom-operations.json';
    $absoluteOperationsFile = $this->projectRoot . '/' . $relativeOperationsFile;

    $this->fileSystem->dumpFile(
      $absoluteOperationsFile,
      json_encode([$fileDest => $docroot . '/sites/default/default.settings.php'])
    );
    $this->fileSystem->dumpFile(
      $this->projectRoot . '/composer.json',
      json_encode([
        'extra' => [
          'drupal-recommended-settings' => [
            'operations' => [
              $inlineDest => $docroot . '/sites/default/default.settings.php',
            ],
            'operations-file' => $relativeOperationsFile,
          ],
        ],
      ])
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);

    $this->assertTrue($this->fileSystem->exists($inlineDest));
    $this->assertFalse($this->fileSystem->exists($fileDest));
  }

  /**
   * Test that SettingsException is thrown when operations-file does not exist.
   */
  public function testCombineProjectOperationsThrowsForMissingOperationsFile(): void {
    $docroot = $this->projectRoot . '/docroot';
    $relativeOperationsFile = 'nonexistent-operations.json';

    $this->fileSystem->dumpFile(
      $this->projectRoot . '/composer.json',
      json_encode([
        'extra' => [
          'drupal-recommended-settings' => [
            'operations-file' => $relativeOperationsFile,
          ],
        ],
      ])
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));

    $this->expectException(SettingsException::class);
    $this->expectExceptionMessageMatches('/nonexistent-operations\.json/');
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);
  }

  /**
   * Test that a custom event handler can modify the operations list.
   */
  public function testPrepareOperationsHandlerModifiesOperations(): void {
    $docroot = $this->projectRoot . '/docroot';
    $extraDest = $docroot . '/sites/default/settings/handler-added.settings.php';
    $source = $docroot . '/sites/default/default.settings.php';

    $hookManager = new HookManager();
    $hookManager->add(
      function (PreSettingsFileGenerateEvent $event) use ($extraDest, $source): void {
        $ops = $event->getOperations();
        $ops[$extraDest] = $source;
        $event->setOperations($ops);
      },
      HookManager::ON_EVENT,
      PreSettingsFileGenerateEvent::NAME
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));
    $settings->setHookManager($hookManager);
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);

    $this->assertTrue($this->fileSystem->exists($extraDest));
  }

  /**
   * Test that all handlers run when none stop propagation.
   */
  public function testPrepareOperationsAllHandlersCalledWithoutPropagationStop(): void {
    $docroot = $this->projectRoot . '/docroot';
    $destA = $docroot . '/sites/default/settings/handler-a.settings.php';
    $destB = $docroot . '/sites/default/settings/handler-b.settings.php';
    $source = $docroot . '/sites/default/default.settings.php';

    $hookManager = new HookManager();
    $hookManager->add(
      function (PreSettingsFileGenerateEvent $event) use ($destA, $source): void {
        $ops = $event->getOperations();
        $ops[$destA] = $source;
        $event->setOperations($ops);
      },
      HookManager::ON_EVENT,
      PreSettingsFileGenerateEvent::NAME
    );
    $hookManager->add(
      function (PreSettingsFileGenerateEvent $event) use ($destB, $source): void {
        $ops = $event->getOperations();
        $ops[$destB] = $source;
        $event->setOperations($ops);
      },
      HookManager::ON_EVENT,
      PreSettingsFileGenerateEvent::NAME
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));
    $settings->setHookManager($hookManager);
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);

    $this->assertTrue($this->fileSystem->exists($destA));
    $this->assertTrue($this->fileSystem->exists($destB));
  }

  /**
   * Test that stopping propagation skips subsequent handlers.
   */
  public function testPrepareOperationsStopPropagationSkipsSubsequentHandlers(): void {
    $docroot = $this->projectRoot . '/docroot';
    $destA = $docroot . '/sites/default/settings/propagation-a.settings.php';
    $destB = $docroot . '/sites/default/settings/propagation-b.settings.php';
    $source = $docroot . '/sites/default/default.settings.php';

    $hookManager = new HookManager();
    $hookManager->add(
      function (PreSettingsFileGenerateEvent $event) use ($destA, $source): void {
        $ops = $event->getOperations();
        $ops[$destA] = $source;
        $event->setOperations($ops);
        $event->stopPropagation();
      },
      HookManager::ON_EVENT,
      PreSettingsFileGenerateEvent::NAME
    );
    $hookManager->add(
      function (PreSettingsFileGenerateEvent $event) use ($destB, $source): void {
        $ops = $event->getOperations();
        $ops[$destB] = $source;
        $event->setOperations($ops);
      },
      HookManager::ON_EVENT,
      PreSettingsFileGenerateEvent::NAME
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));
    $settings->setHookManager($hookManager);
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);

    $this->assertTrue($this->fileSystem->exists($destA));
    $this->assertFalse($this->fileSystem->exists($destB));
  }

  /**
   * Test that SettingsException is thrown when operations have invalid JSON.
   */
  public function testCombineProjectOperationsThrowsForInvalidJsonInOperationsFile(): void {
    $docroot = $this->projectRoot . '/docroot';
    $relativeOperationsFile = 'invalid-operations.json';
    $absoluteOperationsFile = $this->projectRoot . '/' . $relativeOperationsFile;

    $this->fileSystem->dumpFile($absoluteOperationsFile, 'this is not valid json {');
    $this->fileSystem->dumpFile(
      $this->projectRoot . '/composer.json',
      json_encode([
        'extra' => [
          'drupal-recommended-settings' => [
            'operations-file' => $relativeOperationsFile,
          ],
        ],
      ])
    );

    $settings = new Settings();
    $settings->setConfig(new DefaultConfig($docroot));

    $this->expectException(SettingsException::class);
    $settings->generate([
      'drupal' => [
        'db' => [
          'database' => 'drs',
          'username' => 'drupal',
          'password' => 'drupal',
          'host' => 'localhost',
          'port' => '3306',
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->fileSystem->chmod($this->projectRoot, 0777, 0o000, TRUE);
    $this->fileSystem->remove($this->projectRoot);
  }

}
