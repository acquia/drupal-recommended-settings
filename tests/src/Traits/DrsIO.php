<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Traits;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An extension of \Robo\Common\IO.
 *
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
 */
trait DrsIO {

  /**
   * Stores the output.
   */
  protected OutputInterface $output;

  /**
   * Writes text to screen, without decoration.
   *
   * @param string $text
   *   The text to write.
   */
  protected function say($text): void {
    $this->writeln($text);
  }

  /**
   * Writes text to screen with big, loud decoration.
   *
   * @param string $text
   *   The text to write.
   * @param int $length
   *   The length at which text should be wrapped.
   * @param string $color
   *   The color of the text.
   */
  protected function yell($text, $length = 40, $color = 'green'): void {
    $format = "<fg=white;bg=$color;options=bold>%s</fg=white;bg=$color;options=bold>";
    $this->formattedOutput($text, $length, $format);
  }

  /**
   * Format text as a question.
   *
   * @param string $message
   *   The question text.
   *
   * @return string
   *   The formatted question text.
   */
  protected function formatQuestion($message): string {
    return "<question> $message</question> ";
  }

  /**
   * Asks a required question.
   *
   * @param string $text
   *   Prints the output text.
   */
  protected function writeln($text): void {
    $this->getOutput()->writeln($text);
  }

  /**
   * Formats the output message to terminal.
   *
   * @param string $text
   *   Output message to format.
   * @param int $length
   *   Length of the output.
   * @param string $format
   *   Given string format.
   */
  protected function formattedOutput($text, $length, $format): void {
    $lines = explode("\n", trim($text, "\n"));
    $maxLineLength = array_reduce(array_map('strlen', $lines), 'max');
    $length = max($length, $maxLineLength);
    $len = $length + 2;
    $space = str_repeat(' ', $len);
    $this->writeln(sprintf($format, $space));
    foreach ($lines as $line) {
      $line = str_pad($line, $length, ' ', STR_PAD_BOTH);
      $this->writeln(sprintf($format, " $line "));
    }
    $this->writeln(sprintf($format, $space));
  }

  /**
   *
   * @return $this
   *
   * @see \Robo\Contract\OutputAwareInterface::setOutput()
   */
  public function setOutput(OutputInterface $output) {
    $this->output = $output;

    return $this;
  }

  /**
   *
   * @return $this
   *
   * @see \Robo\Contract\OutputAwareInterface::getOutput()
   */
  public function getOutput(): OutputInterface {
    if (!isset($this->output)) {
      $this->setOutput(new NullOutput());
    }
    return $this->output;
  }

}
