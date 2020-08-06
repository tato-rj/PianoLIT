@component('components.shop.forms.summary')
<h4 class="border-bottom mb-3 pb-3">You're almost there!</h4>
<div class="border-bottom mb-3 pb-3">
	<div class="row">
		<div class="col-lg-6 col-8 mx-auto position-relative">
			<img src="{{$product->shelf_image()}}" class="w-100">
			@include('components.shop.discount-tag', ['position' => 'left'])
		</div>
		<div class="col-lg-6 col-12 d-flex h-100">
			<div class="mt-3">
				<div>
					@include('components.shop.highlights')
				</div>
				<div class="d-flex">
					<div class="mr-2">
						<h4 class="m-0">Total in USD:</h4>
					</div>
					<div>
						@include('components.shop.price')
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div>
	<h4 class="clamp-2"><strong>{{$product->title}}</strong></h4>
	<p>{{$product->subtitle}}</p>
</div>
@endcomponent
