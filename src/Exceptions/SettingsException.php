<?php

namespace Acquia\Drupal\RecommendedSettings\Exceptions;

/**
 * Custom reporting and error handling for exceptions.
 *
 * @package Acquia\Drupal\RecommendedSettings\Exceptions
 */
class SettingsException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct(
        $message = "",
        $code = 0,
        \Throwable $previous = NULL
    ) {
    $message .= PHP_EOL . " For troubleshooting guidance and support, see https://github.com/acquia/drupal-recommended-settings";
    parent::__construct($message, $code, $previous);

    $this->transmitAnalytics();
  }

  /**
   * Transmit anonymous data about Exception.
   */
  protected function transmitAnalytics(): void {
    // Create new AcquiaDrupalRecommendedSettingsAnalyticsData class.
  }

}
