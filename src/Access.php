<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts;

use Statamic\Facades\User;

/**
 * Who may see security alerts: super users, and anyone whose role grants
 * the "view security alerts" permission.
 */
final class Access
{
    public static function allows(): bool
    {
        $user = User::current();

        return $user !== null && ($user->isSuper() || $user->hasPermission(ServiceProvider::PERMISSION));
    }
}
