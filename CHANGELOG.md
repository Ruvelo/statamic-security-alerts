# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-10-13

Requires `ruvelo/laravel-security-alerts` ^2.0, which moved its scanning to the shared core `ruvelo/security-advisories`. Nothing to do when upgrading: the stored alerts and the table are unchanged.

### Added

- The page's status line says where the last "Check now" got its answers: "via Valtry, signed" or "via public feeds". It is only shown while that check is the latest on record.

### Changed

- Requires `ruvelo/laravel-security-alerts` ^2.0 (was ^1.0), which asks Valtry's Advisory API by default and falls back to the public feeds. "Check now" does the same.
- A failed check shows its friendly message for the core's exceptions too (`SecurityAdvisoriesException`), which no longer extend `SecurityAlertsException`.
- Unrated alerts are recognised through the core's `Severity::NotRated` (was `Unknown`); they still show as "Unrated".

## [1.0.0] - 2026-10-12

First release.

### Added

- A **Security alerts** page under Tools in the Control Panel: open findings from the last check with exploited ones first (marked KEV, with the date CISA listed them and the patch-by date), then by severity, each with the installed and fixed-in versions and a link to the advisory. Light and dark mode, and stacked cards at phone width.
- **Check now**: runs a fresh check from the page (POST, CSRF-protected, permission-checked), with a message about what it found and any feed that could not be read.
- An **All clear** empty state, and clear notices when the alerts table is missing (`php artisan migrate`), when no check has run, when the last check was incomplete and when it is more than 48 hours old (the scheduler has probably stopped).
- A **dashboard widget** (`security_alerts`): counts by severity, red when anything is exploited, when it was last checked, and a stale warning after 48 hours.
- The `view security alerts` permission, for roles; super users always have it.
- Statamic 5 and 6, PHP 8.3 and 8.4, on Laravel 12 and 13. Plain Blade views and one CSS file: no JavaScript build.
