# Contributing

Thanks for helping improve OpenConsent CMP.

## Scope

OpenConsent CMP is a self-hosted WordPress plugin. Changes should keep consent records local to WordPress, avoid hidden vendor calls, and preserve readable PHP, CSS, and JavaScript.

## Development Guidelines

- Follow WordPress coding and escaping practices.
- Keep frontend text as normal visible DOM text so browser translation tools can translate it.
- Keep the live demo controller and plugin controller aligned.
- Do not claim legal compliance, Google CMP certification, or IAB TCF support unless that work is actually completed and certified.
- Keep changes focused and include a short explanation of user-facing behavior.

## Pull Requests

Before opening a pull request:

- Test activation on a clean WordPress installation.
- Check that the consent dialog opens, saves, reopens, and updates Google Consent Mode signals.
- Verify that optional scripts remain blocked until matching consent is granted.
- Confirm that `assets/js/openconsent-cmp.js` and `assets/css/openconsent-cmp.css` are readable and not minified beyond reviewability.

## Reporting Issues

Please include:

- WordPress version.
- PHP version.
- Browser and device.
- Steps to reproduce.
- Expected and actual behavior.
- Any relevant console errors.
