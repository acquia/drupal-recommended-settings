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

### Generate settings without local.settings.php (for CI/production):
```
./vendor/bin/drush init:settings --no-local
```

### Generate only core settings without default templates:
```
./vendor/bin/drush init:settings --no-defaults
```

# Environment-Aware Settings Generation

The plugin intelligently manages settings file generation based on your environment to prevent local development files from being created in production or CI/CD pipelines.

## Automatic Environment Detection

By default, the plugin automatically detects non-local environments and skips generation of local settings files when it detects:

- **CI/CD Environments**: GitHub Actions, GitLab CI, CircleCI, Jenkins, Travis CI, Acquia Pipelines, etc.
- **Production Environments**: Acquia Cloud production, staging, or ODE environments

When a non-local environment is detected, you'll see:
```
Skipping settings generation (non-local environment detected)
```

## Controlling Settings Generation

You have multiple ways to control settings generation, listed in order of priority:

### 1. Environment Variable (Highest Priority)

Use the `DRS_GENERATE_SETTINGS` environment variable to explicitly control whether settings are generated:

**Disable settings generation:**
```bash
export DRS_GENERATE_SETTINGS=false
composer install
```

**Force settings generation:**
```bash
export DRS_GENERATE_SETTINGS=true
composer install
```

**Control local.settings.php generation specifically:**
```bash
export DRS_GENERATE_LOCAL_SETTINGS=false
composer install
```

### 2. Composer Configuration

Configure behavior in your project's `composer.json`:

```json
{
  "extra": {
    "drupal-recommended-settings": {
      "auto-generate-on-install": false
    }
  }
}
```

**Configuration Options:**
- `auto-generate-on-install`: Set to `false` to completely disable automatic settings generation during `composer install/update`

### 3. Automatic Detection (Default)

If no environment variable or composer configuration is set, the plugin uses intelligent environment detection to determine if it's safe to generate local settings files.

## Production & CI/CD Best Practices

### GitHub Actions Example
```yaml
name: Build
on: [push]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Install dependencies
        env:
          DRS_GENERATE_SETTINGS: false
        run: composer install --no-dev --optimize-autoloader
```

### GitLab CI Example
```yaml
build:
  script:
    - export DRS_GENERATE_SETTINGS=false
    - composer install --no-dev --optimize-autoloader
```

### Acquia Cloud Hooks Example
Create `hooks/common/post-code-deploy/install-dependencies.sh`:
```bash
#!/bin/bash
export DRS_GENERATE_SETTINGS=false
composer install --no-dev --optimize-autoloader
```

### Using Composer Configuration
For projects that never want automatic generation (managing settings manually):
```json
{
  "extra": {
    "drupal-recommended-settings": {
      "auto-generate-on-install": false
    }
  }
}
```

Then generate settings manually when needed:
```bash
./vendor/bin/drush init:settings
```

## Files Created by Environment

### Local Development (Default Behavior)
When in a local environment, the following files are created:

**Global settings** (shared across all sites):
- `docroot/sites/settings/default.global.settings.php`

**Site-specific settings** (per site):
- `docroot/sites/default/settings/default.includes.settings.php` - Template for custom includes
- `docroot/sites/default/settings/default.local.settings.php` - Template for local overrides
- `docroot/sites/default/settings/local.settings.php` - Active local settings (with credentials)

**Configuration directories:**
- `config/default/` - Configuration sync directory

**Other files:**
- `salt.txt` - Hash salt for Drupal

### CI/Production Environments (Skipped by Default)
In CI or production environments, only essential global files are created, and local development files like `local.settings.php` are **not generated**, preventing:
- ❌ Local database credentials in production artifacts
- ❌ Development-only settings in production
- ❌ Unnecessary files in deployment packages

## Troubleshooting

**Q: Settings are generated in my CI pipeline, but I don't want them**

A: Set the environment variable in your CI configuration:
```bash
export DRS_GENERATE_SETTINGS=false
```

**Q: I want to generate settings manually only**

A: Disable auto-generation in composer.json:
```json
{
  "extra": {
    "drupal-recommended-settings": {
      "auto-generate-on-install": false
    }
  }
}
```

Then run manually when needed:
```bash
./vendor/bin/drush init:settings
```

**Q: Settings aren't being generated in my local environment**

A: Check if you're using a CI-like environment variable. Explicitly enable generation:
```bash
export DRS_GENERATE_SETTINGS=true
composer install
```

## Quick Reference

| Method | Use Case | Command/Configuration |
|--------|----------|----------------------|
| **Environment Variable** | CI/CD pipelines, temporary override | `export DRS_GENERATE_SETTINGS=false` |
| **Composer Config** | Never auto-generate (manual control) | `"auto-generate-on-install": false` |
| **Drush Flag** | One-time generation without local files | `drush init:settings --no-local` |
| **Auto Detection** | Default behavior (recommended) | No configuration needed |

## Drush Command Options

The `drush init:settings` command supports the following options:

| Option | Description | Example |
|--------|-------------|---------|
| `--database` | Local database name | `--database=mydrupal` |
| `--username` | Database username | `--username=root` |
| `--password` | Database password | `--password=secret` |
| `--host` | Database host | `--host=127.0.0.1` |
| `--port` | Database port | `--port=3306` |
| `--no-local` | Skip generating local.settings.php | `--no-local` |
| `--no-defaults` | Skip copying default template files | `--no-defaults` |
| `--uri` | Multisite URI | `--uri=site1` |

# License

Copyright (C) 2023 Acquia, Inc.

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License version 2 as published by the
Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.
