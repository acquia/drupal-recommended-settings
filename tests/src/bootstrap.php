<?php

use DrupalFinder\DrupalFinder;

$finder = new DrupalFinder();
$finder->locateRoot(getcwd());
$root = $finder->getDrupalRoot();
$vendor = $finder->getVendorDir();

// When running projects locally, the path will return empty.
if (!$vendor) {
  $vendor = getcwd() . "/vendor";
}

$autoload = "$vendor/autoload.php";
if (!file_exists($autoload)) {
  throw new Exception("Unable to determine autoload file.");
}

/** @var \Composer\Autoload\ClassLoader $class_loader */
// Require Project's autoloader.
$class_loader = require $autoload;

$class_loader->addPsr4("Acquia\\Drupal\\RecommendedSettings\\Tests\\", __DIR__);
if ($root) {
  // Register the Drupal core 'Test' namespaces. This is required, as ORCA uses
  // "\Drupal\Tests\Listeners\HtmlOutputPrinter" as printer class.
  $class_loader->addPsr4('Drupal\\', "$root/core/tests/Drupal");
}
