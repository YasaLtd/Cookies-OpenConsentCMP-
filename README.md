# OpenConsent CMP

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)](https://www.php.net/)
[![License: GPL v2 or later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](https://www.gnu.org/licenses/gpl-2.0.html)

OpenConsent CMP is a GPL WordPress plugin for self-hosted consent management. It helps site owners present clear choices, block optional services until consent, publish a cookie declaration, and keep consent records inside WordPress.

Public website: https://cookies.yasa.fi/

GitHub repository: https://github.com/Yasaltd/cookies-openconsentcmp-

Logo asset: `assets/openconsent-cmp-logo.svg`.

Current release: `1.0.221`.

OpenConsent CMP is built by YASA LTD: https://yasa.fi/

Support ongoing open source development: https://buymeacoffee.com/anteryasa/e/550479

## Repository Description

Open source WordPress consent management plugin by YASA LTD with consent categories, script and embed blocking, local consent logs, cookie declarations, browser-language detection, region behavior controls, and Google Consent Mode v2 support.

## Suggested GitHub Topics

`wordpress`, `wordpress-plugin`, `cookie-consent`, `gdpr`, `cmp`, `privacy`, `google-consent-mode`, `consent-management`, `open-source`, `yasa-ltd`

## What it does

- Shows a configurable consent dialog with necessary, preferences, statistics, marketing, and unclassified categories.
- Blocks configured WordPress script handles, URL-matched scripts, dynamically inserted scripts, and matching iframe embeds until the visitor grants the mapped category.
- Emits Google Consent Mode v2 default denied signals and updates them after consent.
- Integrates with the WP Consent API plugin by declaring compatibility, publishing the consent type, and syncing functional, preferences, statistics, statistics-anonymous, and marketing choices.
- Supports Google Consent Mode Basic or Advanced behavior. Advanced mode lets consent-aware Google tags load with denied defaults so they can adjust behavior before consent.
- Stores anonymized consent audit records in a local WordPress table.
- Shows structured consent records in the WordPress admin and lets administrators download CSV or JSON exports.
- Shows a structured cookie/service inventory table with provider, purpose, privacy policy URL, category, and review status.
- Runs a local crawl scanner for internal pages, `Set-Cookie` headers, external static resources, and suggested service registry rows.
- Provides JSON settings export/import and CSV service registry export/import for migration and review workflows.
- Includes optional debug mode so site owners can inspect blocked scripts and embeds through `OpenConsent.debug` and console notices.
- Shows a persistent Privacy choices control after consent so visitors can reopen the dialog and change their choices.
- Detects the visitor browser language for built-in banner labels and category names when the site owner has not customized that text.
- Keeps banner copy as regular visible DOM text with `translate="yes"` and `lang` attributes so browser translation tools can translate it.
- Provides a `[openconsent_declaration]` shortcode for a cookie declaration page.
- Includes a local homepage scanner that reports `Set-Cookie` headers and external static resource hosts.
- Lets site owners maintain an open service registry using `pattern|category|name|provider|purpose|privacy_url` lines and optional script-handle rules using `handle|category|name` lines.

## Google publisher ads and TCF

Google requires publishers using AdSense, Ad Manager, or AdMob to use a Google-certified CMP integrated with the IAB Transparency and Consent Framework when serving personalized ads to users in the EEA, UK, or Switzerland. OpenConsent CMP is not certified by Google and does not implement the IAB TCF signal. The default service registry treats AdSense and Google publisher ad scripts as marketing services, but that does not make this plugin eligible for personalized publisher ads in those regions.

## Google EU user consent policy

Google's EU user consent policy requires legally valid consent where required, consent records, clear revocation instructions, and clear identification of parties that may collect, receive, or use personal data. OpenConsent CMP includes local consent logs, a persistent privacy choices control, party disclosure text, and a cookie declaration shortcode to help site owners present this information. Site owners remain responsible for validating their notices, vendor list, and consent flows.

## WP Consent API mapping

When the WP Consent API plugin is installed and the integration is enabled in OpenConsent settings, OpenConsent publishes visitor choices to the standard API categories:

- Necessary -> `functional`
- Preferences -> `preferences`
- Statistics -> `statistics` and `statistics-anonymous`
- Marketing -> `marketing`
- Unclassified -> not mapped; these services should stay blocked until reviewed

This allows compatible WordPress plugins to read the same consent state as the OpenConsent banner.

## How this differs from hosted CMPs

Hosted CMPs often provide cloud crawling, managed cookie repositories, geolocation frameworks, TCF integrations, certification programs, and paid compliance operations. OpenConsent CMP is intentionally self-hosted and transparent: site owners control the service registry and consent records. The scanner is lightweight and local; it does not execute JavaScript like a full browser crawler.

## Installation

1. Upload the release ZIP through **Plugins > Add New > Upload Plugin**, or copy the plugin files into `wp-content/plugins/openconsent-cmp/`.
2. Activate **OpenConsent CMP** in WordPress.
3. Open **Settings > OpenConsent CMP**.
4. Review the service registry and add any third-party scripts used by the site.
5. Add `[openconsent_declaration]` to your cookie policy page.

Compatibility target: WordPress 6.0 or newer, tested up to WordPress 7.0. PHP 7.4 or newer is required.

## Manual blocking markup

For snippets outside WordPress' registered script system, use:

```html
<script type="text/plain" data-openconsent-category="statistics" data-openconsent-src="https://analytics.vendor.test/analytics.js"></script>
```

Inline snippets also work:

```html
<script type="text/plain" data-openconsent-category="marketing">
  console.log('Runs after marketing consent.');
</script>
```

Registered WordPress script handles can also be mapped in **Settings > OpenConsent CMP**:

```text
contact-form-analytics|statistics|Contact form analytics
```

Iframe embeds whose URLs match the service registry are replaced with a consent placeholder until the mapped category is granted.

## Service Registry Format

The service registry supports both the older three-field format and the richer six-field format:

```text
pattern|category|display name|provider|purpose|provider privacy URL
```

Example:

```text
analytics.example.com|statistics|Example Analytics|Example Ltd|Audience measurement.|https://example.com/privacy
```

The extra provider, purpose, and policy fields appear in the cookie declaration and visitor category details.

The admin screen can export the registry as CSV and import CSV rows back in replace or append mode. CSV files may include a header row with `pattern,category,name,provider,purpose,privacy_url`.

## Public JavaScript API

```js
OpenConsent.getConsent();
OpenConsent.setConsent({ preferences: true, statistics: true, marketing: false });
OpenConsent.showBanner();
OpenConsent.revoke();
OpenConsent.debug.blocked;

window.addEventListener('openconsent:updated', (event) => {
  console.log(event.detail);
});
```

## Important compliance note

OpenConsent CMP helps site owners configure and document consent choices. Laws and regulator expectations vary by region and change over time, so review your setup with qualified counsel before relying on it for compliance.

## Support

Use GitHub issues for reproducible bugs and feature requests. For YASA LTD project inquiries, use https://yasa.fi/contact/.
