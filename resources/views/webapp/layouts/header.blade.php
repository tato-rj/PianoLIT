<div class="pt-4 pb-3">
	<a class="navbar-brand" href="{{route('webapp.discover')}}">
		<img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 60px">
	</a>
</div>

<div class="text-center mb-4">
	<h2>{{$title ?? null}}</h2>
	<p style="max-width: 80%" class="mx-auto">{!! $subtitle ?? null !!}</p>
	{{$slot ?? null}}
</div>