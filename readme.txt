=== OpenConsent CMP ===
Contributors: yasa
Donate link: https://buymeacoffee.com/anteryasa/e/550479
Tags: cookies, consent, gdpr, google consent mode, privacy
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted cookie consent management with prior blocking, local consent logs, declarations, and Google Consent Mode v2.

== Description ==

OpenConsent CMP is a self-hosted consent management plugin by YASA LTD. It helps WordPress site owners present clear cookie choices, categorize services, block optional scripts until consent, record anonymized consent choices, publish a cookie declaration, and send Google Consent Mode v2 signals.

The plugin is open source and runs from your WordPress installation. It does not sell a hosted service and does not send consent logs to YASA LTD.

Author: YASA LTD, https://yasa.fi/

Support open source development: https://buymeacoffee.com/anteryasa/e/550479

Source code: https://github.com/Yasaltd/cookies-openconsentcmp-

Project website: https://cookies.yasa.fi/

Important: OpenConsent CMP helps configure and document consent choices. Legal requirements vary by site and region, and site owners remain responsible for their configuration, notices, vendor list, regional rules, and legal review.

= Features =

* Necessary, preferences, statistics, marketing, and unclassified categories.
* Service registry with match pattern, category, display name, provider, purpose, and provider privacy URL fields.
* Configurable dialog text, disclosure text, colors, and buttons.
* Browser-language detection, WordPress-locale mode, or fixed banner language for built-in frontend labels.
* Region behavior controls for strict opt-in, browser-hint auto mode, or notice mode.
* Browser-translation-friendly banner DOM with visible text and language attributes.
* Automatic URL-pattern script blocking, WordPress script handle blocking, iframe embed blocking, and manual blocking markup.
* Google Consent Mode v2 Basic and Advanced behavior.
* Per-signal Google Consent Mode mapping for ad storage, ad user data, ad personalization, analytics storage, functionality storage, and personalization storage.
* WP Consent API compatibility for plugin-to-plugin consent sharing when the WP Consent API plugin is installed.
* Local anonymized consent log table with retention cleanup.
* Structured consent records in the WordPress admin with filters, detail views, pagination, retention cleanup, and CSV/JSON downloads.
* Structured cookie and service inventory table for reviewing provider, purpose, privacy URL, category, and registry status.
* Cookie declaration shortcode: [openconsent_declaration].
* Local crawl scanner for internal pages, Set-Cookie headers, external static resource hosts, and suggested service registry rows.
* Debug mode for inspecting blocked scripts and embeds in the browser console and JavaScript API.
* JSON settings export/import and CSV service registry export/import.
* Suggested privacy policy text for WordPress privacy tools.

= Google publisher ads and TCF =

Google may require a Google-certified CMP integrated with the IAB Transparency and Consent Framework for personalized AdSense, Ad Manager, or AdMob ads in the EEA, UK, or Switzerland. OpenConsent CMP is not a Google-certified TCF CMP.

= Privacy =

OpenConsent CMP stores settings in the WordPress options table and stores anonymized consent logs in a local database table. Consent logs include a consent ID, selected categories, timestamp, consent hash, and salted hashes of the visitor IP address and user agent.

The plugin sets a first-party browser cookie named `openconsent_cmp` to remember the visitor consent choice. It does not send consent records to YASA LTD or any external service.

The optional crawl scanner sends HTTP requests only to the site's own internal pages and stores the resulting local scan report in WordPress options.

YASA LTD links and the optional donation link are shown only inside the WordPress admin settings for site owners. The plugin does not add YASA LTD credit links, donation links, or powered-by links to the public frontend.

= External services =

OpenConsent CMP does not contact YASA LTD servers and does not use a hosted YASA service.

If the site owner configures services such as Google Tag Manager, Google Analytics, Google Consent Mode, embedded media, analytics scripts, or advertising scripts, those third-party services may receive data according to the site owner's configuration and the visitor's consent choices. Site owners are responsible for documenting those services in their privacy policy and service registry.

== Installation ==

1. Upload the `openconsent-cmp` folder to `/wp-content/plugins/`.
2. Activate OpenConsent CMP in WordPress.
3. Go to Settings > OpenConsent CMP.
4. Review the service registry and category text.
5. Add `[openconsent_declaration]` to your cookie policy page.

== Frequently Asked Questions ==

= Does this guarantee GDPR compliance? =

No. It helps implement and document consent choices, but legal requirements depend on the site, vendors, jurisdictions, and notices.

= Does it contact YASA LTD servers? =

No. The plugin stores settings and anonymized consent logs locally in WordPress.

= Where can I report bugs or request features? =

Use the GitHub repository: https://github.com/Yasaltd/cookies-openconsentcmp-

= Can users revoke consent? =

Yes. After consent, the frontend shows a Privacy choices control that reopens the full consent dialog with the current choices selected.

== Screenshots ==

1. Admin settings with service inventory, Google Consent Mode mapping, crawl scanner, and import/export tools.
2. Frontend consent dialog with expandable category details, admin-configured theme colors, localized labels, and a site privacy policy link.
3. Local consent records with filters, detail views, retention cleanup, and CSV/JSON exports.

== Upgrade Notice ==

= 1.1.5 =
Fixes the release ZIP packaging so internal archive paths use WordPress-safe forward slashes.

= 1.1.4 =
Moves frontend color theme selection into the admin settings page and removes visitor-facing color customization from the consent dialog.

= 1.1.3 =
Uses a slug-stable install package name so WordPress installs the plugin as openconsent-cmp and keeps the text domain valid.

= 1.1.2 =
Improves WordPress.org review readiness with clearer privacy disclosures and reduced static-analysis noise.

== Changelog ==

= 1.1.5 =
* Rebuilds the public release ZIP with forward-slash internal paths for reliable activation on Linux WordPress hosts.
* Keeps the 1.1.4 plugin behavior unchanged.

= 1.1.4 =
* Moves color theme selection into the WordPress admin settings page.
* Removes the visitor-facing consent dialog color selector and local theme preference.
* Adds a settings-page changelog section for recent version changes.

= 1.1.3 =
* Keeps the installable ZIP filename slug-stable as `openconsent-cmp.zip`.
* Moves release versioning to plugin metadata, readme stable tag, cache-busting query strings, and Git tags.
* Prevents versioned ZIP filenames from changing the installed WordPress plugin slug and causing text-domain mismatch reports.

= 1.1.2 =
* Removes the manual translation loader that WordPress.org no longer requires for hosted plugins.
* Clarifies WordPress.org static-analysis annotations for plugin-owned custom log table queries and uninstall cleanup.
* Expands readme privacy and external-service disclosures for review clarity.

= 1.1.1 =
* Normalizes frontend script line endings to reduce WordPress.org review noise.
* Sanitizes uploaded import temp-file paths before reading settings and service CSV imports.
* Clarifies static-analysis annotations around whitelisted consent-log SQL queries.

= 1.1 =
* Removes public frontend credit and donation links from the consent dialog for WordPress.org guideline readiness.
* Keeps YASA LTD credits and optional donation links in the WordPress admin for site owners.
* Cleans submission-readiness issues found by Plugin Check, including translator comments, SQL preparation annotations, CSV import handling, and readme metadata.

= 1.0.20 =
* Removes visitor-facing Google documentation links from the public consent dialog.
* Completes built-in frontend translations for category details, summaries, blocked embed text, and theme controls.
* Keeps Google Consent Mode reference links in the WordPress admin where site owners configure the integration.
* Removes unused public banner link styling after the UI cleanup.

= 1.0.19 =
* Rebuilds the WordPress upload package with a standard `openconsent-cmp` top-level folder.
* Uses ZIP-standard forward-slash paths so managed Linux hosts can extract plugin directories correctly.
* Fixes activation failures caused by missing `includes` paths after upload extraction.

= 1.0.18 =
* Rebuilds the install package with runtime plugin files only at the ZIP root.
* Removes repository-only assets and local test scripts from the WordPress upload package.

= 1.0.17 =
* Adds CSV import for the service registry with replace and append modes.
* Accepts service CSV files with or without a header row.
* Improves the admin import/export workflow for moving service inventories between WordPress sites.

= 1.0.16 =
* Adds a structured cookie and service inventory table in the WordPress admin.
* Replaces the homepage-only scan with a local crawl report for internal pages, Set-Cookie headers, external resources, and suggested service registry rows.
* Adds frontend debug mode for blocked scripts and embeds.
* Adds JSON settings export/import and CSV service registry export.
* Adds WordPress.org submission assets and automated lint checks.

= 1.0.15 =
* Extends the service registry with provider, purpose, and provider privacy URL fields.
* Adds richer service disclosures to the cookie declaration shortcode.
* Shows provider and purpose context in visitor category details and blocked-resource diagnostics.
* Keeps older three-field service registry lines backward-compatible.

= 1.0.14 =
* Adds WP Consent API compatibility declaration for OpenConsent CMP.
* Publishes the configured consent type to WP Consent API.
* Syncs visitor choices to functional, preferences, statistics, statistics-anonymous, and marketing consent categories.
* Adds an admin setting and category mapping guidance for WP Consent API.

= 1.0.13 =
* Adds consent record filters for action, granted category, date range, and consent ID or URL search.
* Adds paginated admin record views with expandable technical details for each consent record.
* Makes CSV and JSON downloads respect the current admin filters.
* Adds a manual retention cleanup action for expired local consent records.

= 1.0.12 =
* Stores consent records in structured database columns for action, categories, region, language, page URL, plugin version, and anonymized hashes.
* Adds readable admin consent records with category badges and summary counts.
* Adds CSV and JSON record downloads for administrators.
* Adds a WordPress dashboard widget with consent record totals and download shortcuts.

= 1.0.11 =
* Adds WordPress script handle rules for local or plugin-registered scripts that cannot be identified by URL pattern alone.
* Adds consent-aware iframe embed blocking for matching services in post content.
* Restores matching blocked embeds when consent is granted and reports blocked scripts and embeds in the visitor dialog details.
* Moves admin styling into a dedicated enqueued admin stylesheet.

= 1.0.10 =
* Adds a native Settings link on the WordPress Plugins screen.
* Adds a first-run admin notice that links to setup after activation.
* Adds a GPL license file and removes the custom Update URI header for WordPress.org-style submission readiness.

= 1.0.9 =
* Fixes frontend startup on WordPress sites where optimization plugins load the consent script after DOMContentLoaded.
* Publishes the install package with plugin files at the ZIP root as requested for direct WordPress upload testing.

= 1.0.8 =
* Replaces redundant Google signal checkboxes with real signal-to-category mappings.
* Uses the mapping to build the actual Google Consent Mode default and update payloads.
* Shows actual blocked runtime scripts in the expanded visitor category details.

= 1.0.7 =
* Adds per-signal Google Consent Mode controls in the admin screen.
* Adds expandable visitor banner details showing blocked services and affected Google consent signals.
* Adds direct Google Consent Mode and user consent policy links in admin and frontend details.

= 1.0.6 =
* Adds region behavior controls for strict opt-in, browser-hint auto mode, and notice mode.
* Adds a selectable banner language mode alongside browser-language detection.
* Adds an Unclassified category for services that need review before final categorization.
* Mirrors the new controls in the live demo controller.

= 1.0.5 =
* Improves the admin settings screen with status cards, setup guidance, clearer grouped controls, and CSV export for consent logs.
* Improves the visitor consent dialog with a live summary of selected optional categories and clearer accessible category descriptions.

= 1.0.4 =
* Hardens activation defaults and locale detection.
* Corrects the public GitHub repository URL.

= 1.0.3 =
* Cache-busts frontend assets after the live controller polish pass.

= 1.0.2 =
* Adds a theme selector to the Customize button.
* Keeps the live website demo and plugin frontend controller aligned.

= 1.0.1 =
* Restores the dark centered consent dialog and left-side Privacy choices control.
* Reopens the full consent dialog with existing choices selected.

= 1.0.0 =
* Initial release.
