@if($freePick ?? false)
<article class="free-pick-card bg-align-center rounded overflow-hidden position-relative p-3 p-md-4 text-white" style="background-image: url('{{ $piece->image_background }}')">
	<div class="free-pick-card__content position-relative">
		<p class="d-flex align-items-center mb-3">
			@icon('circle', ['mr' => 2, 'classes' => 'color-' . $piece->level_name, 'filled' => true])
			<span>{{ ucfirst($piece->extended_level_name) }}</span>
		</p>
		{{-- @pill(['label' => 'FREE THIS WEEK', 'color' => 'primary', 'text' => 'white', 'classes' => 'mb-3 px-3 py-2']) --}}
		<h4 class="free-pick-card__title text-white mb-0">{{ $piece->collection_name === 'Sonata' ? $piece->getRawOriginal('name') : ($piece->nickname ?: $piece->getRawOriginal('name')) }}</h4>
		<p class="free-pick-card__metadata mb-3">@if(trim($piece->catalogue)){{ $piece->catalogue }} · @endif{{ $piece->attribution }}{{ $piece->composer->short_name }}</p>

		<a class="btn btn-secondary d-inline-flex align-items-center gap-3" href="{{ route('webapp.pieces.show', $piece) }}">Explore piece @icon('arrow-right', ['mr' => 0])</a>
		<ul class="free-pick-card__media small list-unstyled d-flex flex-wrap gap-4 mb-0 mt-4" aria-label="Available media">
			<li class="d-flex align-items-center">@icon('video', ['mr' => 2])Video</li>
			@if($piece->hasAudio())
			<li class="d-flex align-items-center">@icon('headphones', ['mr' => 2])Audio</li>
			@endif
			@if($piece->hasScore(true))
			<li class="d-flex align-items-center">@icon('file-text', ['mr' => 2])Score</li>
			@endif
			@if($piece->webapp_has_synthesia ?? $piece->hasTutorials(['synthesia']))
			<li class="d-flex align-items-center">@icon('flame', ['mr' => 2])Synthesia</li>
			@endif
		</ul>
	</div>
</article>
@else
<div class=" cursor-pointer bg-align-center rounded d-flex d-apart flex-column p-3 piece-card" role="img" aria-label="{{$piece->name}}" data-url="{{route('webapp.pieces.show', $piece)}}" style="background-image: url({{$piece->image_background}}); height: {{$height ?? '200px'}}; width: {{$width ?? '100%'}}">
	<div class="w-100 text-white">
		<p class="h6 m-0 text-white clamp-2">{{$piece->name}}</p>
		<p class="m-0 text-white">{{$piece->attribution}}{{$piece->composer->short_name}}</p>
	</div>
	<div class="w-100 text-white">
		@icon('circle', ['mr' => 1, 'classes' => 'align-middle color-' . $piece->level_name, 'filled' => true])
		<span><small>{{strtoupper($piece->extended_level_name)}}</small></span>
	</div>
</div>
@endif
