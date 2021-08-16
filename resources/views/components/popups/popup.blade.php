@if(view()->exists('components.popups.'.$view))
	@include('components.popups.'.$view, ['data' => $data ?? null])
@endif