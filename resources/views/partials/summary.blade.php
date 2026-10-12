<div class="sa-stats">
    @if ($overview->exploited() > 0)
        <span class="sa-chip sa-chip--exploited"><b>{{ $overview->exploited() }}</b> exploited in the wild</span>
    @endif
    @foreach ($overview->bySeverity() as $severity => $count)
        <span class="sa-chip"><b>{{ $count }}</b> {{ $severity === 'unknown' ? 'unrated' : $severity }}</span>
    @endforeach
</div>
