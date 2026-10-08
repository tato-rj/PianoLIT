@extends('webapp.layouts.app', ['title' => 'Latest pieces'])

@section('content')
@include('webapp.layouts.header', ['title' => 'Latest pieces', 'subtitle' => 'The newest additions to the repertoire'])

<div class="discover-latest discover-latest__grid">
	@forelse($pieces as $piece)
		@include('webapp.discover.cards.latest-piece')
	@empty
		<p class="text-muted">New pieces are on their way.</p>
	@endforelse
</div>
<div class="mt-4">{{ $pieces->links() }}</div>
@endsection
