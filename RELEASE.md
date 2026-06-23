# Release And Sync Workflow

## Packaging Rule

The WordPress upload ZIP must be root-packed and named `openconsent-cmp.zip`. The ZIP root contains the plugin files directly:

- `openconsent-cmp.php`
- `readme.txt`
- `uninstall.php`
- `LICENSE.txt`
- `assets/`
- `includes/`
- `languages/`

Do not put those files inside a wrapper folder such as `openconsent-cmp/`. The package layout `openconsent-cmp/openconsent-cmp.php` is incorrect for this project.

Do not put the version number in the installable ZIP filename. A root-packed ZIP installs under the ZIP basename, so a filename such as `openconsent-cmp-wordpress-plugin-1.1.4.zip` creates the wrong plugin slug and makes Plugin Check expect the wrong text domain. Keep the installable filename `openconsent-cmp.zip`; put release versioning in plugin metadata, `readme.txt`, Git tags, website visible labels, and cache-busting query strings such as `openconsent-cmp.zip?v=1.1.4`.

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

- Current version: `1.1.4`
- Current tag: `v1.1.4`
- Current public ZIP filename: `openconsent-cmp.zip`

## WordPress.org Submission State

- Submitted for WordPress Plugin Directory review on 2026-06-23.
- Submitted file: `openconsent-cmp.zip`.
- Submitted version: `1.1.3`.
- Initial assigned slug: `openconsent-cmp`.
- Automated scan result: pass.
- Review status at submission time: awaiting manual review.

While the plugin is awaiting review, do not submit it again. If a submission correction is required, reply to the WordPress.org submission email or contact `plugins@wordpress.org`; a plugin slug cannot be changed after approval.

## Verification

Before publishing, verify:

1. The plugin ZIP has `openconsent-cmp.php` at ZIP root.
2. The plugin ZIP does not contain `openconsent-cmp/openconsent-cmp.php`.
3. ZIP paths use forward slashes.
4. The plugin header version, `OPENCONSENT_CMP_VERSION`, WordPress readme stable tag, website metadata, download link, embedded package metadata, and GitHub tag all match.
5. The exact ZIP installs and activates in a local WordPress test site.
6. The official WordPress Plugin Check plugin reports no errors for the installed release ZIP. Run WP-CLI from the clean WordPress root so it scans `wp-content/plugins/openconsent-cmp`, not this development repository.
7. The live website download link returns the same ZIP hash as the local package.

## llms.txt Rule

The canonical LLM guidance file is `cookies-yasa-site/llms.txt`, published as `/llms.txt`.

Keep this file in the simple recommended Markdown shape:

1. One H1 title at the top.
2. One optional blockquote summary.
3. Optional plain-text details without links.
4. One `## Links` section containing all links.
5. Link rows must use this exact bullet format: `- [Link title](https://link_url): Optional link details`.
6. Optional notes may go under `## Notes`, without Markdown links.

Do not add `ilms.txt`, `llms-full.txt`, compatibility copies, redirects, `.htaccess` rules, inline paragraph links, bare URLs, or extra LLM metadata files unless the user explicitly asks for them. When the user says FTP is the truth, download `/llms.txt` from FTP and use that exact content as the local source.

## FTP Sync Rule

When the user says FTP is the latest truth, download from FTP first and sync local/GitHub from that source. Do not upload to FTP during a sync-from-FTP task unless explicitly requested.
