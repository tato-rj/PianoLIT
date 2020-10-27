<section class="container mb-5">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-12 m-auto">
			<div class="text-center">
				<h2>Need more results?</h2>
				<p>Sign up for free or try our premium plan for 7 days</p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-8 col-md-9 col-12 mx-auto row">
			@component('search.components.plan', ['color' => 'light', 
				'items' => [
					'iOS App',
					'Desktop App',
					'Advanced filters',]])

			@slot('button')
			<a href="{{route('webapp.discover')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-outline-primary text-nowrap btn-block mb-3">SIGN UP FOR FREE</a>
			@endslot
			@endcomponent

			@component('search.components.plan', ['color' => 'light', 
				'items' => [
					'iOS App',
					'Desktop App',
					'Advanced filters',
					'Get the score',
					'Watch videos',
					'Request video tutorials',
					'Save and organize your favorites',
					'Speed up/slow down audio',
					'Separate hands recordings',
					'Curated playlists']])
			
			@slot('button')
			<a href="{{route('webapp.membership.pricing')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-primary text-nowrap btn-block mb-3">@fa(['icon' => 'crown'])GO PREMIUM</a>
			@endslot
			@endcomponent
		</div>
	</div>
</section>