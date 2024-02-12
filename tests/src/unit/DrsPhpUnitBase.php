<?php

namespace Acquia\Drupal\RecommendedSettings\Tests\unit;

use PHPUnit\Framework\TestCase;

class DrsPhpUnitBase extends TestCase {

  /**
   * Drs Class object.
   *
   * @var object
   */
  public Object $classObj;

  /**
   * Setting the class object.
   *
   * @param string $classObj
   *   Class Object.
   */
  public function setClass(Object $classObj): void {
    $this->classObj = $classObj;
  }

  /**
   * Getting the class object.
   *
   * @return object
   *   Class object.
   */
  public function getClass(): Object {
    return $this->classObj;
  }

  /**
   * Returns the class ReflectionMethod object.
   *
   * @throws \ReflectionException
   */
  protected function getClassMethod(string $method_name): \ReflectionMethod {
    $class = new \ReflectionClass($this->getClass());
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);

    return $method;
  }

}
