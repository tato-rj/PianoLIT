<div class="row">
	<div class="col-12 text-center">
		<h5 class="mb-4">You might also be interested in</h5>
	</div>
	@each('shop.components.display.sm', $product->suggestions()->get(), 'suggestion')
</div>