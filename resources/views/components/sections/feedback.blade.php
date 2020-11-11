@if($full ?? true)
	<div class="row">
		<div class="col-lg-8 mx-auto border-top border-bottom mb-6 text-center p-5">
			<div style="max-width: 400px" class="mx-auto">
				<h4 class="mb-3">Did you find this useful?</h4>
				<p>Subscribe to our newsletter and be the first one to know when a new tool like this one is out!</p>
				@include('components.form.subscription', ['id' => 'feedback-subscription-form'])
			</div>
		</div>
	</div>
@endif