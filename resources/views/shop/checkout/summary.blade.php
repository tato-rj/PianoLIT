@component('shop.components.forms.summary')
<div class="mb-2 text-muted"><small>@fa(['icon' => 'question-circle'])Need help or have a question? <a class="link-blue" href="mailto:{{config('app.emails.general')}}?subject=Help with a purchase">Let us know</a></small></div>
<h4 class="border-bottom mb-3 pb-3">You're almost there!</h4>

<div class="mb-4">
	<div class="row">
		<div class="col-lg-6 col-8 mx-auto">
			@include('shop.components.cover')
		</div>
		<div class="col-lg-6 col-12 mt-3">
			<h4 class="clamp-2 mb-3"><strong>{{$product->title}}</strong></h4>
			<div class="mb-3">
				@include('shop.components.details')
			</div>
			<div class="border-y py-2">
				@include('shop.components.price')
			</div>
		</div>
	</div>
</div>

<div>
	<h6 class="clamp-2"><strong>About this product</strong></h6>
	<p>{{$product->subtitle}}</p>
</div>
@endcomponent
