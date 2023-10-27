@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')
<div class="text-center mb-3 position-relative">
	@include('webapp.components.back')
	
	<p class="text-muted mb-1"><small><i>last updated on {{$folder->updated_at->toFormattedDateString()}}</i></small></p>
	<h3 class="px-3">@fa(['icon' => 'folder-open', 'color' => 'grey']){{$folder->name}}</h3>
	@include('webapp.user.my-pieces.favorites.folders.pieces-count')
</div>

<div class="mb-4"> 
@include('webapp.user.my-pieces.favorites.folders.pdf')
</div>

{{-- @include('webapp.components.sorting', ['disabled' => false, 'env' => 'local']) --}}

<div class="mt-3 favorites-container" data-url-reorder="{{route('api.users.favorites.folders.reorder', ['user_id' => auth()->user()->id, 'folder_id' => $folder->id])}}">
	@foreach($folder->favorites as $favorite)
		@component('components.draggable.cards.small', ['model' => $favorite])

		<span class="badge align-text-bottom badge-pill bg-{{$favorite->piece->level_name}}-raw"">{{$favorite->order + 1}}</span>

		<span style="user-select: none;">{{$favorite->piece->name}} <small class="text-muted">&middot; {{$favorite->piece->composer->short_name}}</small></span>


		@slot('controls')
			<div class="d-flex">
				<button class="btn-raw t-2 mr-1" id="flag-{{$favorite->piece->id}}" data-submit="favorite" data-target="#flag-{{$favorite->piece->id}}" data-url="{{route('webapp.users.favorites.update', ['piece' => $favorite->piece, 'folder_id' => $folder->id])}}" data-favorited="true" style="font-size: 120%" title="Remove from this folder">
					@fa(['icon' => 'heart', 'fa_type' => 's', 'color' => 'red'])
				</button>

				<a class="btn btn-sm btn-primary text-nowrap" href="{{route('webapp.pieces.show', $favorite->piece)}}">@fa(['icon' => 'arrow-right', 'mr' => 0])</a>
			</div>
		@endslot
		@endcomponent
	@endforeach
</div>

{{-- <section id="pieces-list">
	@forelse($folder->favorites as $favorite)
		@component('webapp.components.piece', ['piece' => $favorite->piece, 'hasFullAccess' => $hasFullAccess])
		<button class="btn-raw t-2" id="flag-{{$favorite->piece->id}}" data-submit="favorite" data-target="#flag-{{$favorite->piece->id}}" data-url="{{route('webapp.users.favorites.update', ['piece' => $favorite->piece, 'folder_id' => $folder->id])}}" data-favorited="true" style="font-size: 120%" title="Remove from this folder">
			@fa(['icon' => 'heart', 'fa_type' => 's', 'color' => 'red'])
		</button>
		@endcomponent
	@empty
		<h5 class="text-grey text-center mt-6 mb-3"><i>This folder is emtpy</i></h5>
	@endforelse
</section> --}}

@endsection

@push('scripts')
<script type="text/javascript">
$('div.favorites-container').each(function() {
  let $tab = $(this);
  $tab.sortable({
  	mobile: true,
    handle: '.sort-handle',
    update: function(element) {
      let url = $tab.attr('data-url-reorder');
      let ids = $tab.find('.ordered').attrToArray('data-id');

      axios.patch(url, {ids: ids})
      .then(function(response) {
        $('.alert-container').remove();

        $('body').append(response.data);
        
        setTimeout(function() {
          $('.alert-temporary').fadeOut(function() {
            $(this).remove();
          });
        }, 2000);
      })
      .catch(function(error) {
        alert('Something went wrong...');
        console.log(error)
      });
    }
  });
});
</script>
<script type="text/javascript">
$('#local-filter input[type="checkbox"]').change(function() {
	let filters = [];

	$('#local-filter .options-columns > div').each(function(index) {
		let arr = $(this).find('input[type="checkbox"]:checked').attrToArray('value');

		if (arr.length)
			filters.push(arr);
	});

	reset();

	if (filters.length)
	    applyFilters(filters);
});

function reset() {
	$('.piece-result').show();
}

function applyFilters(filters) {
	$('.piece-result').hide();

	$('.piece-result').each(function() {
		let tags = $(this).data('tags');
		let valid = 0;

		for (i=0; i < filters.length; i++) {
			if (filters[i].some(tags.includes.bind(tags)))
				valid += 1;
		}

		if (valid == filters.length) $(this).show();
	});
}
</script>
@endpush