@foreach(auth()->user()->suggestions(20)->shuffle()->take(10) as $piece)
	@include('webapp.components.piece', compact('hasFullAccess'))
@endforeach

