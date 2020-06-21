<div class="row mb-5 pb-5 border-bottom">
	<div class="col-12">
		<h5 class="mb-4">What's this eBook about?</h5>

	</div>
</div>

<div class="row mb-5">
	<div class="col-lg-6 col-md-8 col-10 mx-auto text-center">
		<div class="bg-light rounded p-5">
			<h4><u>Download your copy today!</u></h4>
			<p class="mb-4 lead"><i>{{$ebook->title}}</i> is ready for purchase.</p>
			<a href="#" class="btn btn-sm btn-wide btn-primary mb-1">@fa(['icon' => 'shopping-cart'])Buy now for {{$ebook->price_to_humans}}</a>
			<div class="text-muted"><small>You'll love it {{emoji('happy', 1)[0]}}!</small></div>
		</div>
	</div>
</div>