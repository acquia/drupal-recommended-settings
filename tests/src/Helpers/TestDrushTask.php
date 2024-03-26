<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Helpers;

use Acquia\Drupal\RecommendedSettings\Robo\Tasks\DrushTask;
use Robo\ResultData;

class TestDrushTask extends DrushTask {

  /**
   * {@inheritdoc}
   */
  #[Override]
  protected function execute($process, $output_callback = NULL): ResultData {
    $command = $process->getCommandLine();
    $command_arr = explode(" ", $command);
    $process->disableOutput();
    if (in_array("--error", $command_arr)) {
      return new ResultData(ResultData::EXITCODE_ERROR, $command, []);
    }
    return new ResultData(ResultData::EXITCODE_OK, $command, []);
  }

}
