@if($pending = auth()->user()->pendingTutorialRequests()->exists())
<div class="mb-4">
@foreach(auth()->user()->pendingTutorialRequests as $request)
	@component('webapp.components.piece', ['piece' => $request->piece, 'hasFullAccess' => $hasFullAccess])
		@button(['label' => '<i class="fas fa-hourglass-half"></i> Pending', 'theme' => 'warning', 'size' => 'sm'])
	@endcomponent
@endforeach
</div>
@endif

@if($published = auth()->user()->publishedTutorialRequests()->exists())
<div class="mb-4">
@foreach(auth()->user()->publishedTutorialRequests as $request)
	@component('webapp.components.piece', ['piece' => $request->piece, 'hasFullAccess' => $hasFullAccess])
		<p class="m-0 text-success">@fa(['icon' => 'check-circle', 'color' => 'success'])Published on {{$request->published_at->toFormattedDateString()}}</p>
	@endcomponent
@endforeach
</div>
@endif

@if(! $pending && ! $published)
@include('webapp.components.empty', [
	'icon' => 'empty-tutorial-requests', 
	'title' => 'No tutorials yet', 
	'subtitle' => 'Start requesting tutorials for any piece available in PianoLIT!'])
@endif