<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts\Widgets;

use Illuminate\Contracts\View\View;
use Ruvelo\StatamicSecurityAlerts\Access;
use Ruvelo\StatamicSecurityAlerts\Overview;
use Statamic\Widgets\Widget;

class SecurityAlertsWidget extends Widget
{
    public function html(): ?View
    {
        // Widgets that render nothing are left off the dashboard.
        if (! Access::allows()) {
            return null;
        }

        /** @var view-string $view */
        $view = 'statamic-security-alerts::widget';

        return view($view, ['overview' => Overview::current()]);
    }
}
