<?php

namespace Acquia\Drupal\RecommendedSettings\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base functional PHPUnit class.
 */
abstract class FunctionalTestBase extends TestCase {

  /**
   * An array of fixture files to create.
   *
   * @var array<string>
   */
  private array $fixtureFiles = [];

  /**
   * Copies fixture files in project directory.
   *
   * @param string $base_fixture_dir
   *   Given base fixture directory.
   * @param string $root_fixture_dir
   *   Given project or root fixture directory.
   */
  protected function copyFixtureFiles(string $base_fixture_dir, string $root_fixture_dir): void {
    $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base_fixture_dir));

    /** @var \SplFileInfo $file */
    foreach ($rii as $file) {
      if ($file->isDir()) {
        continue;
      }

      $file_path = str_replace($base_fixture_dir . "/project", "", $file->getRealPath());
      if (!file_exists($root_fixture_dir . $file_path)) {
        $root_base_dir = dirname($root_fixture_dir . $file_path);
        $dir_exist = FALSE;
        if (mkdir($root_base_dir, 0777, 'TRUE')) {
          $dir_exist = TRUE;
        }
        if (copy($file->getRealPath(), $root_fixture_dir . $file_path)) {
          $this->fixtureFiles[] = $root_fixture_dir . $file_path;
        }
        if ($dir_exist) {
          $this->fixtureFiles[] = $root_base_dir;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    foreach ($this->fixtureFiles as $file) {
      if (is_dir($file)) {
        @rmdir($file);
      }
      else {
        @unlink($file);
      }
    }
  }

  /**
   * Returs the fixture directory path.
   */
  protected function getFixtureDirectory(): string {
    return realpath(__DIR__ . "/../fixtures");
  }

  /**
   * Returns the project directory.
   */
  protected function getProjectRoot(): string {
    static $files_copied = FALSE;
    $orca_fixture_dir = getenv("ORCA_FIXTURE_DIR");
    if ($orca_fixture_dir) {
      // The ORCA_FIXTURE_DIR doesn't returns the realpath to the directory.
      // Want to ensure it does, so that PHPUnit tests doesn't have to do this.
      $orca_fixture_dir = realpath($orca_fixture_dir);
    }
    $base_fixture_dir = $this->getFixtureDirectory();
    if ($orca_fixture_dir) {
      if ($files_copied) {
        return $orca_fixture_dir;
      }
      // This code specifically needed in CI because we want to run tests by
      // copying fixture files in actual drupal project where our plugin
      // searches for this files to present.
      $this->copyFixtureFiles($base_fixture_dir, $orca_fixture_dir);
      $files_copied = TRUE;
      return $orca_fixture_dir;
    }
    return $base_fixture_dir . "/project";
  }

  /**
   * Returns the drupal webroot directory.
   */
  protected function getDrupalRoot(): string {
    static $root_dir = "";
    if ($root_dir) {
      return $root_dir;
    }
    $project_dir = $this->getProjectRoot();

    // We check for different directory to check where drupal root directory
    // exists. Though we can directory check for docroot directory as DRS uses
    // it, but for safer side checking for web as well, in case tests runs on
    // drupal/drupal-recommended project.
    if (is_dir($project_dir . "/docroot")) {
      $root_dir = $project_dir . "/docroot";
    }
    elseif (is_dir($project_dir . "/web")) {
      $root_dir = $project_dir . "/web";
    }
    else {
      $root_dir = $project_dir;
    }
    return $root_dir;
  }

  /**
   * Returns an object of ReflectionClass.
   *
   * @param string $class_name
   *   Given class name.
   *
   * @throws \ReflectionException
   */
  protected function getReflectionClass(string $class_name): \ReflectionClass {
    return new \ReflectionClass($class_name);
  }

  /**
   * Returns an object of ReflectionMethod.
   *
   * @param string $class
   *   Given class name.
   * @param string $method
   *   Given method name.
   *
   * @throws \ReflectionException
   */
  protected function getReflectionMethod(string $class, string $method): \ReflectionMethod {
    $class = $this->getReflectionClass($class);
    $method = $class->getMethod($method);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * Returns an object of ReflectionProperty.
   *
   * @param string $class
   *   Given class name.
   * @param string $property
   *   Given propert name.
   *
   * @throws \ReflectionException
   */
  protected function getReflectionProperty(string $class, string $property): \ReflectionProperty {
    $class = $this->getReflectionClass($class);
    $property = $class->getProperty($property);
    $property->setAccessible(TRUE);
    return $property;
  }

}
