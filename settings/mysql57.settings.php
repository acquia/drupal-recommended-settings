<?php

/**
 * @file
 * Settings file for mysql57 backport module if present.
 */
if (file_exists(DRUPAL_ROOT . '/modules/contrib/mysql57/settings.inc')) {
  require DRUPAL_ROOT . '/modules/contrib/mysql57/settings.inc';
}
