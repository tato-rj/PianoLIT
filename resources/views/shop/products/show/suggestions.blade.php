<div class="row">
	<div class="col-12 text-center">
		<h5 class="mb-4">You might also be interested in</h5>
	</div>
	@each('components.shop.display.sm', $product->suggestions()->get(), 'suggestion')
</div>