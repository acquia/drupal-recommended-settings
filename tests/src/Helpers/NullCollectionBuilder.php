<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Helpers;

use Robo\Collection\CollectionBuilder;
use Robo\ResultData;

/**
 * Helper class to hide/update messages when tests are running.
 */
class NullCollectionBuilder extends CollectionBuilder {

  /**
   * {@inheritdoc}
   */
  #[Override]
  public static function create($container, $commandFile) {
    // Copied from parent class.
    $builder = new static($commandFile);
    $builder->setLogger($container->get('logger'));
    $builder->setProgressIndicator($container->get('progressIndicator'));
    $builder->setConfig($container->get('config'));
    $builder->setOutputAdapter($container->get('outputAdapter'));
    return $builder;
  }

  /**
   * {@inheritdoc}
   */
  #[Override]
  public function run() {
    $command = $this->getCommand();
    $command_arr = explode(" ", $command);
    if (in_array("--error", $command_arr)) {
      return new ResultData(ResultData::EXITCODE_ERROR, $command, []);
    }
    return new ResultData(ResultData::EXITCODE_OK, $command, []);
  }

}
