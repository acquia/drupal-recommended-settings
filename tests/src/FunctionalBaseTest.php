<?php

namespace Acquia\Drupal\RecommendedSettings\Tests;

use PHPUnit\Framework\TestCase;

abstract class FunctionalBaseTest extends TestCase {

  protected function getFixtureDirectory(): string {
    return realpath(__DIR__ . "/../fixtures");
  }

  protected function getReflectionClass(string $class_name): \ReflectionClass {
    return new \ReflectionClass($class_name);
  }

  protected function getReflectionMethod(\ReflectionClass $class, string $method): \ReflectionMethod {
    $method = $class->getMethod($method);
    $method->setAccessible(TRUE);
    return $method;
  }

  protected function getReflectionProperty(\ReflectionClass $class, string $property): \ReflectionProperty {
    $property = $class->getProperty($property);
    $property->setAccessible(TRUE);
    return $property;
  }

}
