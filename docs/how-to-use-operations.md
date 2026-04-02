## Customizing File Operations

There are two ways to customize file operations:

1. **`composer.json`** → Declare your customizations statically in your project's `composer.json`.
2. **`PreSettingsFileGenerateEvent`** → Subscribe to an event at runtime to add, change, or remove operations programmatically.

Both approaches use the same simple operation format described below.

---

### Understanding Operations

Every operation maps a **destination file path** to an **action** (or a set of
actions). The operations support placeholders such as `${docroot}`, `${site}`, `${drs.root}` etc. which
are resolved automatically at runtime.

#### Supported operations and options

The table below details each operation, the payload keys you can use, their types, and what happens when you set them:

| Action  | What it does  |
|---------|-----------------------|
| copy    | - `path` (string, **required**): The source file to copy. Must be a local file path.<br>- `overwrite` (boolean, optional): If true, overwrites the destination only if its content differs from the source. If false or omitted, skips if the destination exists.<br>- `with-placeholder` (boolean, optional): If true, resolves config placeholders (e.g. `${drupal.db.database}`) in the source content before copying. If false or omitted, copies the file as-is. |
| append  | - `path` (string, optional): Source file whose content will be appended to the destination. Can be used alone or with `content`.<br>- `content` (string, optional): Inline string content to append to the destination. Can be used alone or with `path`. |
| prepend | - `path` (string, optional): Source file whose content will be prepended to the destination. Can be used alone or with `content`.<br>- `content` (string, optional): Inline string content to prepend to the destination. Can be used alone or with `path`. |

- For `append` and `prepend`, you can use either `path`, `content`, or a list of objects with either key.
- For `copy`, only `path` is required; `overwrite` and `with-placeholder` are optional.

### Default operations

The block below shows the default operations performed by DRS.

```json
{
  "${docroot}/sites/${site}/settings.php": {
    "copy": {
      "path": "${docroot}/sites/default/default.settings.php"
    },
    "append": [
      {
        "content": "require DRUPAL_ROOT . \"/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php\""
      },
      {
        "content": "/**\n  * IMPORTANT.\n  *\n  * Do not include additional settings here. Instead, add them to settings\n  * included by `acquia-recommended.settings.php`. See Acquia's documentation for more detail.\n  *\n  * @link https://docs.acquia.com/\n*/"
      }
    ]
  },
  "${docroot}/sites/settings/default.global.settings.php": "${drs.root}/settings/global/default.global.settings.php",
  "${docroot}/sites/${site}/settings/default.includes.settings.php": "${drs.root}/settings/site/default.includes.settings.php",
  "${docroot}/sites/${site}/settings/default.local.settings.php": "${drs.root}/settings/site/default.local.settings.php",
  "${docroot}/sites/${site}/settings/local.settings.php": {
    "copy": {
      "path": "${drs.root}/settings/site/default.local.settings.php",
      "with-placeholder": true
    }
  }
}
```
**Note:** For the destination `${docroot}/sites/${site}/settings.php`, you cannot set its value to `false` to skip the operation. DRS always appends the required `require` line to this file and will throw an error if you attempt to skip it. You may change the source path for this file, but skipping the operation entirely is not supported.

---

### Option 1 — Customise via `composer.json`

Add an `operations` key under `extra.drupal-recommended-settings` in your
project's `composer.json`. Your entries are **merged with** the DRS defaults,
so you only need to declare what you want to change or override.

```json
{
  "extra": {
    "drupal-recommended-settings": {
      "operations": {
        "${docroot}/sites/${site}/settings/local.settings.php": false
      }
    }
  }
}
```

> **Tip:** If your operations list grows large, move it to a separate JSON file
> and reference it with `operations-file` instead:
> ```json
> {
>   "extra": {
>     "drupal-recommended-settings": {
>       "operations-file": "drs-operations.json"
>     }
>   }
> }
> ```

#### Examples

**Skip a file — prevent DRS from copying or modifying it:**
```json
"${docroot}/sites/${site}/settings/local.settings.php": false
```

**Copy a file from a custom source path (skips if destination already exists):**
```json
"${docroot}/sites/${site}/settings.php": "${drs.root}/assets/settings.php"
```

**Copy a file and overwrite the destination when content has changed:**
> ```json
> "${docroot}/sites/${site}/settings.php": {
>   "copy": {
>     "path": "${drs.root}/assets/settings.php",
>     "overwrite": true
>   }
> }
> ```

**Copy a file while resolving placeholders inside the source content:**
> ```json
> "${docroot}/sites/${site}/settings.php": {
>   "copy": {
>     "path": "${drs.root}/assets/settings.php",
>     "with-placeholder": true
>   }
> }
> ```

**Append content to a file — from an inline string and/or from another file:**
> ```json
> "${docroot}/sites/${site}/settings.php": {
>   "append": [
>     { "content": "// My custom settings line.\n" },
>     { "path": "/path/to/my/extra.settings.php" }
>   ]
> }
> ```

**Prepend content to a file:**
> ```json
> "${docroot}/sites/${site}/settings.php": {
>   "prepend": [
>     { "content": "<?php\n// Prepended by my project.\n" }
>   ]
> }
> ```

**Combine multiple actions on the same file (copy then append/prepend):**
> ```json
> "${docroot}/sites/${site}/settings/local.settings.php": {
>   "copy": {
>     "path": "${drs.root}/assets/local.settings.php"
>   },
>   "append": [
>     { "content": "// Added by my project.\n" }
>   ]
> }
> ```

---

### Option 2 — Customize via the `PreSettingsFileGenerateEvent`

If you need to alter operations at runtime — for example based on the current
environment, the site being initialized, or any other dynamic condition — you
can subscribe to the `PreSettingsFileGenerateEvent`.

The event gives you the full list of operations just before they run. You can
add new entries, modify existing ones, or remove any you do not want.

#### Registering a subscriber

Your Drush command class can listen to the event using a `#[CLI\Hook]` attribute.

For a complete, real-world example of how to alter file operations using this event, see:

- [ExampleDrushCommands.php – alterSettingsOperations()](../examples/example-drush-command/src/Drush/Commands/ExampleDrushCommands.php#L56)

This method demonstrates how to skip files, change source paths, enable overwrite or placeholder resolution, and append custom content to settings files.
