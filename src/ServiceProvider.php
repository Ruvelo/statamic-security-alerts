<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts;

use Ruvelo\StatamicSecurityAlerts\Widgets\SecurityAlertsWidget;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public const PERMISSION = 'view security alerts';

    /** A shield with an exclamation mark (Lucide, ISC licence). Inline, because Statamic 5 and 6 ship different icon sets. */
    private const ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>';

    protected $widgets = [
        SecurityAlertsWidget::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $stylesheets = [
        __DIR__.'/../resources/css/security-alerts.css',
    ];

    protected $viewNamespace = 'statamic-security-alerts';

    protected $config = false;

    protected $translations = false;

    public function bootAddon(): void
    {
        Permission::extend(function (): void {
            Permission::register(self::PERMISSION)
                ->label('View security alerts')
                ->description('See vulnerable packages on the Security alerts page and dashboard widget, and run a check');
        });

        Nav::extend(function ($nav): void {
            $nav->tools('Security alerts')
                ->route('security-alerts.index')
                ->icon(self::ICON)
                ->can(self::PERMISSION);
        });
    }
}
