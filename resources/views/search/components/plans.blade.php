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
		@component('search.components.plan')
		@slot('button')
		<button class="btn px-0 text-nowrap mb-3" disabled>YOU'RE NOT REGISTERED</button>
		@endslot
		@slot('list')
		<ul class="list-flat li-mb-1">
			<li><strong style="font-size: 160%; line-height: 1" class="text-red">8</strong> results limit</li>
			<li><strong style="font-size: 160%; line-height: 1" class="text-red">2</strong> queries per day</li>
		</ul>
		@endslot
		@endcomponent

		@component('search.components.plan', ['color' => 'light', 
			'items' => [
				'<span class="text-primary">Unlimited</span> results',
				'<span class="text-primary">Unlimited</span> queries per day',
				'iOS App',
				'Desktop App',
				'Advanced filters',]])

		@slot('button')
		<a href="{{route('webapp.discover')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-outline-primary text-nowrap btn-block mb-3">SIGN UP FOR FREE</a>
		@endslot
		@endcomponent

		@component('search.components.plan', ['color' => 'light', 
			'items' => [
				'<span class="text-primary">Unlimited</span> results',
				'<span class="text-primary">Unlimited</span> queries per day',
				'iOS App',
				'Desktop App',
				'Advanced filters',
				'Get the score',
				'Request video tutorials',
				'Save and organize your favorites',
				'Curated playlists']])
		
		@slot('button')
		<a href="{{route('webapp.membership.pricing')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-primary text-nowrap btn-block mb-3">@fa(['icon' => 'crown'])GO PREMIUM</a>
		@endslot
		@endcomponent
	</div>
</section>