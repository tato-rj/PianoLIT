@if(view()->exists('components.promotions.popups.'.$view))
	@include('components.promotions.popups.'.$view, ['data' => $data ?? null])
@endif