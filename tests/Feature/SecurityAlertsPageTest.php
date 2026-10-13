<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts\Tests\Feature;

use Carbon\CarbonImmutable;
use Ruvelo\SecurityAdvisories\Exceptions\SecurityAdvisoriesException;
use Ruvelo\SecurityAdvisories\Report;
use Ruvelo\SecurityAlerts\Models\SecurityAlert;
use Ruvelo\SecurityAlerts\Scanner;
use Ruvelo\SecurityAlerts\SecurityAlerts;
use Ruvelo\StatamicSecurityAlerts\Tests\TestCase;

class SecurityAlertsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
    }

    public function test_a_permitted_user_sees_findings_with_the_exploited_one_first(): void
    {
        $this->fakeReport([$this->mediumFinding(), $this->exploitedFinding()]);
        SecurityAlerts::check(notify: false);

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertSeeInOrder(['Exploited (KEV)', 'livewire/livewire', '3.6.2', '3.6.4', 'axios'])
            ->assertSee('In CISA KEV since 2026-03-20, patch by 2026-04-03')
            ->assertSee('Check now')
            ->assertSee('exploited in the wild');
    }

    public function test_unsafe_advisory_links_are_not_rendered(): void
    {
        $this->fakeReport([$this->mediumFinding()]);
        SecurityAlerts::check(notify: false);

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertDontSee('javascript:alert(1)', false);
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs($this->cpUser())
            ->get(cp_route('security-alerts.index'))
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        $this->get(cp_route('security-alerts.index'))->assertRedirect();
    }

    public function test_it_shows_all_clear_when_nothing_is_open(): void
    {
        $this->fakeReport([]);
        SecurityAlerts::check(notify: false);

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertSee('All clear');
    }

    public function test_it_warns_when_the_last_check_is_stale(): void
    {
        $this->fakeReport([]);
        SecurityAlerts::check(notify: false);
        $this->travelTo(CarbonImmutable::now()->addHours(49));

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertSee('This is out of date');
    }

    public function test_it_says_so_when_no_check_has_run(): void
    {
        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertSee('No check has run yet')
            ->assertDontSee('All clear');
    }

    public function test_it_asks_for_a_migration_when_the_table_is_missing(): void
    {
        $this->artisan('migrate:rollback');

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertSee('php artisan migrate')
            ->assertDontSee('Check now');
    }

    public function test_check_now_runs_a_check_and_records_findings(): void
    {
        $this->fakeReport([$this->exploitedFinding()]);

        $this->actingAs($this->superUser())
            ->post(cp_route('security-alerts.check'))
            ->assertRedirect(cp_route('security-alerts.index'))
            ->assertSessionHas('security-alerts.status', '1 vulnerability found in 1 package.');

        $this->assertSame(1, SecurityAlert::query()->open()->exploited()->count());
        $this->assertNotNull(SecurityAlerts::lastCheck());
    }

    public function test_the_page_names_the_source_of_the_check_it_just_ran(): void
    {
        $this->fakeReport([$this->exploitedFinding()]);
        $user = $this->superUser();

        $this->actingAs($user)->post(cp_route('security-alerts.check'));

        $this->actingAs($user)->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertSee('105 packages looked at, via Valtry, signed.');
    }

    public function test_a_failed_check_shows_the_message_from_the_core(): void
    {
        $this->app->instance(Scanner::class, new class extends Scanner
        {
            public function __construct() {}

            public function scan(bool $fresh = false): Report
            {
                throw new SecurityAdvisoriesException('Valtry and the public feeds are both unreachable.');
            }
        });

        $this->actingAs($this->superUser())
            ->post(cp_route('security-alerts.check'))
            ->assertRedirect(cp_route('security-alerts.index'))
            ->assertSessionHas('security-alerts.error', 'Valtry and the public feeds are both unreachable.');
    }

    public function test_check_now_needs_the_permission(): void
    {
        $this->fakeReport([$this->exploitedFinding()]);

        $this->actingAs($this->cpUser())
            ->post(cp_route('security-alerts.check'))
            ->assertForbidden();

        $this->assertSame(0, SecurityAlert::query()->count());
    }

    public function test_the_check_now_form_carries_a_csrf_token(): void
    {
        $this->fakeReport([]);
        SecurityAlerts::check(notify: false);

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertSee('name="_token"', false)
            ->assertSee('action="'.cp_route('security-alerts.check').'"', false);
    }

    public function test_open_alerts_still_say_when_they_were_checked_after_the_cache_is_cleared(): void
    {
        $this->fakeReport([$this->exploitedFinding()]);
        SecurityAlerts::check(notify: false);
        $this->artisan('cache:clear');

        $this->actingAs($this->superUser())
            ->get(cp_route('security-alerts.index'))
            ->assertOk()
            ->assertSee('Last checked')
            ->assertDontSee('No check has run yet')
            ->assertSee('livewire/livewire');
    }
}
