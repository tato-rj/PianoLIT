<div class="position-relative" style="overflow: hidden;">
	<div class="bg-light position-absolute w-100 h-100" style="top: 0; left: 30%; z-index: -1"></div>
	<div class="text-center px-3 pt-5 pb-6">
		<div class="mb-2">
			<h5 class="m-0 text-primary">Piano<strong>LIT</strong></h5>
			<p class="font-cursive mb-0" style="font-size: 4em; margin-top: -30px;">eBooks</p>
		</div>
		<div class="px-4 mb-4">
			@include('shop.components.cover', ['product' => $ad_product, 'maxWidth' => '300px'])
		</div>
		<div class="mb-5">
			<p>{{$ad_product->subtitle}}</p>
			<h6>{{$ad_product->title}}</h6>
		</div>
		<div>
			<a href="{{$ad_product->showRoute()}}" class="link-none border-bottom py-3 py-2 border-1x border-dark text-nowrap" style="letter-spacing: 3px">
				DOWNLOAD NOW @fa(['icon' => 'chevron-right', 'mr' => 0])
			</a>
		</div>
	</div>
</div>