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
		<button class="btn px-0 text-nowrap mb-3" disabled>YOU'RE NOT REGISTERED</button>
		<ul class="list-flat li-mb-1">
			<li><strong style="font-size: 160%; line-height: 1" class="text-red">8</strong> results limit</li>
			<li><strong style="font-size: 160%; line-height: 1" class="text-red">2</strong> queries per day</li>
		</ul>
		@endcomponent

		@component('search.components.plan', ['color' => 'light'])
		<a href="{{route('webapp.discover')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-outline-primary text-nowrap btn-block mb-3">SIGN UP FOR FREE</a>
		<ul class="list-flat li-mb-3">
			<li><strong>Unlimited</strong> results</li>
			<li><strong>Unlimited</strong> queries per day</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'apple', 'fa_type' => 'b'])</span>iOS App</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'laptop'])</span>Desktop App</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'filter'])</span>Advanced filters</li>
		</ul>
		@endcomponent

		@component('search.components.plan', ['color' => 'light'])
		<a href="{{route('webapp.discover')}}" data-ios="{{config('app.stores.ios')}}" class="btn btn-primary text-nowrap btn-block mb-3">@fa(['icon' => 'crown'])GO PREMIUM</a>
		<ul class="list-flat li-mb-3">
			<li><strong>Unlimited</strong> results</li>
			<li><strong>Unlimited</strong> queries per day</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'apple', 'fa_type' => 'b'])</span>iOS App</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'laptop'])</span>Desktop App</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'filter'])</span>Advanced filters</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'file-alt'])</span>Get the score</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'video'])</span>Request video tutorials</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'heart'])</span>Organize your favorites</li>
			<li><span class="text-center d-inline-block mr-2" style="min-width: 20px;">@fa(['mr' => 0, 'color' => 'green', 'icon' => 'list-ul'])</span>Curated playlists</li>
		</ul>
		@endcomponent
	</div>
</section>