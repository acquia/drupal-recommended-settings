<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\unit\Common;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Unit test for the IO class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\IO
 */
// Revert changes made in this file once consolidation/robo is having some fix.
// For more details follow issue : https://github.com/consolidation/robo/issues/1155
class IOTest extends TestCase {

  // use IO, RoboIO {
  //   IO::say insteadof RoboIO;
  //   IO::formatQuestion insteadof RoboIO;
  //   IO::yell insteadof RoboIO;
  // }

  /**
   * Stores the messages to print.
   *
   * @var array<string>
   */
  private array $print;

  /**
   * Stores the question object.
   */
  private Question $question;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->markTestSkipped('must be revisited.');
    $this->print = [];
    $this->output = $this->getOutput();
  }

  /**
   * Tests the say() method.
   */
  public function testSay(): void {
    $say = "Hi! How are you ?";
    $this->say($say);
    $this->assertEquals($this->print[0], $say);
  }

  /**
   * Tests the yell() method.
   */
  public function testYell(): void {
    $yell = "WARNING";
    $this->yell($yell, 2);
    $yell_opening = "<fg=white;bg=green;options=bold>";
    $yell_closed = "</fg=white;bg=green;options=bold>";
    $this->assertEquals([
      "$yell_opening         $yell_closed",
      "$yell_opening $yell $yell_closed",
      "$yell_opening         $yell_closed",
    ], $this->print);

    // Clear the print messages.
    $this->print = [];
    $color = "red";
    $this->yell($yell, 2, $color);
    $yell_opening = "<fg=white;bg=$color;options=bold>";
    $yell_closed = "</fg=white;bg=$color;options=bold>";
    $this->assertEquals([
      "$yell_opening         $yell_closed",
      "$yell_opening $yell $yell_closed",
      "$yell_opening         $yell_closed",
    ], $this->print);
  }

  /**
   * Tests the FormatQuestion() method.
   */
  public function testFormatQuestion(): void {
    $message = "How are you ?";
    $this->assertEquals("<question> $message</question> ", $this->formatQuestion($message));
  }

  /**
   * Tests the askChoice() method.
   */
  public function testAskChoice(): void {
    $question = "How are you ?";
    $options = ["Good", "Bad", "Not Well"];
    $default = "Good";
    $answer = $this->askChoice($question, $options, $default);
    $this->assertEquals($answer, "answer");
  }

  /**
   * Tests the askRequired() method.
   */
  public function testAskRequired(): void {
    $message = "How are you ?";
    $answer = $this->askRequired($message);
    $this->assertEquals($answer, "answer");
  }

  /**
   * Tests the printArrayAsTable() method.
   */
  public function testPrintArrayAsTable(): void {
    $input = [
      "acquia_cms_common" => [
        "version" => "1.0.0",
        "status" => "enabled",
      ],
      "acquia_connector" => [
        "version" => "4.0.0",
        "status" => "enabled",
      ],
    ];
    $this->printArrayAsTable($input);

    $header = "+--+--+";
    $this->assertEquals([
      $header,
      "|<info> Property </info>|<info> Value </info>|",
      $header,
      "| acquia_cms_common.version | 1.0.0 |",
      "| acquia_cms_common.status | enabled |",
      "| acquia_connector.version | 4.0.0 |",
      "| acquia_connector.status | enabled |",
      $header,
    ], $this->print);

    // Clear the print messages.
    $this->print = [];
    $this->printArrayAsTable($input, ["Modules", "Property"]);
    $this->assertEquals([
      $header,
      "|<info> Modules </info>|<info> Property </info>|",
      $header,
      "| acquia_cms_common.version | 1.0.0 |",
      "| acquia_cms_common.status | enabled |",
      "| acquia_connector.version | 4.0.0 |",
      "| acquia_connector.status | enabled |",
      $header,
    ], $this->print);
  }

  /**
   * Tests the logConfig() method.
   */
  public function testLogConfig(): void {
    $this->logConfig([
      "acquia_cms_common" => [
        "version" => "1.0.0",
        "status" => "enabled",
      ],
    ]);
    $header = "+--+--+";
    $this->assertEquals([
      $header,
      "|<info> Property </info>|<info> Value </info>|",
      $header,
      "| acquia_cms_common.version | 1.0.0 |",
      "| acquia_cms_common.status | enabled |",
      $header,
    ], $this->print);
  }

  /**
   * Returns the mocked output object.
   */
  protected function getOutput(): OutputInterface {
    $output = $this->createMock(OutputInterface::class);
    $output->method("writeln")->willReturnCallback(fn ($input) => $this->mockPrint($input));
    $output->method("getVerbosity")->willReturn(OutputInterface::VERBOSITY_VERY_VERBOSE);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function input(): InputInterface {
    return $this->createMock(InputInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDialog() {
    $question_helper = $this->createMock(QuestionHelper::class);
    $question_helper->expects($this->any())
      ->method("ask")->willReturn("answer");
    return $question_helper;
  }

  /**
   * Helper method to store input string to property.
   */
  protected function mockPrint(string|iterable $messages): void {
    if (is_iterable($messages)) {
      foreach ($messages as $message) {
        $this->print[] = $message;
      }
    }
    else {
      $this->print[] = $messages;
    }
  }

}
