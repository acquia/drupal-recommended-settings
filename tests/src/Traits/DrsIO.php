<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Traits;

use Robo\Common\InputAwareTrait;

/**
 * An extension of \Robo\Common\IO.
 */
trait DrsIO {

  use OutputAwareTrait;
  use InputAwareTrait;

  /**
   * Writes the text to screen.
   *
   * @param string|iterable[string] $text
   *   Prints the output text.
   */
  protected function writeln(string|iterable $text): void {
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
   *
   * @see \Robo\Common\IO::formattedOutput()
   */
  protected function formattedOutput(string $text, int $length, string $format): void {
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

}
