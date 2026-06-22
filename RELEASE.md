# Release Checklist

1. Update `Version` in `openconsent-cmp.php`.
2. Update `OPENCONSENT_CMP_VERSION`.
3. Update `Stable tag` and changelog in `readme.txt`.
4. Refresh `languages/openconsent-cmp.pot` if strings changed.
5. Mirror any frontend controller or CSS changes into the live website demo.
6. Rebuild the root-level WordPress ZIP files.
7. Copy the ZIP into `cookies-yasa-site/downloads/openconsent-cmp-wordpress-plugin-1.0.22.zip`.
8. Update website copy, version labels, metadata, and download size.
9. Verify the live website demo and plugin frontend controller match in behavior and appearance.
10. Commit, push GitHub main, and tag the release.
11. Upload the website/download files and keep only the current release ZIP on the server.

Suggested tag format: `v1.0.22`.
