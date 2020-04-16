@forelse($results as $result)
	@if($result['data']->count())
		@include('components.search.results.global.header')
		@include('components.search.results.global.models.' . $result['model'], ['data' => $result['data']])
	@endif
@empty
	@include('components.search.results.global.empty')
@endforelse