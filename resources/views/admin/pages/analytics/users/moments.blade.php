@foreach($items as $moment)
@if(isset($moment['section']))
<li class="ua-context"><section class="ua-context-card" aria-label="{{ $moment['section']['title'] }}">
    <div class="ua-context-heading"><h3>{{ $moment['section']['title'] }}</h3><time>{{ \App\Activity\Time::event($moment['event'])->format('g:i:s A') }}</time></div>
    @if($moment['children']->isNotEmpty())
    <ol class="ua-actions ua-context-actions">@include('admin.pages.analytics.users.moments', ['items' => $moment['children']])</ol>
    @else<p class="ua-meta">No further actions recorded.</p>@endif
    @if($moment['section']['closed'])<p class="ua-context-closed">Closed at {{ \App\Activity\Time::event($moment['section']['closed'])->format('g:i:s A') }}</p>@endif
    @if($moment['section']['opener'])
    @php($event = $moment['section']['opener'])
    @include('admin.pages.analytics.users.details')
    @endif
</section></li>
@else
@php($event = $moment['event'])
@if($moment['playback'])
<li>@include('admin.pages.analytics.users.playback', ['playback' => $moment['playback']])</li>
@continue
@endif
<li><span class="ua-action-icon" aria-hidden="true">@icon(\App\Activity\Presentation::icon($event->type, \App\Activity\Presentation::metadata($event)), ['mr' => 0])</span><div>
    <div class="ua-action-line"><strong>{{ \App\Activity\Presentation::action($event, $pieces) }}</strong><time>{{ \App\Activity\Time::event($event)->format('g:i:s A') }}</time></div>
    @if($event->resource_type === 'piece' && isset($pieces[$event->resource_id]) && (!$group['piece'] || $group['piece']->id != $event->resource_id))<a href="{{ route('admin.pieces.edit', $event->resource_id) }}">{{ $pieces[$event->resource_id]->name }}</a>@endif
    @php($m = \App\Activity\Presentation::metadata($event))
    @if($event->type === 'score_progress')<p class="ua-meta">Score visible for {{ \App\Activity\Time::duration($m['visible_ms'] ?? null) }} so far</p>@endif
    @if($event->type === 'annotation_saved')<p class="ua-meta">{{ $m['created_count'] ?? 0 }} created · {{ $m['edited_count'] ?? 0 }} edited · {{ $m['removed_count'] ?? 0 }} removed. Marking contents are private.</p>@endif
    @include('admin.pages.analytics.users.details')
</div></li>
@endif
@endforeach
