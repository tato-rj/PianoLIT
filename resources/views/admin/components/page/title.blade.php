<div class="d-md-flex justify-content-between {{$mb ?? 'mb-4'}}">
	<div class="px2 d-flex align-items-center mb-3" id="page-title">
		<div class="bg-white rounded d-flex flex-center border me-3 hide-on-sm" style="width: 60px; height: 60px; flex-shrink: 0">
			@isset($theme)
				@switch($theme)
				@case('edit')
				<div>@icon('wrench', ['mr' => 0, 'color' => 'grey', 'size' => 'lg'])</div>
				@break

				@case('create')
				<div>@icon('wand-sparkles', ['mr' => 0, 'color' => 'grey', 'size' => 'lg'])</div>
				@break

				@default
				<div>@icon('', ['mr' => 0, 'color' => 'grey', 'size' => 'lg'])</div>
				@endswitch
			@else
				<div>@icon($icon, ['mr' => 0, 'color' => 'grey', 'size' => 'lg'])</div>
			@endif
		</div>
		<div class="title-text">
			<h5 class="m-0">{{$title}}</h5>
			<div class="text-muted">{{$subtitle}}</div>
		</div>
	</div>

	@isset($slot)
	<div class="mb-3">
		{{$slot}}
	</div>
	@endisset

	@isset($action)
    <div class="mb-3">
	@isset($action['url'])
	      <a href="{{$action['url']}}" class="btn btn-sm btn-primary">
	        @icon('plus'){{$action['label']}}
	      </a>
	    @endisset

	@isset($action['external_link'])
	      <a href="{{$action['external_link']}}" target="_blank" class="btn btn-sm btn-primary">
	        @icon('external-link'){{$action['label']}}
	      </a>
	    @endisset

	@isset($action['modal'])
	      <button data-bs-toggle="modal" data-bs-target="#{{$action['modal']}}" class="btn btn-sm btn-primary">
	        @icon('plus'){{$action['label']}}
	      </button>
	    @endisset
    </div>
	@endisset

	@isset($back)
		<div class="mb-3">
		@foreach($back as $label => $url)
	      <a href="{{$url}}" class="btn btn-sm btn-secondary text-uppercase">
	        @icon('circle-arrow-left'){{$label}}
	      </a>
	    @endforeach
		</div>
	@endisset
</div>