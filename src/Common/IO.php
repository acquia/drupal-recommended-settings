<?php

namespace Acquia\Drupal\RecommendedSettings\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * An extension of \Robo\Common\IO.
 *
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
 */
trait IO {

  /**
   * Writes text to screen, without decoration.
   *
   * @param string|iterable[string] $text
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
   * Prints the message to terminal.
   *
   * @param string $message
   *   Given message to print.
   * @param string $type
   *   Type of message. Ex: error, success, info etc.
   * @param string $color
   *   Given background color.
   */
  protected function print(string $message, string $type = "success", string $color = ""): void {
    if (!$color) {
      $color = match ($type) {
        "warning" => "yellow",
        "notice" => "cyan",
        "error" => "red",
        default => "green",
      };
    }
    $message = " <fg=white;bg=$color;options=bold>[$type]</fg=white;bg=$color;options=bold> " . $message;
    $this->say($message);
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
   * Asks the user a multiple-choice question.
   *
   * @param string $question
   *   The question text.
   * @param string[] $options
   *   An array of available options.
   * @param mixed $default
   *   Default.
   *
   * @return string
   *   The chosen option.
   */
  protected function askChoice(string $question, array $options, mixed $default = NULL): string {
    return $this->doAsk(new ChoiceQuestion($this->formatQuestion($question),
      $options, $default));
  }

  /**
   * Asks a required question.
   *
   * @param string $message
   *   The question text.
   *
   * @return string
   *   The response.
   */
  protected function askRequired(string $message): string {
    $question = new Question($this->formatQuestion($message));
    $question->setValidator(function ($answer) {
      if (empty($answer)) {
        throw new \RuntimeException(
          'You must enter a value!'
        );
      }

      return $answer;
    });
    return $this->doAsk($question);
  }

  /**
   * Writes an array to the screen as a formatted table.
   *
   * @param string[] $array
   *   The unformatted array.
   * @param string[] $headers
   *   The headers for the array. Defaults to ['Property','Value'].
   */
  protected function printArrayAsTable(
    array $array,
    array $headers = ['Property', 'Value']
  ): void {
    $table = new Table($this->output);
    $table->setHeaders($headers)
      ->setRows(ArrayManipulator::convertArrayToFlatTextArray($array))
      ->render();
  }

  /**
   * Writes a particular configuration key's value to the log.
   *
   * @param string[] $array
   *   The configuration.
   * @param string $prefix
   *   A prefix to add to each row in the configuration.
   * @param int $verbosity
   *   The verbosity level at which to display the logged message.
   */
  protected function logConfig(array $array, string $prefix = '', int $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE): void {
    // We can't directly use $this->output() as it throws error with PHPUnit 10
    // because it defines output() method with a different method signature.
    $output = ($this->output() instanceof OutputInterface) ? $this->output() : $this->getOutput();
    if ($output->getVerbosity() >= $verbosity) {
      if ($prefix) {
        $output->writeln("<comment>Configuration for $prefix:</comment>");
        foreach ($array as $key => $value) {
          $array["$prefix.$key"] = $value;
          unset($array[$key]);
        }
      }
      $this->printArrayAsTable($array);
    }
  }

}
