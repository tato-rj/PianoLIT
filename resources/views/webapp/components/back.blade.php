@unless($inline ?? false)
<div class="position-absolute d-flex align-items-center h-100" style="left: 0; top:0; font-size: 1.44em">
@endunless
	<a class="btn-raw link-none{{ ($inline ?? false) ? ' piece-header-back' : '' }}" href="{{route('webapp.discover')}}" data-history-back aria-label="Go back" >
	@icon('chevron-left', ($inline ?? false) ? ['mr' => 0] : [])</a>
@unless($inline ?? false)
</div>
@endunless
