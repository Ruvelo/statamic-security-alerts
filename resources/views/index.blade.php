@extends('statamic::layout')

@section('title', 'Security alerts')

@section('content')
    @php
        $last = $overview->lastCheck;
        $safeUrl = fn (?string $url): ?string => $url !== null && preg_match('#^https?://#i', $url) ? $url : null;
        $summary = $last
            ? 'Last checked '.$last['checked_at']->diffForHumans()
                .($last['packages'] > 0 ? ', '.number_format($last['packages']).' '.\Illuminate\Support\Str::plural('package', $last['packages']).' looked at.' : '.')
            : 'Never checked.';
    @endphp

    <div class="sa sa--page">
        <div class="sa-head">
            <div>
                <h1 class="sa-title">Security alerts</h1>
                <p class="sa-meta">
                    @if ($last)
                        {{ $summary }}
                    @else
                        Never checked.
                    @endif
                </p>
            </div>

            @if ($overview->installed)
                <form method="POST" action="{{ cp_route('security-alerts.check') }}">
                    @csrf
                    <button type="submit" class="sa-btn">Check now</button>
                </form>
            @endif
        </div>

        @if (session('security-alerts.status'))
            <div class="sa-notice sa-notice--ok" role="status">{{ session('security-alerts.status') }}</div>
        @endif

        @if (session('security-alerts.error'))
            <div class="sa-notice sa-notice--danger" role="alert">{{ session('security-alerts.error') }}</div>
        @endif

        @foreach ((array) session('security-alerts.problems', []) as $problem)
            <div class="sa-notice sa-notice--warn" role="alert">{{ $problem }}</div>
        @endforeach

        @if (! $overview->installed)
            <div class="sa-notice sa-notice--warn" role="alert">
                <div><strong>The alerts table is missing.</strong> Run <code>php artisan migrate</code>, then check again.</div>
            </div>
        @elseif ($overview->neverChecked())
            <div class="sa-notice sa-notice--warn" role="alert">
                <div><strong>No check has run yet.</strong> Press Check now, or run <code>php artisan security-alerts:check --notify</code>. The scheduler runs it daily once <code>php artisan schedule:run</code> is set up to run every minute.</div>
            </div>
        @elseif ($overview->isStale())
            <div class="sa-notice sa-notice--warn" role="alert">
                <div><strong>This is out of date.</strong> The last check ran {{ $last['checked_at']->diffForHumans() }}. Is the scheduler running? Statamic sites need <code>php artisan schedule:run</code> every minute.</div>
            </div>
        @elseif (! $last['complete'])
            <div class="sa-notice sa-notice--warn" role="alert">
                <div><strong>The last check was incomplete.</strong> A lockfile or an advisory feed could not be read, so some packages may not have been checked.</div>
            </div>
        @endif

        @if ($overview->total() > 0)
            @include('statamic-security-alerts::partials.summary')

            <div class="sa-panel">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th scope="col">Status</th>
                            <th scope="col">Package</th>
                            <th scope="col">Installed</th>
                            <th scope="col">Fixed in</th>
                            <th scope="col">Advisory</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($overview->alerts as $alert)
                            <tr @class(['is-exploited' => $alert->isExploited()])>
                                <td data-label="Status">
                                    @if ($alert->isExploited())
                                        <span class="sa-badge sa-badge--exploited">Exploited (KEV)</span>
                                    @else
                                        <span class="sa-badge sa-badge--{{ $alert->severity->value }}">{{ $alert->severity === \Ruvelo\SecurityAlerts\Severity::Unknown ? 'Unrated' : $alert->severity->label() }}</span>
                                    @endif
                                </td>
                                <td data-label="Package">
                                    <span class="sa-pkg">{{ $alert->package }}</span>
                                    @if ($alert->ecosystem === \Ruvelo\SecurityAlerts\Ecosystem::Npm)<span class="sa-tag">npm</span>@endif
                                    @if ($alert->dev)<span class="sa-tag">dev</span>@endif
                                </td>
                                <td data-label="Installed">{{ $alert->version }}</td>
                                <td data-label="Fixed in">
                                    @if ($alert->fixed_in)
                                        <span class="sa-fix">{{ $alert->fixed_in }}</span>
                                    @else
                                        <span class="sa-sub">None known</span>
                                    @endif
                                </td>
                                <td data-label="Advisory">
                                    {{ $alert->title }}
                                    <span class="sa-sub">
                                        {{ $alert->advisory_id }}@if ($alert->cve) · {{ $alert->cve }}@endif
                                        @if ($url = $safeUrl($alert->link))
                                            · <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">Read the advisory</a>
                                        @endif
                                    </span>
                                    @if ($alert->isExploited())
                                        <span class="sa-kev">
                                            In CISA KEV since {{ $alert->exploited_at?->toDateString() }}@if ($alert->kev_due_date), patch by {{ $alert->kev_due_date->toDateString() }}@endif
                                            @if ($alert->ransomware) · used in ransomware campaigns @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($overview->isClean() && ! $overview->neverChecked())
            <div class="sa-panel">
                <div class="sa-empty">
                    <span class="sa-empty-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <h2>All clear</h2>
                    <p>No known vulnerabilities in the {{ number_format($last['packages']) }} packages this site runs.</p>
                </div>
            </div>
        @endif

        <p class="sa-foot">Advisories come from Packagist, OSV.dev and CISA's Known Exploited Vulnerabilities catalogue. Only package names and versions leave your server.</p>
    </div>
@endsection
