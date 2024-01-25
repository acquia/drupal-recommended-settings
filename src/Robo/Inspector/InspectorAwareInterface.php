<?php

namespace Acquia\Drupal\RecommendedSettings\Robo\Inspector;

/**
 * Requires setter for inspector.
 */
interface InspectorAwareInterface {

  /**
   * Sets $this->inspector.
   *
   * @param \Acquia\Drupal\RecommendedSettings\Robo\Inspector\Inspector $inspector
   *   The inspector.
   *
   * @return $this
   *   The object.
   */
  public function setInspector(Inspector $inspector);

}
