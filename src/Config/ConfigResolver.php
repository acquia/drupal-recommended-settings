<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\ConfigInterface;
use Grasmash\YamlExpander\YamlExpander;
use Psr\Log\NullLogger;

/**
 * ConfigResolver class for processing variables in configuration data.
 *
 * Uses YamlExpander to perform variable replacement in configuration data.
 *
 * @internal
 */
final class ConfigResolver {

  /**
   * Holds the YamlExpander class object.
   */
  private YamlExpander $expander;

  /**
   * ConfigProcessor Constructor.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   *   The configuration object to be used for variable replacement.
   */
  public function __construct(private readonly ConfigInterface $config) {
    $this->expander = new YamlExpander(new NullLogger());
  }

  /**
   * Resolves the configuration data from various sources.
   *
   * @param array|string $data
   *   The data to process. Can be:
   *   - Array: Processed recursively
   *   - String: Processed for variable expansion and boolean conversion.
   */
  public function resolve(array|string $data): array|string {
    if (is_string($data)) {
      return $this->resolveString($data);
    }

    return $this->resolveArray($data);
  }

  /**
   * Process an array recursively.
   *
   * @param array $data
   *   The data array to process.
   *
   * @return array
   *   The processed data array with variables replaced.
   */
  private function resolveArray(array $data): array {
    $output = [];
    foreach ($data as $key => $value) {
      assert(is_scalar($key));
      $expandedKey = $this->expandKey($key);
      $expandedValue = $this->expandValue($value);
      $output[$expandedKey] = $expandedValue;
    }
    return $output;
  }

  /**
   * Process a string value.
   *
   * @param string $data
   *   The string to process.
   *
   * @return string
   *   The processed string with variables replaced.
   */
  private function resolveString(string $data): string {
    $value = $this->expandString($data);
    return is_string($value) ? $this->convertBooleanString($value) : $value;
  }

  /**
   * Expand a key if it contains variable placeholders.
   *
   * @param mixed $key
   *   The key to expand.
   *
   * @return mixed
   *   The expanded key.
   */
  private function expandKey(mixed $key): mixed {
    if (!is_string($key) || !str_contains($key, '$')) {
      return $key;
    }
    return $this->expandString($key);
  }

  /**
   * Expand a value recursively or perform variable replacement.
   *
   * @param mixed $value
   *   The value to expand.
   *
   * @return mixed
   *   The expanded value.
   */
  private function expandValue(mixed $value): mixed {
    if (is_array($value)) {
      return $this->resolveArray($value);
    }

    if (is_string($value) && str_contains($value, '$')) {
      $value = $this->expandString($value);
    }

    return is_string($value) ? $this->convertBooleanString($value) : $value;
  }

  /**
   * Expand a string value using YamlExpander.
   *
   * @param string $value
   *   The string to expand.
   *
   * @return mixed
   *   The expanded value.
   */
  private function expandString(string $value): mixed {
    $expanded = $this->expander->expandArrayProperties([$value], $this->config->export());
    return reset($expanded);
  }

  /**
   * Convert boolean string representations to actual boolean values.
   *
   * @param string $value
   *   The value to convert.
   */
  private function convertBooleanString(string $value): string|bool {
    return match (strtolower($value)) {
      'false' => FALSE,
      'true' => TRUE,
      default => $value,
    };
  }

}
