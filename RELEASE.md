# Release And Sync Workflow

## Packaging Rule

The WordPress upload ZIP must be root-packed. The ZIP root contains the plugin files directly:

- `openconsent-cmp.php`
- `readme.txt`
- `uninstall.php`
- `LICENSE.txt`
- `assets/`
- `includes/`
- `languages/`

Do not put those files inside a wrapper folder such as `openconsent-cmp/`. The package layout `openconsent-cmp/openconsent-cmp.php` is incorrect for this project.

## Version Rule

Every project change requires a version bump and a metadata sweep across the whole project before publishing.

Update all of these together:

1. Plugin header `Version` in `openconsent-cmp.php`.
2. `OPENCONSENT_CMP_VERSION` in `openconsent-cmp.php`.
3. `Stable tag` and changelog in `readme.txt`.
4. `Current release` in `README.md`.
5. `Project-Id-Version` in `languages/openconsent-cmp.pot`.
6. Test expectations in `tests/smoke.php`.
7. Website `softwareVersion`, visible version labels, package filename, download URL, download attribute, file size, and asset cache-busters.
8. `cookies-yasa-site/llms.txt`.
9. `cookies-yasa-site/assets/openconsent-package.js`.
10. GitHub commit and tag, using `v` plus the release version.

## Current Release

- Current version: `1.1.2`
- Current tag: `v1.1.2`
- Current public ZIP filename: `openconsent-cmp-wordpress-plugin-1.1.2.zip`

## Verification

Before publishing, verify:

1. The plugin ZIP has `openconsent-cmp.php` at ZIP root.
2. The plugin ZIP does not contain `openconsent-cmp/openconsent-cmp.php`.
3. ZIP paths use forward slashes.
4. The plugin header version, `OPENCONSENT_CMP_VERSION`, WordPress readme stable tag, website metadata, download link, embedded package metadata, and GitHub tag all match.
5. The exact ZIP installs and activates in a local WordPress test site.
6. The live website download link returns the same ZIP hash as the local package.

## FTP Sync Rule

When the user says FTP is the latest truth, download from FTP first and sync local/GitHub from that source. Do not upload to FTP during a sync-from-FTP task unless explicitly requested.
