@forelse($results as $result)
	@if($result['data']->count())
		@include('search.components.results.global.header')
		@include('search.components.results.global.models.' . strtolower($result['model']), ['data' => $result['data']])
	@endif
@empty
	@include('search.components.results.global.empty')
@endforelse