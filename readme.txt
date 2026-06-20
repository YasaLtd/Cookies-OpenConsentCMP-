=== OpenConsent CMP ===
Contributors: yasa
Tags: cookies, consent, gdpr, google consent mode, privacy
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted cookie consent management for WordPress with categories, prior blocking, local consent logs, declarations, and Google Consent Mode v2 signals.

== Description ==

OpenConsent CMP is a self-hosted consent management plugin by YASA LTD. It helps WordPress site owners present clear cookie choices, categorize services, block optional scripts until consent, record anonymized consent choices, publish a cookie declaration, and send Google Consent Mode v2 signals.

The plugin is open source and runs from your WordPress installation. It does not sell a hosted service and does not send consent logs to YASA LTD.

Source code: https://github.com/Yasaltd/cookies-openconsentcmp-

Project website: https://cookies.yasa.fi/

Important: OpenConsent CMP helps configure and document consent choices. Legal requirements vary by site and region, and site owners remain responsible for their configuration, notices, vendor list, regional rules, and legal review.

= Features =

* Necessary, preferences, statistics, marketing, and unclassified categories.
* Configurable dialog text, disclosure text, colors, and buttons.
* Browser-language detection, WordPress-locale mode, or fixed banner language for built-in frontend labels.
* Region behavior controls for strict opt-in, browser-hint auto mode, or notice mode.
* Browser-translation-friendly banner DOM with visible text and language attributes.
* Automatic URL-pattern script blocking and manual blocking markup.
* Google Consent Mode v2 Basic and Advanced behavior.
* Local anonymized consent log table with retention cleanup.
* Cookie declaration shortcode: [openconsent_declaration].
* Homepage scan for Set-Cookie headers and external static resource hosts.
* Suggested privacy policy text for WordPress privacy tools.

= Google publisher ads and TCF =

Google may require a Google-certified CMP integrated with the IAB Transparency and Consent Framework for personalized AdSense, Ad Manager, or AdMob ads in the EEA, UK, or Switzerland. OpenConsent CMP is not a Google-certified TCF CMP.

= Privacy =

OpenConsent CMP stores settings in the WordPress options table and stores anonymized consent logs in a local database table. Consent logs include a consent ID, selected categories, timestamp, consent hash, and salted hashes of the visitor IP address and user agent.

The plugin sets a first-party browser cookie named `openconsent_cmp` to remember the visitor consent choice. It does not send consent records to YASA LTD or any external service.

The optional homepage scanner sends an HTTP request only to the site's own homepage URL and stores the resulting local scan report in WordPress options.

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

== Changelog ==

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
