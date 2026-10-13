<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Ruvelo\SecurityAdvisories\Exceptions\SecurityAdvisoriesException;
use Ruvelo\SecurityAlerts\Exceptions\SecurityAlertsException;
use Ruvelo\SecurityAlerts\SecurityAlerts;
use Ruvelo\StatamicSecurityAlerts\Access;
use Ruvelo\StatamicSecurityAlerts\Overview;
use Statamic\Http\Controllers\CP\CpController;

class SecurityAlertsController extends CpController
{
    public function index(): View
    {
        $this->authorizeView();

        /** @var view-string $view */
        $view = 'statamic-security-alerts::index';

        return view($view, [
            'title' => 'Security alerts',
            'overview' => Overview::current(),
        ]);
    }

    public function check(): RedirectResponse
    {
        $this->authorizeView();

        try {
            $result = SecurityAlerts::check(fresh: true);
        } catch (SecurityAlertsException|SecurityAdvisoriesException $e) {
            return redirect(cp_route('security-alerts.index'))->with('security-alerts.error', $e->getMessage());
        }

        $report = $result->report;
        Overview::rememberSource($report);
        $message = $report->isClean()
            ? 'Checked '.Str::plural('package', $report->packageCount(), true).': no known vulnerabilities.'
            : Str::plural('vulnerability', count($report->findings), true).' found in '.Str::plural('package', count($report->affectedPackages()), true).'.';

        $redirect = redirect(cp_route('security-alerts.index'))->with('security-alerts.status', $message);

        return $report->isComplete() ? $redirect : $redirect->with('security-alerts.problems', $report->problems);
    }

    private function authorizeView(): void
    {
        abort_unless(Access::allows(), 403);
    }
}
