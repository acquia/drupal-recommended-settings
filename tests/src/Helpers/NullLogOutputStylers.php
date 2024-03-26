<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Helpers;

use Consolidation\Log\SymfonyLogOutputStyler;

/**
 * Class for not logging any error.
 */
class NullLogOutputStylers extends SymfonyLogOutputStyler {

  /**
   * @inheritdoc
   */
  #[Override]
  public function error($symfonyStyle, $level, $message, $context): void {
  }

}
