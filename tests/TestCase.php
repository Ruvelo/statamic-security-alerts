<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Ruvelo\SecurityAlerts\Ecosystem;
use Ruvelo\SecurityAlerts\Finding;
use Ruvelo\SecurityAlerts\KevEntry;
use Ruvelo\SecurityAlerts\Report;
use Ruvelo\SecurityAlerts\Scanner;
use Ruvelo\SecurityAlerts\SecurityAlertsServiceProvider;
use Ruvelo\SecurityAlerts\Severity;
use Ruvelo\StatamicSecurityAlerts\ServiceProvider;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Nothing here may reach Packagist, OSV or CISA.
        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app)
    {
        return [...parent::getPackageProviders($app), SecurityAlertsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('s', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('security-alerts.notify.mail', []);
        $app['config']->set('security-alerts.notify.slack', null);
        $app['config']->set('statamic.editions.pro', true);
    }

    protected function superUser(): \Statamic\Contracts\Auth\User
    {
        return tap(User::make()->email('maya@halyard.test')->makeSuper())->save();
    }

    protected function plainUser(): \Statamic\Contracts\Auth\User
    {
        return tap(User::make()->email('sam@halyard.test'))->save();
    }

    /** Can open the Control Panel, but has no other permission. */
    protected function cpUser(): \Statamic\Contracts\Auth\User
    {
        $role = tap(Role::make('cp-only')->title('CP only')->permissions(['access cp']))->save();

        return tap(User::make()->email('kit@halyard.test')->assignRole($role))->save();
    }

    /**
     * Makes the next check find exactly these findings, with no network.
     *
     * @param  list<Finding>  $findings
     */
    protected function fakeReport(array $findings, bool $complete = true): void
    {
        $report = new Report(
            findings: $findings,
            packages: ['composer' => 67, 'npm' => 38],
            checked: [Ecosystem::Composer, Ecosystem::Npm],
            problems: $complete ? [] : ['package-lock.json could not be read.'],
            checkedAt: CarbonImmutable::now(),
        );

        $this->app->instance(Scanner::class, new class($report) extends Scanner
        {
            public function __construct(private readonly Report $report) {}

            public function scan(bool $fresh = false): Report
            {
                return $this->report;
            }
        });
    }

    protected function exploitedFinding(): Finding
    {
        return new Finding(
            ecosystem: Ecosystem::Composer,
            package: 'livewire/livewire',
            version: '3.6.2',
            advisoryId: 'PKSA-g5js-3ypd-k2kr',
            title: 'Livewire is vulnerable to remote command execution',
            severity: Severity::Critical,
            link: 'https://github.com/advisories/GHSA-29cq-5w36-x7w3',
            cve: 'CVE-2025-54068',
            fixedIn: '3.6.4',
            exploited: new KevEntry('CVE-2025-54068', 'Laravel Livewire Code Injection', CarbonImmutable::parse('2026-03-20'), CarbonImmutable::parse('2026-04-03')),
        );
    }

    protected function mediumFinding(): Finding
    {
        return new Finding(
            ecosystem: Ecosystem::Npm,
            package: 'axios',
            version: '1.7.0',
            advisoryId: 'GHSA-aaaa-bbbb-cccc',
            title: 'axios SSRF',
            severity: Severity::Medium,
            link: 'javascript:alert(1)',
            fixedIn: '1.8.2',
            dev: true,
        );
    }
}
