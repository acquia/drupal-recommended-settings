<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\unit\Common;

use Acquia\Drupal\RecommendedSettings\Common\IO;
use Acquia\Drupal\RecommendedSettings\Tests\Traits\DrsIO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Unit test for the IO class.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\IO
 */
class IOTest extends TestCase {
  use IO, DrsIO;

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
   * Stores the expected answer.
   */
  private string $answer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->print = [];
    $this->answer = "";
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
   * Tests the print() method.
   */
  public function testPrint(): void {
    $this->print("This is test success message.");
    $this->assertEquals(
     " <fg=white;bg=green;options=bold>[success]</fg=white;bg=green;options=bold> This is test success message.",
      $this->print[0],
    );

    $this->print("This is test success message with yellow bg.", 'success', "yellow");
    $this->assertEquals(
      " <fg=white;bg=yellow;options=bold>[success]</fg=white;bg=yellow;options=bold> This is test success message with yellow bg.",
      $this->print[1],
    );

    $this->print("This is test warning message.", 'warning');
    $this->assertEquals(
      " <fg=white;bg=yellow;options=bold>[warning]</fg=white;bg=yellow;options=bold> This is test warning message.",
      $this->print[2],
    );

    $this->print("This is test error message.", 'error');
    $this->assertEquals(
      " <fg=white;bg=red;options=bold>[error]</fg=white;bg=red;options=bold> This is test error message.",
      $this->print[3],
    );

    $this->print("This is test notice message.", 'notice');
    $this->assertEquals(
      " <fg=white;bg=cyan;options=bold>[notice]</fg=white;bg=cyan;options=bold> This is test notice message.",
      $this->print[4],
    );
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
    $this->answer = "Not Well";
    $this->askChoice($question, $options, $this->answer);
    $this->assertEquals("Not Well", $this->answer);
  }

  /**
   * Tests the askRequired() method.
   */
  public function testAskRequired(): void {
    $message = "How are you ?";
    $this->answer = "Good";
    $this->askRequired($message);
    $this->assertEquals("Good", $this->answer);

    $this->expectException(\RuntimeException::class);
    $this->answer = "";
    $this->askRequired($message);

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

    // Clear print messages.
    $this->print = [];
    $this->logConfig([
      "acquia_cms_common" => [
        "version" => "1.0.0",
        "status" => "enabled",
      ],
    ], "drupal");
    $this->assertEquals([
      "<comment>Configuration for drupal:</comment>",
      $header,
      "|<info> Property </info>|<info> Value </info>|",
      $header,
      "| drupal.acquia_cms_common.version | 1.0.0 |",
      "| drupal.acquia_cms_common.status | enabled |",
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
  protected function doAsk(Question $question) {
    $validator = $question->getValidator();
    call_user_func_array($validator, [$this->answer]);
    return $this->answer;
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
