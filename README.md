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

You have multiple ways to control settings generation:

### 1. Composer Configuration (Recommended for Production Artifacts)

Configure behavior in your project's `composer.json` to skip local development files in production artifacts:

```json
{
  "extra": {
    "drupal-recommended-settings": {
      "generate-local-settings": false
    }
  }
}
```

This will skip generating:
- `local.settings.php` - Active local settings with credentials
- `default.local.settings.php` - Local settings template
- `default.includes.settings.php` - Custom includes template

Global settings and core configuration will still be created.

**Configuration Option:**
- `generate-local-settings`: Set to `false` to prevent generating local development files - ideal for production artifact builds

### 2. Environment Variable (Runtime Override)

Use `DRS_GENERATE_LOCAL_SETTINGS` to control local file generation at runtime:

```bash
export DRS_GENERATE_LOCAL_SETTINGS=false
composer install
```

This is useful for one-time builds or testing scenarios.

### 3. Automatic Detection (Default)

If no environment variable or composer configuration is set, the plugin uses intelligent environment detection to determine if it's safe to generate local settings files.

## Production & CI/CD Best Practices

### CI/CD Pipeline Example

The plugin automatically detects CI/CD environments and skips local file generation. Simply run:

```bash
composer install --no-dev --optimize-autoloader
```

No additional configuration needed - works with GitHub Actions, GitLab CI, CircleCI, Jenkins, and other CI platforms.

### Using Composer Configuration

**For production artifact builds (generate settings but not local files):**
```json
{
  "extra": {
    "drupal-recommended-settings": {
      "generate-local-settings": false
    }
  }
}
```

This is ideal when building artifacts for deployment - you get all the recommended settings structure without local development files. This configuration persists in version control and doesn't rely on environment variables during the build process.

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

### CI/Production Environments (Auto-detected or `generate-local-settings: false`)
When `generate-local-settings` is disabled (via composer config, environment variable, or auto-detection), the following files are **not generated**:
- `default.includes.settings.php` - Template for custom includes
- `default.local.settings.php` - Template for local overrides
- `local.settings.php` - Active local settings (with credentials)

Only essential files are created:
- `default.global.settings.php` - Global settings (shared)
- `settings.php` - Core settings file with acquia-recommended.settings.php include
- `config/default/` - Configuration sync directory
- `salt.txt` - Hash salt

This prevents:
- ❌ Local database credentials in production artifacts
- ❌ Development-only settings in production
- ❌ Unnecessary files in deployment packages

## Quick Reference

| Method | Use Case | Command/Configuration |
|--------|----------|----------------------|
| **Composer Config** | Skip local files in production artifacts (recommended) | `"generate-local-settings": false` |
| **Environment Variable** | Temporary/runtime skip of local files | `export DRS_GENERATE_LOCAL_SETTINGS=false` |
| **Drush Flag** | One-time manual generation without local files | `drush init:settings --no-local` |
| **Auto Detection** | Automatic in CI/production (default) | No configuration needed |

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
