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
### Install using Composer

```
composer require acquia/drupal-recommended-settings
```
### Multi-site features with Acquia DRS
The Drupal Recommended Settings offer the multi-site feature out of the box.
To configure a multi-site, run the following command, and the plugin will
automatically generate the settings.php in the backend.
```
drush site:install --uri site1
```

The plugin offers various events that allow you to implement custom logic based
on when these events are triggered. You can find the examples of such
implementations from [here](examples).

# Quick examples
### Generate settings with default credentials for the default site:
 ```
./vendor/bin/drush init:settings
```

### Set up a new site with custom credentials for the local environment:
```
./vendor/bin/drush init:settings --database=site1 --username=myuser --password=mypass --host=127.0.0.1 --port=1234 --uri=site1
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
