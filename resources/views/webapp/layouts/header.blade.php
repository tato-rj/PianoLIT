<div class="pt-4 pb-3 d-flex align-items-center justify-content-between">
	<a class="navbar-brand" href="{{route('webapp.discover')}}">
		@icon
	</a>
	@guest('web')
	<a href="{{ route('login') }}" class="btn btn-outline-secondary rounded-pill">Sign in</a>
	@endguest
</div>

<div class="text-center mb-4">
	<h2>{{$title ?? null}}</h2>
	<p style="max-width: 80%" class="mx-auto">{!! $subtitle ?? null !!}</p>
	{{$slot ?? null}}
</div>
