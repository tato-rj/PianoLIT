<form method="GET" action="{{route('webapp.search.results', ['lazy-load'])}}" id="search-form">
  <div class="input-icon">
    @icon('search', ['color' => 'grey', 'size' => 'lg'])
    <input type="text" name="search" value="{{request('search')}}" class="form-control search-input pianolit-input w-100" aria-label="{{ $searchLabel ?? 'Search repertoire' }}" placeholder="{{ $searchPlaceholder ?? 'Search here...' }}">
    @if(!empty($directorySearch) || !empty($accessibleSearch))
    <button type="button" data-erase="search" class="btn-raw input-erase position-absolute p-1 px-2 text-dark" aria-label="{{ !empty($directorySearch) ? 'Clear composer search' : 'Clear search' }}" style="display: none;">&times;</button>
    @else
    <div data-erase="search" class="input-erase position-absolute cursor-pointer p-1 px-2 text-dark" style="display: none;">&times;</div>
    @endif

    @if(empty($directorySearch) || !empty($composerControls))
    <button type="button" class="btn-raw search-controls-toggle" data-bs-toggle="offcanvas" data-bs-target="#{{ !empty($composerControls) ? 'composer-controls' : 'search-controls' }}" aria-controls="{{ !empty($composerControls) ? 'composer-controls' : 'search-controls' }}" aria-label="Sort and filter {{ !empty($composerControls) ? 'composers' : 'pieces' }}" @if(!empty($composerControls)) data-composer-controls hidden @endif>
      @icon('sliders-horizontal', ['mr' => 0])
    </button>
    @endif
  </div>
</form>

<div id="most-recent" class="text-center mt-1" style="display: none;">
	<p class="mb-1 text-muted"><small>Most recent searches...</small></p>
	<div class="d-flex flex-wrap justify-content-center"></div>
</div>

@if(!empty($composerControls))
    @push('page-navigation')
        @include('webapp.composers.controls')
    @endpush
@elseif(empty($directorySearch))
    @once
        @push('page-navigation')
            @include('webapp.search.controls')
        @endpush
    @endonce
@endif
