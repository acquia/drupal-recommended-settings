<?php

namespace Acquia\Drupal\RecommendedSettings\Helpers;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;

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
   */
  public static function generate(string $directory, $io): void {
    try {
      $fileSystem = new Filesystem();
      $hash_salt_file = $directory . '/salt.txt';
      if (!file_exists($hash_salt_file)) {
        $io->write("Generating hash salt...");
        $fileSystem->appendToFile($hash_salt_file, RandomString::string(55));
        $io->write("Hash salt written on <info>" . $hash_salt_file . "</info>.");
      }
      else {
        $io->write("<comment>Hash salt already exists.</comment>");
      }
    }
    catch (\RuntimeException $e) {
      $io->write("<error>" . $e->getMessage() . "</error>");
    }
  }

}
