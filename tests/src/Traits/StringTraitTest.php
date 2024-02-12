<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Traits;

/**
 * Test traits to perform string operations.
 */
trait StringTraitTest {

  /**
   * Generates the random string of given length.
   *
   * @param int $length
   *   Given length of string.
   */
  protected function randomString($length = 5): string {
    return substr(
      str_shuffle(
        str_repeat(
          $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)),
        )
      ), 1, $length);
  }

}
