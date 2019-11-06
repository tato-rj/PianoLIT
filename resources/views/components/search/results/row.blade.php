@component('components.swiper', ['title' => [$playlist['title'], $playlist['tag']]])
	@foreach($playlist['content'] as $model)
	@include('components.search.results.galleries.' . $playlist['type'])
	@endforeach
	
	@if($playlist['type'] == 'piece')
	<div class="cursor-pointer p-0 mx-1">
		<div class="d-flex flex-center rounded bg-white border border-4 py-2 px-3 text-grey" 
		style="height: 188px; width: 188px; border-width: 8px!important" title="Click for more">
			<i class="fas fa-plus fa-3x opacity-6"></i>
		</div>
	</div>
	@endif
@endcomponent