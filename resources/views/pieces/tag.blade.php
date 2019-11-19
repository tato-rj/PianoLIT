<a href="{{route('search.index', ['global', 'search' => $tag->name])}}" 
	target="_blank" 
	title="Find more pieces about this" class="align-text-bottom link-{{$color}} tag rounded-pill alert-{{$color}} m-1 px-3 py-1">
	<strong>{{$tag->name}}</strong>
</a>