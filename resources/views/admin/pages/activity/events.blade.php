<div class="table-responsive"><table class="table"><thead><tr><th>When</th><th>Event</th><th>Page / resource</th><th>Details</th></tr></thead><tbody>
@forelse($events as $event)
    @php($details = json_decode($event->metadata ?: '{}', true))
    <tr>
        <td class="text-nowrap">{{ $event->occurred_at ?: 'Unknown time' }}
            @if($visit && $event->occurred_at)<br><small class="text-muted">+{{ round(\Carbon\Carbon::parse($visit->started_at)->diffInMilliseconds(\Carbon\Carbon::parse($event->occurred_at)) / 1000, 1) }} s in visit</small>@endif
            @if($event->elapsed_ms !== null)<br><small class="text-muted">+{{ round($event->elapsed_ms / 1000, 1) }} s on page</small>@endif
        </td>
        <td>{{ str_replace('_', ' ', $event->type) }}<br><small class="text-muted">{{ $event->source }} · {{ $event->platform }}@if($event->status) · HTTP {{ $event->status }}@endif</small></td>
        <td class="text-break">{{ $event->url }}@if($event->resource_type)<br>{{ $event->resource_type }} #{{ $event->resource_id }}@endif</td>
        <td>
            @if(isset($details['label']))<div>{{ $details['label'] }}</div>@endif
            @if(isset($details['position_seconds']))<div>{{ ucfirst($details['media_kind'] ?? 'Media') }} at {{ round($details['position_seconds'], 1) }} s · played {{ round($details['played_seconds'] ?? 0, 1) }} s</div>@endif
            @if($event->type === 'favorite_changed')<div>{{ !empty($details['result']) ? 'Added to favorites' : 'Removed from favorites' }}</div>@endif
            <details><summary>Recorded details</summary><pre class="small text-wrap text-break">{{ json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @if($event->source === 'redis')<p class="small">Original Redis field: {{ $event->legacy_field }}</p><pre class="small text-wrap text-break">{{ $event->legacy_payload }}</pre>@endif
            </details>
        </td>
    </tr>
@empty<tr><td colspan="4">No activity recorded.</td></tr>@endforelse
</tbody></table></div>
