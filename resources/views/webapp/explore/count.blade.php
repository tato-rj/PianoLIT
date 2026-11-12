<div class="text-center mb-3">
	<p class="m-0 text-nowrap">We found <strong>{{$count}} {{ str_plural('piece', $count) }}</strong></p>
	@if($count)
	<form class="mt-3" method="GET" action="{{route('webapp.search.results')}}">
		<input type="hidden" name="for" value="{{$query}}" data-search-term>
		<button type="submit" class="btn text-nowrap btn-primary btn-wide">@icon('lightbulb')See results</button>
	</form>
	@endif
</div>
