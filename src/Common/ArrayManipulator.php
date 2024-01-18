<?php

namespace Acquia\Drupal\RecommendedSettings\Common;

use Dflydev\DotAccessData\Data;

/**
 * Utility class for manipulating arrays.
 */
class ArrayManipulator {

  /**
   * Merges arrays recursively while preserving.
   *
   * @param string[] $array1
   *   The first array.
   * @param string[] $array2
   *   The second array.
   *
   * @return string[]
   *   The merged array.
   *
   * @see http://php.net/manual/en/function.array-merge-recursive.php#92195
   */
  public static function arrayMergeRecursiveDistinct(
        array &$array1,
        array &$array2
    ): array {
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key],
              $value);
      }
      else {
        $merged[$key] = $value;
      }
    }
    return $merged;
  }

  /**
   * Converts dot-notated keys to proper associative nested keys.
   *
   * E.g., [drush.alias => 'self'] would be expanded to
   * ['drush' => ['alias' => 'self']]
   *
   * @param string[] $array
   *   The array containing unexpanded dot-notated keys.
   *
   * @return string[]
   *   The expanded array.
   */
  public static function expandFromDotNotatedKeys(array $array): array {
    $data = new Data();

    // @todo Make this work at all levels of array.
    foreach ($array as $key => $value) {
      $data->set($key, $value);
    }

    return $data->export();
  }

  /**
   * Flattens a multidimensional array to a flat array with dot-notated keys.
   *
   * This is the inverse of expandFromDotNotatedKeys(), e.g.,
   * ['drush' => ['alias' => 'self']] would be flattened to
   * [drush.alias => 'self'].
   *
   * @param string[] $array
   *   The multidimensional array.
   *
   * @return string[]
   *   The flattened array.
   */
  public static function flattenToDotNotatedKeys(array $array): array {
    return self::flattenMultidimensionalArray($array, '.');
  }

  /**
   * Flattens a multidimensional array to a flat array, using custom glue.
   *
   * This is the inverse of expandFromDotNotatedKeys(), e.g.,
   * ['drush' => ['alias' => 'self']] would be flattened to
   * [drush.alias => 'self'].
   *
   * @param string[] $array
   *   The multidimensional array.
   * @param string $glue
   *   The character(s) to use for imploding keys.
   *
   * @return string[]
   *   The flattened array.
   */
  public static function flattenMultidimensionalArray(array $array, string $glue): array {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
    $result = [];
    foreach ($iterator as $leafValue) {
      $keys = [];
      foreach (range(0, $iterator->getDepth()) as $depth) {
        $keys[] = $iterator->getSubIterator($depth)->key();
      }
      $result[implode($glue, $keys)] = $leafValue;
    }

    return $result;
  }

  /**
   * Converts a multi-dimensional array to a human-readable flat array.
   *
   * Used primarily for rendering tables via Symfony Console commands.
   *
   * @param string[] $array
   *   The multi-dimensional array.
   *
   * @return string[]
   *   The human-readable, flat array.
   */
  public static function convertArrayToFlatTextArray(array $array): array {
    $rows = [];
    $max_line_length = 60;
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $flattened_array = self::flattenToDotNotatedKeys($value);
        foreach ($flattened_array as $sub_key => $sub_value) {
          if ($sub_value === TRUE) {
            $sub_value = 'true';
          }
          elseif ($sub_value === FALSE) {
            $sub_value = 'false';
          }
          $rows[] = [
            "$key.$sub_key",
            wordwrap($sub_value, $max_line_length, "\n", TRUE),
          ];
        }
      }
      else {
        if ($value === TRUE) {
          $contents = 'true';
        }
        elseif ($value === FALSE) {
          $contents = 'false';
        }
        else {
          $contents = wordwrap($value, $max_line_length, "\n", TRUE);
        }
        $rows[] = [$key, $contents];
      }
    }

    return $rows;
  }

}
