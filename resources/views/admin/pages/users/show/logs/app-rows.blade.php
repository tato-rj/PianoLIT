@foreach($logs as $event)
  <tr>
    <td class="text-nowrap">{{ $event->occurred_at ? carbon($event->occurred_at)->format('M j, Y \\a\\t h:i A') : 'Unknown time' }}</td>
    <td class="dataTables_main_column text-break">{{ $event->url }}<br><small class="text-muted">{{ $event->method }} {{ $event->route_name }}</small></td>
    <td>
      <details><summary>Recorded details</summary><pre class="small text-wrap text-break">{{ json_encode(json_decode($event->metadata ?: '{}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
      @if($event->source === 'redis')<pre class="small text-wrap text-break">{{ $event->legacy_payload }}</pre>@endif
      </details>
    </td>
  </tr>
@endforeach
