<div class="alert-container animated fadeInUp {{! empty($temporary) ? 'alert-temporary' : null}} d-flex justify-content-center w-100 alert-{{! empty($floating) ? $floating : null}}">
	<div class="alert rounded border-0 {{! empty($fullX) ? 'w-100' : null}} {{! empty($floating) ? 'm-0' : null}} alert-{{$color}} {{! empty($dismissible) ? 'alert-dismissible' : null}} fade show" role="alert">
		
		{!! $message !!}

		@isset($dismissible)
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
		@endisset
	</div>  
</div>