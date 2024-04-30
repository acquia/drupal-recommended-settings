<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\Unit\Common;

use Acquia\Drupal\RecommendedSettings\Common\Executor;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Tests\CommandsTestBase;
use Acquia\Drupal\RecommendedSettings\Tests\Helpers\NullCollectionBuilder;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Robo\Common\ProcessExecutor;
use Robo\ResultData;

/**
 * Unit test for the ConfigAwareTrait trait.
 *
 * @covers \Acquia\Drupal\RecommendedSettings\Common\Executor
 */
class ExecutorTest extends CommandsTestBase {

  /**
   * An instance of executor object.
   */
  protected Executor $executor;

  /**
   * An instance of logger object.
   */
  protected LoggerInterface $mockLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->getConfig()->set("composer.bin", "/var/www/html/acms/vendor/bin");
    $this->getConfig()->set("drush.alias", "self");
    $this->getConfig()->set("drush.uri", "default");
    $this->executor = new Executor($this->builder);
    $this->executor->setConfig($this->getConfig());
    $this->mockLogger = $this->createMock(LoggerInterface::class);
    $this->executor->setLogger($this->mockLogger);
  }

  /**
   * Tests the getBuilder() method.
   */
  public function testGetBuilder(): void {
    $this->assertInstanceOf(NullCollectionBuilder::class, $this->executor->getBuilder());
  }

  /**
   * Tests the taskExec() method.
   */
  public function testTaskExec(): void {
    $tasExec = $this->executor->taskExec("ls -ltr");
    $output = $tasExec->run();
    $this->assertEquals($output->getExitCode(), ResultData::EXITCODE_OK);
    $this->assertEquals($output->getMessage(), "ls -ltr");
  }

  /**
   * Tests the drush() method.
   */
  public function testDrush(): void {
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "/var/www/html/acms/vendor/bin/drush @self cr --no-interactive",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->assertInstanceOf(ProcessExecutor::class, $this->executor->drush("cr --no-interactive"));
  }

  /**
   * Tests the drush() method passing array arguments.
   */
  public function testDrushCommandArrayArgs(): void {
    $config = $this->executor->getConfig();
    $config->set("drush.uri", "site1");
    $this->executor->setConfig($config);
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Running command /var/www/html/acms/vendor/bin/drush @self --uri=site1 cr --no-interactive",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->assertInstanceOf(ProcessExecutor::class, $this->executor->drush(["cr", "--no-interactive"]));
  }

  /**
   * Tests the killProcessByPort() method.
   */
  public function testKillProcessByPort(): void {
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Killing all processes on port '12345'...",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->assertNull($this->executor->killProcessByPort(12345));
  }

  /**
   * Tests the killProcessByName() method.
   */
  public function testKillProcessByName(): void {
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Killing all processing containing string 'some'...",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->assertNull($this->executor->killProcessByName("some"));
  }

  /**
   * Tests the waitForUrlAvailable() method.
   */
  public function testWaitForUrlAvailable(): void {
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Waiting for non-50x response from https://www.google.com...",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->assertNull($this->executor->waitForUrlAvailable("https://www.google.com"));
  }

  /**
   * Tests the wait() method.
   */
  public function testWait(): void {
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Waiting for something() to return true.",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->assertTrue($this->executor->wait([$this, "something"], [TRUE]));
  }

  /**
   * Tests the wait() method when exception is thrown.
   */
  public function testWaitTimeoutException(): void {
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Should throw exception",
        $message,
      );
    });
    $this->mockLogger->method("error")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Something went wrong",
        $message,
      );
    });
    $this->executor->setLogger($this->mockLogger);
    $this->expectException(SettingsException::class);
    $this->expectExceptionMessage("Timed out.");
    $this->assertTrue($this->executor->wait([$this, "something"], [FALSE], "Should throw exception", 10));
  }

  /**
   * Tests the checkUrl() method.
   */
  public function testCheckUrl(): void {
    $client = $this->createMock(ClientInterface::class);
    $http_response = $this->createMock(ResponseInterface::class);
    $stream_interface = $this->createMock(StreamInterface::class);
    $stream_interface->method("__toString")->willReturn("Server encountered exception.");
    $http_response->method("getStatusCode")->willReturn(500);
    $http_response->method("getBody")->willReturn($stream_interface);
    $client->method("request")->willReturn($http_response);
    $executor = new Executor($this->builder, $client);

    $this->mockLogger->method("debug")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Server encountered exception.",
        (string) $message,
      );
    });
    $this->mockLogger->method("info")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Response code: 500",
        (string) $message,
      );
    });
    $executor->setLogger($this->mockLogger);
    $this->assertFalse($executor->checkUrl("https://www.google.com"));
  }

  /**
   * Tests the checkUrl() method when exception is thrown.
   */
  public function testCheckUrlForException(): void {
    $client = $this->createMock(ClientInterface::class);
    $http_response = $this->createMock(ResponseInterface::class);
    $http_response->method("getStatusCode")->willReturn(500);
    $executor = new Executor($this->builder, $client);
    $this->mockLogger->method("debug")->willReturnCallback(function ($message): void {
      $this->assertEquals(
        "Invalid Url" . PHP_EOL . " For troubleshooting guidance and support, see https://github.com/acquia/drupal-recommended-settings",
        (string) $message,
      );
    });
    $executor->setLogger($this->mockLogger);
    $exception = new SettingsException("Invalid Url");
    $client->method("request")->willThrowException($exception);
    $this->assertFalse($executor->checkUrl("https://www.google.com"));
  }

  /**
   * @throws \Exception
   */
  public function something(bool $return): bool {
    if (!$return) {
      throw new \Exception("Something went wrong");
    }
    return $return;
  }

}
