<?php

namespace Acquia\Drupal\RecommendedSettings\Helpers;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;

/**
 * Class to generate salt hash.
 */
class HashGenerator {

  /**
   * Generates the salt hash file in given directory.
   *
   * @param string $directory
   *   Given directory.
   * @param mixed $io
   *   Given io object to print message to terminal.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public static function generate(string $directory, mixed $io): void {
    try {
      $fileSystem = new Filesystem();
      $hash_salt_file = $directory . '/salt.txt';
      if (!file_exists($hash_salt_file)) {
        $io->write("Generating hash salt...");
        $fileSystem->appendToFile($hash_salt_file, RandomString::string(55));
        $io->write("<fg=white;bg=green;options=bold>[success]</> Hash salt written on <info>" . $hash_salt_file . "</info>.");
      }
      else {
        $io->write("<fg=white;bg=cyan;options=bold>[notice]</> Hash salt already exists.");
      }
    }
    catch (\Exception $e) {
      throw new SettingsException($e);
    }
  }

}
