# Acquia Drupal Recommended Settings
The Acquia Drupal Recommended Settings plugin adds the recommended settings to
the Drupal project, so developers won't have to edit settings.php manually.

The recommended settings includes:
- the required database credentials.
- configuration sync directory path.
- public/private etc. file directory path.
- Acquia site studio sync directory path.

It allows your websites to be easily installed in both Acquia Cloud IDE & local
and deployable on Acquia Cloud.

## Installation

You can also install this using Composer like so:

```
composer require acquia/drupal-recommended-settings
```

## Steps to use Acquia Drupal Recommended Settings with BLT.
This plugin works with acquia/blt plugin.

- Update BLT plugin to latest release.
```
composer update acquia/blt -W
```

- Remove BLT reference from settings.php file located at
  ``/docroot/sites/default/settings.php``.
```php
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

- Update `default.local.settings.php` and `local.settings.php` to use the Environment Detector provided by this plugin instead of BLT:
```diff
- use Acquia\Blt\Robo\Common\EnvironmentDetector;
+ use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
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

