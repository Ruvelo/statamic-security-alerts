<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts\Tests\Feature;

use Carbon\CarbonImmutable;
use Ruvelo\SecurityAlerts\SecurityAlerts;
use Ruvelo\StatamicSecurityAlerts\ServiceProvider;
use Ruvelo\StatamicSecurityAlerts\Tests\TestCase;
use Ruvelo\StatamicSecurityAlerts\Widgets\SecurityAlertsWidget;
use Statamic\Facades\Permission;
use Statamic\Facades\Role;
use Statamic\Facades\User;

class WidgetAndPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
    }

    private function widgetHtml(): string
    {
        return (string) app(SecurityAlertsWidget::class)->html();
    }

    public function test_the_widget_is_registered(): void
    {
        $this->assertSame(SecurityAlertsWidget::class, app('statamic.widgets')->get('security_alerts'));
    }

    public function test_the_widget_is_red_when_something_is_exploited(): void
    {
        $this->fakeReport([$this->exploitedFinding(), $this->mediumFinding()]);
        SecurityAlerts::check(notify: false);
        $this->actingAs($this->superUser());

        $html = $this->widgetHtml();

        $this->assertStringContainsString('class="sa-widget-num sa-widget-num--exploited"', $html);
        $this->assertStringContainsString('1 exploited in the wild', $html);
        $this->assertStringContainsString('Last checked', $html);
        $this->assertStringNotContainsString('Is the scheduler running', $html);
    }

    public function test_the_widget_is_not_red_without_an_exploited_finding(): void
    {
        $this->fakeReport([$this->mediumFinding()]);
        SecurityAlerts::check(notify: false);
        $this->actingAs($this->superUser());

        $html = $this->widgetHtml();

        $this->assertStringNotContainsString('class="sa-widget-num sa-widget-num--exploited"', $html);
        $this->assertStringContainsString('1 open alert', $html);
    }

    public function test_the_widget_says_all_clear(): void
    {
        $this->fakeReport([]);
        SecurityAlerts::check(notify: false);
        $this->actingAs($this->superUser());

        $this->assertStringContainsString('All clear', $this->widgetHtml());
    }

    public function test_the_widget_warns_after_48_hours(): void
    {
        $this->fakeReport([]);
        SecurityAlerts::check(notify: false);
        $this->actingAs($this->superUser());

        $this->travelTo(CarbonImmutable::now()->addHours(47));
        $this->assertStringNotContainsString('Is the scheduler running', $this->widgetHtml());

        $this->travelTo(CarbonImmutable::now()->addHours(2));
        $this->assertStringContainsString('No check for over 48 hours. Is the scheduler running?', $this->widgetHtml());
    }

    public function test_the_widget_renders_nothing_without_the_permission(): void
    {
        $this->actingAs($this->cpUser());

        $this->assertNull(app(SecurityAlertsWidget::class)->html());
    }

    public function test_the_widget_appears_on_the_dashboard_when_configured(): void
    {
        config()->set('statamic.cp.widgets', [['type' => 'security_alerts', 'width' => 50]]);
        $this->fakeReport([$this->exploitedFinding()]);
        SecurityAlerts::check(notify: false);

        $this->actingAs($this->superUser())
            ->get(cp_route('dashboard'))
            ->assertOk()
            ->assertSee('1 exploited in the wild', false);
    }

    public function test_the_permission_is_registered(): void
    {
        Permission::boot();

        $this->assertTrue(Permission::all()->contains(fn ($permission) => $permission->value() === ServiceProvider::PERMISSION));
    }

    public function test_a_role_with_the_permission_can_open_the_page(): void
    {
        $user = $this->plainUser();
        $user->assignRole($this->role());

        $this->actingAs(User::find($user->id()))
            ->get(cp_route('security-alerts.index'))
            ->assertOk();
    }

    private function role(): \Statamic\Contracts\Auth\Role
    {
        return tap(Role::make('security')->title('Security')->permissions([ServiceProvider::PERMISSION, 'access cp']))->save();
    }
}
