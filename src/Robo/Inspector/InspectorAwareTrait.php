<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Inspector;

/**
 * Adds getters and setters for $this->inspector.
 */
trait InspectorAwareTrait {

  /**
   * The inspector.
   */
  private Inspector $inspector;

  /**
   * Sets $this->inspector.
   */
  public function setInspector(Inspector $inspector): self {
    $this->inspector = $inspector;

    return $this;
  }

  /**
   * Gets $this->inspector.
   *
   * @return \Acquia\Drupal\RecommendedSettings\Robo\Inspector\Inspector
   *   The inspector.
   */
  public function getInspector(): Inspector {
    return $this->inspector;
  }

}
