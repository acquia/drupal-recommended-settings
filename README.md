# Acquia Drupal Recommended Settings
The Acquia Drupal Recommended Settings plugin adds the recommended settings to
the Drupal project, so developers won't have to edit settings.php manually.

The recommended settings includes:
- The required database credentials.
- Configuration sync directory path.
- File directory path i.e public/private etc.
- Acquia site studio sync directory path.

It allows your websites to be easily installed in both Acquia Cloud IDE & local
and deployable on Acquia Cloud.

## Installation

You can also install this using Composer like so:

```
composer require acquia/drupal-recommended-settings
```

# Quick examples.
## Generate settings for a given site.
 ```
<?php

/**
 * @file
 * Include DRS settings.
 */

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Settings;

// Create settings object.
$siteUri = "site1";
$settings = new Settings(DRUPAL_ROOT, $siteUri);

try {
  // Call generate method.
  $settings->generate();
} catch (SettingsException $e) {
  echo $e->getMessage();
}
```

## Generate settings for a given site passing database credentials.

```
<?php

/**
 * @file
 * Include DRS settings.
 */

use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Acquia\Drupal\RecommendedSettings\Settings;

// Create settings object.
$siteUri = "site1";
$settings = new Settings(DRUPAL_ROOT, $siteUri);

// Database details.
$dbSpec = [
  'drupal' => [
    'db' => [
      'database' => 'drupal',
      'username' => 'drupal',
      'password' => 'drupal',
      'host' => 'localhost',
      'port' => '3306',
    ],
  ],
];

try {
  // Call generate method passing database details.
  $settings->generate($dbSpec);
} catch (SettingsException $e) {
  echo $e->getMessage();
}
```

# License

Copyright (C) 2023 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License version 2 as published by the
Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.
