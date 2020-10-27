@foreach($pieces as $piece)
	@include('webapp.components.piece', compact('hasFullAccess'))
@endforeach