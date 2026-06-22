# FTP Source Sync Workflow

The live FTP site and the ZIP currently present in `/downloads/` are the source of truth for this project. For version `1.0.221`, sync local files and GitHub from FTP; do not generate or upload replacement files unless explicitly requested.

## Current Source

- FTP root: `/`
- Website entrypoint: `/index.php`
- Latest plugin package on FTP: `/downloads/openconsent-cmp-wordpress-plugin-1_0_221.zip`
- Current plugin version inside the package: `1.0.221`
- Public package layout: root-packed ZIP, with `openconsent-cmp.php` at the ZIP root.

## Sync Steps

1. Download the current FTP website files into `cookies-yasa-site/`.
2. Download the current FTP `/assets/` files into `cookies-yasa-site/assets/`.
3. Download the current FTP `/downloads/` package into `cookies-yasa-site/downloads/`.
4. Extract the FTP plugin ZIP into a temporary directory.
5. Replace the plugin repo runtime files from that extracted ZIP:
   - `openconsent-cmp.php`
   - `readme.txt`
   - `uninstall.php`
   - `LICENSE.txt`
   - `assets/`
   - `includes/`
   - `languages/`
6. Keep repo-only files such as `.github/`, `README.md`, `RELEASE.md`, `SECURITY.md`, `SUPPORT.md`, and `tests/`.
7. Update repo-only version references and tests to match the FTP package version.
8. Verify the ZIP structure before committing:
   - `openconsent-cmp.php` exists at ZIP root.
   - `openconsent-cmp/openconsent-cmp.php` does not exist.
   - ZIP paths use forward slashes.
   - plugin header and `OPENCONSENT_CMP_VERSION` match the FTP package version.
9. Commit the synced plugin repo and tag GitHub with `v1.0.221`.

## Do Not

- Do not edit FTP during a sync-from-FTP task.
- Do not rebuild the plugin package unless explicitly requested.
- Do not rename the FTP download package during sync.
- Do not change plugin or website behavior while syncing.
