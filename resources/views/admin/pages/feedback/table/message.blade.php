<div class="admin-feedback-message-text">@if($submission->message !== null && $submission->message !== ''){{ $submission->message }}@else<span class="text-muted">No message</span>@endif</div>
@if($submission->context)
<details class="small mt-2 text-muted">
    <summary>Context</summary>
    <dl class="mt-2 mb-0">
        @if(!empty($submission->context['url']))<dt>Page</dt><dd class="text-break"><a href="{{ $submission->context['url'] }}" target="_blank" rel="noopener noreferrer">{{ $submission->context['url'] }}</a></dd>@endif
        @if(!empty($submission->context['route']))<dt>Route</dt><dd>{{ $submission->context['route'] }}</dd>@endif
        @if(!empty($submission->context['piece_id']))<dt>Piece</dt><dd><a href="{{ route('webapp.pieces.show', $submission->context['piece_id']) }}" target="_blank" rel="noopener noreferrer">#{{ $submission->context['piece_id'] }}</a></dd>@endif
        @if(!empty($submission->context['composer_id']))<dt>Composer</dt><dd><a href="{{ route('webapp.composers.show', $submission->context['composer_id']) }}" target="_blank" rel="noopener noreferrer">#{{ $submission->context['composer_id'] }}</a></dd>@endif
        <dt>Device</dt><dd>{{ ucfirst($submission->context['device_type'] ?? 'Unknown') }} · {{ $submission->context['browser'] ?? 'Unknown' }} · {{ $submission->context['os'] ?? 'Unknown' }}</dd>
        @if($submission->user_id)<dt>Account</dt><dd>#{{ $submission->user_id }}</dd>@endif
    </dl>
</details>
@endif
