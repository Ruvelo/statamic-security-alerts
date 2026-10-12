# Contributing to Statamic Security Alerts

Thanks for helping! Bug reports, docs fixes and features are all welcome.

## Before you start

- **Bugs:** [open an issue](https://github.com/Ruvelo/statamic-security-alerts/issues/new) with the steps to reproduce and your Statamic version, or go straight to a pull request with a failing test.
- **Features:** open an issue first if it's more than a small change.
- **Scanning itself** (lockfiles, advisory sources, notifications, the schedule, the command) lives in [ruvelo/laravel-security-alerts](https://github.com/Ruvelo/laravel-security-alerts). Changes there come first; this addon only shows what it stores.
- **Security issues:** don't open an issue. See [SECURITY.md](https://github.com/Ruvelo/.github/blob/main/SECURITY.md).

## Setup

You need PHP 8.3+ and Composer. No database server: tests run on in-memory SQLite, and Statamic is flat-file.

`ruvelo/laravel-security-alerts` is on GitHub but not yet on Packagist, so tell Composer where it is (this stays out of the committed `composer.json`; don't commit it):

```bash
git clone https://github.com/<you>/statamic-security-alerts && cd statamic-security-alerts
composer config repositories.lsa vcs https://github.com/Ruvelo/laravel-security-alerts
composer install
composer check
```

`composer check` runs exactly what CI runs:

| Command | What it does |
|---|---|
| `composer test` | PHPUnit on Statamic's `AddonTestCase`, through Orchestra Testbench |
| `composer lint` | Code style check (Laravel Pint) |
| `composer format` | Fix code style |
| `composer analyse` | Static analysis (PHPStan level 6 with Larastan) |

CI runs the tests on PHP 8.3 and 8.4 with Statamic 5 (Laravel 12), Statamic 6 on Laravel 12 and Statamic 6 on Laravel 13. To try Statamic 5 locally: `composer require statamic/cms:^5.0 laravel/framework:^12.0 orchestra/testbench:^10.0 --with-all-dependencies`.

## Making a change

1. Branch from `main`.
2. Write a test that fails without your change. Tests never touch the network: the scanner is replaced with a fake report.
3. Keep the public names stable: the `view security alerts` permission, the `security_alerts` widget handle, the `security-alerts.index` and `security-alerts.check` CP routes and the `statamic-security-alerts::` views.
4. Run `composer format` and `composer check`.
5. Add a line under **Unreleased** in [CHANGELOG.md](CHANGELOG.md).
6. Open the pull request. Screenshots help for anything visual.

## Where things live

```
src/ServiceProvider.php                  Nav item, permission, widget, routes, stylesheet
src/Http/Controllers/                    The page and "Check now"
src/Widgets/SecurityAlertsWidget.php     The dashboard widget
src/Overview.php                         What the page and widget show: alerts, counts, staleness
src/Access.php                           Who may see it
resources/views/                         Blade views for the page and the widget
resources/css/security-alerts.css        The only stylesheet; published to public/vendor
```

## Style

- `declare(strict_types=1)` everywhere, typed properties and return types.
- Blade only, no JavaScript build. Statamic 6 strips `<style>` and `<script>` from Blade views, so styles go in the CSS file, scoped to `.sa`.
- The page must work in the Control Panels of both Statamic 5 and 6: don't use `<ui-*>` components or classes that only one of them ships.
- Comments explain *why*, not *what*.

By contributing you agree that your work is released under the [MIT license](LICENSE) and that you'll follow the [code of conduct](https://github.com/Ruvelo/.github/blob/main/CODE_OF_CONDUCT.md).
