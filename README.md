# Acquia Drupal Recommended Settings
This plugin adds the Acquia's recommended settings in your Drupal project, so that you don't need to worry about making any changes in your settings.php file.
This plugin uses the complete settings.php file generation logic from [Acquia Drupal Recommended Settings](https://github.com/acquia/drupal-recommended-settings) project.

## Installation and usage

In your project, add the repository and require the plugin with Composer:

```
composer config repositories.recommended vcs git@github.com:acquia/drupal-recommended-settings.git
composer require acquia/drupal-recommended-settings
```

## Steps to switch from BLT to Acquia Drupal Recommended Settings

- Remove BLT plugin using
```
composer remove acquia/blt
```

- Remove BLT reference from settings.php file located at
``/docroot/sites/default/settings.php``.
```
require DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php";
/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `blt.settings.php`. See BLT's documentation for more detail.
 *
 * @link https://docs.acquia.com/blt/
 */
 ```

 - Require Acquia Drupal Recommended Settings plugin using
 ```
 composer require acquia/drupal-recommended-settings
 ```
 
- Update BLT references from below settings files with Recommended Settings:
  - default.local.settings.php
  - local.settings.php update use statement from
  ``use Acquia\Blt\Robo\Common\EnvironmentDetector;`` to
  ``use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;``

# License

Copyright (C) 2023 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
