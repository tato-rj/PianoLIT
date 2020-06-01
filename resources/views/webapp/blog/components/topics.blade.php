<div class="mb-2">
	@forelse($post->topics as $topic)
	<span class="text-blue mb-1 text-uppercase">
		<small><strong>{{$topic->name}}</strong></small>
	@if(! $loop->last)
	•
	@endif
	</span>
	@empty
	<div class="text-blue mb-1 text-uppercase"><small><strong>TO READ</strong></small></div>
	@endforelse
</div>