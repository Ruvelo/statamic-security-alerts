@php
    $last = $overview->lastCheck;
    $exploited = $overview->exploited();
    $total = $overview->total();
@endphp

<div class="sa sa-widget sa--card">
    <h2 class="sa-card-title">Security alerts</h2>

    <div class="sa-widget-top">
        <div>
            @if (! $overview->installed)
                <div class="sa-widget-num">Not set up</div>
                <p class="sa-widget-line">Run <code>php artisan migrate</code> to create the alerts table.</p>
            @elseif ($overview->neverChecked())
                <div class="sa-widget-num">Never checked</div>
                <p class="sa-widget-line">Run <code>php artisan security-alerts:check --notify</code> or open the page and press Check now.</p>
            @elseif ($total === 0)
                <div class="sa-widget-num sa-widget-num--clear">All clear</div>
                <p class="sa-widget-line">No known vulnerabilities in {{ number_format($last['packages']) }} packages.</p>
            @elseif ($exploited > 0)
                <div class="sa-widget-num sa-widget-num--exploited">{{ $exploited }} exploited in the wild</div>
                <p class="sa-widget-line">{{ $total }} open {{ \Illuminate\Support\Str::plural('alert', $total) }} in all. Patch the exploited ones today.</p>
            @else
                <div class="sa-widget-num">{{ $total }} open {{ \Illuminate\Support\Str::plural('alert', $total) }}</div>
                <p class="sa-widget-line">None is known to be exploited yet.</p>
            @endif
        </div>
        <a href="{{ cp_route('security-alerts.index') }}" style="white-space: nowrap;">View all</a>
    </div>

    @if ($overview->installed && $total > 0)
        <div style="margin-top: 12px;">@include('statamic-security-alerts::partials.summary')</div>
    @endif

    @if ($overview->installed && ! $overview->neverChecked())
        <p class="sa-widget-line">Last checked {{ $last['checked_at']->diffForHumans() }}.</p>
        @if ($overview->isStale())
            <div class="sa-widget-stale" role="alert">No check for over {{ \Ruvelo\StatamicSecurityAlerts\Overview::STALE_AFTER_HOURS }} hours. Is the scheduler running?</div>
        @endif
    @endif
</div>
