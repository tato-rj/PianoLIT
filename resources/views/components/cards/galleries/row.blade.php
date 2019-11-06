@component('components.swiper', ['title' => [$playlist['title'], $playlist['tag']]])
	@foreach($playlist['content'] as $model)
	@include('components.cards.galleries.' . $playlist['type'])
	@endforeach
@endcomponent