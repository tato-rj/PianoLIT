@if($waiting = auth()->user()->performances()->waiting()->get())
<div class="mb-4">
@foreach($waiting as $performance)
	@component('webapp.components.piece', ['piece' => $performance->piece, 'hasFullAccess' => $hasFullAccess])
		<p class="m-0 text-warning">@fa(['icon' => 'hourglass-half'])Uploaded on {{$performance->created_at->toFormattedDateString()}}</p>
	@endcomponent
@endforeach
</div>
@endif

@if($performances = auth()->user()->performances()->approved()->get())
<div class="mb-4">
@foreach($performances as $performance)
	@component('webapp.components.piece', ['piece' => $performance->piece, 'hasFullAccess' => $hasFullAccess])
		<p class="m-0 text-success">@fa(['icon' => 'check-circle', 'color' => 'success'])Published on {{$performance->approved_at->toFormattedDateString()}}</p>
	@endcomponent
@endforeach
</div>
@endif

@if($waiting->isEmpty() && $performances->isEmpty())
@include('webapp.components.empty', [
	'icon' => 'empty-tutorial-requests', 
	'title' => 'No uploads yet', 
	'subtitle' => 'You can now upload your own performance of any piece available in PianoLIT!'])
@endif