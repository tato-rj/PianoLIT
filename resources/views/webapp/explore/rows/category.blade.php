{{-- @component('webapp.explore.rows.row', ['data' => $row])
<div class="row g-0">
	@foreach($row['collection'] as $name => $tags)
		<div class="col-4 p-2">
			<button data-bs-toggle="modal" data-bs-target="#tags-{{$name}}"
					style="border: 1px solid rgba(77, 192, 181, .3)"
					class="btn rounded d-block w-100 py-2 text-nowrap d-flex flex-center">
				<div class="">
					<img src="{{asset('images/icons/'.$name.'.svg')}}" class="m-1" style="width: 25px"><div>{{ucfirst($name)}}</div>
				</div>
			</button>
		</div>

		@component('components.modal', ['id' => 'tags-' . $name])
		@slot('header')
		{{ucfirst($name)}}
		@endslot

		@slot('body')
			<div class="search-results"></div>
			<div class="d-flex justify-content-center flex-wrap">
			@foreach($tags as $tag)
			    <button data-name="{{$tag->name}}" data-id="{{$tag->id}}" class="tag btn btn-light rounded-pill m-2 px-3 py-1 text-nowrap">
			      {{$tag->name}}
			    </button>
			@endforeach
			</div>
		@endslot
		@endcomponent
	@endforeach
</div>
@endcomponent
 --}}