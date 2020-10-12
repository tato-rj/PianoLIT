<div id="inner-subscribe-model" style="display: none;">
	<div class="border-top border-bottom py-6 my-5 text-center">
		<h4><strong>Would you like to read more about piano?</strong></h4>
		<p>Subscribe now and receive the latest news, stories, ideas and much more right in your inbox!</p>
		<form method="POST" action="{{route('subscriptions.store')}}">
			@csrf
			@include('components.form.subscription.hidden')
			<div class="form-row">
				<div class="col-lg-6 col-md-8 col-10 mx-auto">
					<div class="form-group">
						<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 input-light">
					</div>
					@include('components/form/error', ['field' => 'email'])
					<button type="submit" class="btn btn-primary shadow btn-block">JOIN NOW</button>
				</div>
			</div>
		</form>
	</div>
</div>