<div class="mb-2">
	@forelse($post->topics as $topic)
	<span class="text-blue mb-1 text-uppercase"><small><strong>{{$topic->name}}</strong></small>{{! $loop->last ? ' • ' : null}}</span>
	@empty
	<div class="text-blue mb-1 text-uppercase"><small><strong>TO READ</strong></small></div>
	@endforelse
</div>