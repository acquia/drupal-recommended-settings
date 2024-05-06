<?php

namespace Acquia\Drupal\RecommendedSettings\Common;

use Consolidation\AnnotatedCommand\State\State;
use Robo\Common\InputAwareTrait;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

trait IO {
  use InputAwareTrait;
  use OutputAwareTrait;

  protected SymfonyStyle $io;

  public function currentState() {
    return new class($this, $this->input, $this->output, $this->io) implements State {
      protected $obj;
      protected $input;
      protected $output;
      protected $io;

      public function __construct($obj, $input, $output, $io) {
        $this->obj = $obj;
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
      }

      public function restore(): void {
        $this->obj->restoreState($this->input, $this->output, $this->io);
      }

    };
  }

  // This should typically only be called by State::restore()
  public function restoreState(InputInterface $input = NULL, OutputInterface $output = NULL, SymfonyStyle $io = NULL) {
    $this->setInput($input);
    $this->setOutput($output);
    $this->io = $io;

    return $this;
  }

  public function setInput(InputInterface $input) {
    if ($input != $this->input) {
      $this->io = NULL;
    }
    $this->input = $input;

    return $this;
  }

  public function setOutput(OutputInterface $output) {
    if ($output != $this->output) {
      $this->io = NULL;
    }
    $this->output = $output;

    return $this;
  }

  /**
   * Provide access to SymfonyStyle object.
   *
   * @deprecated Use a style injector instead
   *
   *
   * @see https://symfony.com/blog/new-in-symfony-2-8-console-style-guide
   */
  protected function io(): \Symfony\Component\Console\Style\SymfonyStyle {
    if (!$this->io) {
      $this->io = new ConsoleIO($this->input(), $this->getOutput());
    }
    return $this->io;
  }

  /**
   *
   */
  protected function decorationCharacter(string $nonDecorated, string $decorated): string {
    if (!$this->getOutput()->isDecorated() || (strncasecmp(PHP_OS, 'WIN', 3) == 0)) {
      return $nonDecorated;
    }
    return $decorated;
  }

  /**
   * Writes text to screen, without decoration.
   *
   * @param string $text
   *   The text to write.
   */
  protected function say(string $text): void {
    $this->writeln("$text");
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
  protected function yell(string $text, int $length = 40, string $color = 'green'): void {
    $format = "<fg=white;bg=$color;options=bold>%s</fg=white;bg=$color;options=bold>";
    $this->formattedOutput($text, $length, $format);
  }

  /**
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

  /**
   *
   */
  protected function ask(string $question, bool $hideAnswer = FALSE): string {
    if ($hideAnswer) {
      return $this->askHidden($question);
    }
    return $this->doAsk(new Question($this->formatQuestion($question)));
  }

  /**
   *
   */
  protected function askHidden(string $question): string {
    $question = new Question($this->formatQuestion($question));
    $question->setHidden(TRUE);
    return $this->doAsk($question);
  }

  /**
   *
   */
  protected function askDefault(string $question, string $default): string {
    return $this->doAsk(new Question($this->formatQuestion("$question [$default]"), $default));
  }

  /**
   *
   */
  protected function confirm(string $question, bool $default = FALSE): string {
    return $this->doAsk(new ConfirmationQuestion($this->formatQuestion($question . ' (y/n)'), $default));
  }

  /**
   *
   */
  protected function doAsk(Question $question): string {
    return $this->getDialog()->ask($this->input(), $this->getOutput(), $question);
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
  protected function formatQuestion(string $message): string {
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
    if ($this->getOutput()->getVerbosity() >= $verbosity) {
      if ($prefix) {
        $this->getOutput()->writeln("<comment>Configuration for $prefix:</comment>");
        foreach ($array as $key => $value) {
          $array["$prefix.$key"] = $value;
          unset($array[$key]);
        }
      }
      $this->printArrayAsTable($array);
    }
  }

  /**
   */
  protected function getDialog(): \Symfony\Component\Console\Helper\QuestionHelper {
    return new QuestionHelper();
  }

  /**
   * @param $text
   */
  protected function writeln($text): void {
    $this->getOutput()->writeln($text);
  }

}
