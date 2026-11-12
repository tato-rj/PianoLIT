<header class="pt-4 pb-3 d-flex align-items-center justify-content-between">
	<a class="navbar-brand" href="{{route('webapp.discover')}}">
		@brandIcon(['size' => 'clamp(48px, 7vw, 64px)', 'tilt' => true])
	</a>
</header>

@isset($title)
<div class="text-center mb-4">
    @if(!empty($pageHeading))
        <h1 class="h2">{{ $title }}</h1>
    @else
        <h2>{{ $title }}</h2>
    @endif
	<p style="max-width: 80%" class="mx-auto">{!! $subtitle ?? null !!}</p>
	{{$slot ?? null}}
</div>
@endisset
