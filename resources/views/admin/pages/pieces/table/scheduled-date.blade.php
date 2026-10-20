@if($item->next_freepick_at)
    @php($scheduledFreepick = \Carbon\Carbon::parse($item->next_freepick_at, 'UTC')->setTimezone(config('app.timezone')))
    <time class="d-flex align-items-start" datetime="{{ $scheduledFreepick->toIso8601String() }}">
        <span>@icon('calendar-clock', ['color' => 'warning'])</span>
        <span>
            <span class="d-block">{{ $scheduledFreepick->format('M j, Y') }}</span>
            <small class="d-block">{{ $scheduledFreepick->format($scheduledFreepick->minute ? 'g:ia' : 'ga') }}</small>
        </span>
    </time>
@endif
