<?php

declare(strict_types=1);

namespace Ruvelo\StatamicSecurityAlerts;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Ruvelo\SecurityAlerts\Models\SecurityAlert;
use Ruvelo\SecurityAlerts\SecurityAlerts;
use Throwable;

/**
 * What the page and the widget show: the open alerts from the last check,
 * their counts, and whether that check is recent. Reads the database and the
 * cache only; it never calls an advisory feed.
 */
final readonly class Overview
{
    /** A scheduled check runs daily, so two days of silence means the scheduler stopped. */
    public const STALE_AFTER_HOURS = 48;

    /**
     * @param  Collection<int, SecurityAlert>  $alerts  Worst first
     * @param  array{checked_at: CarbonImmutable, packages: int, findings: int, exploited: int, complete: bool}|null  $lastCheck
     */
    public function __construct(
        public bool $installed,
        public Collection $alerts,
        public ?array $lastCheck,
    ) {}

    public static function current(): self
    {
        if (! self::tableExists()) {
            return new self(false, new Collection, null);
        }

        $alerts = SecurityAlerts::open();

        return new self(true, $alerts, SecurityAlerts::lastCheck() ?? self::lastCheckFromAlerts($alerts));
    }

    public function total(): int
    {
        return $this->alerts->count();
    }

    public function exploited(): int
    {
        return $this->alerts->filter(fn (SecurityAlert $alert): bool => $alert->isExploited())->count();
    }

    /**
     * Non-exploited findings by severity value (critical, high, ...), zeros left out.
     *
     * @return array<string, int>
     */
    public function bySeverity(): array
    {
        return $this->alerts
            ->reject(fn (SecurityAlert $alert): bool => $alert->isExploited())
            ->countBy(fn (SecurityAlert $alert): string => $alert->severity->value)
            ->all();
    }

    public function neverChecked(): bool
    {
        return $this->lastCheck === null;
    }

    public function isStale(): bool
    {
        return $this->lastCheck === null
            || $this->lastCheck['checked_at']->lt(CarbonImmutable::now()->subHours(self::STALE_AFTER_HOURS));
    }

    public function isClean(): bool
    {
        return $this->installed && $this->alerts->isEmpty();
    }

    /**
     * The last check is remembered in the cache, which a deploy may clear. The
     * alerts stay in the table, each stamped with the check that last saw it,
     * so open alerts still say when they were last confirmed.
     *
     * @param  Collection<int, SecurityAlert>  $alerts
     * @return array{checked_at: CarbonImmutable, packages: int, findings: int, exploited: int, complete: bool}|null
     */
    private static function lastCheckFromAlerts(Collection $alerts): ?array
    {
        $latest = $alerts->max(fn (SecurityAlert $alert): CarbonImmutable => $alert->last_seen_at);

        if (! $latest instanceof CarbonImmutable) {
            return null;
        }

        return [
            'checked_at' => $latest,
            'packages' => 0,
            'findings' => $alerts->count(),
            'exploited' => $alerts->filter(fn (SecurityAlert $alert): bool => $alert->isExploited())->count(),
            'complete' => true,
        ];
    }

    private static function tableExists(): bool
    {
        try {
            return Schema::hasTable((new SecurityAlert)->getTable());
        } catch (Throwable) {
            return false;
        }
    }
}
