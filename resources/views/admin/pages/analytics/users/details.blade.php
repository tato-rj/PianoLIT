<details class="ua-details">
    <summary>Details</summary>
    <dl class="ua-technical">
        <dt>Exact time</dt><dd>{{ \App\Activity\Time::event($event)->toIso8601String() }} @if(!$event->occurred_at) (receipt time; occurrence unknown) @endif</dd>
        <dt>Original event</dt><dd>{{ $event->type }}</dd>
        <dt>Platform / source</dt><dd>{{ \App\Activity\Presentation::platform($event->platform) }} / {{ $event->source }}</dd>
        @if($event->status)<dt>HTTP response</dt><dd>{{ $event->status }}</dd>@endif
        <dt>Resource</dt><dd>{{ $event->resource_type ?: 'Not recorded' }} {{ $event->resource_id }}</dd>
    </dl>
    @if($safeMetadata = \App\Activity\Presentation::metadata($event))<pre>{{ json_encode($safeMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
</details>
