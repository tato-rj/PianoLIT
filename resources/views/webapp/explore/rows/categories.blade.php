@component('webapp.explore.rows.row', ['data' => $row])
<div class="d-flex flex-wrap">
	@foreach($row['collection'] as $name => $tags)
		<button data-toggle="modal" data-target="#tags-{{$name}}" class="btn m-1 px-4 py-2 text-nowrap d-flex align-items-center" style="border: 1px solid rgba(77, 192, 181, .3)">
			<img src="{{asset('images/icons/'.$name.'.svg')}}" class="mr-3" style="width: 25px">{{ucfirst($name)}}
		</button>
		@component('components.modal', ['id' => 'tags-' . $name, 'title' => ucfirst($name)])
		@slot('body')
			<div class="search-results"></div>
			<div class="d-flex flex-wrap">
			@foreach($tags as $tag)
			    <button data-name="{{$tag->name}}" data-id="{{$tag->id}}" class="tag btn btn-light badge-pill m-2 px-3 py-1 text-nowrap">
			      {{$tag->name}}
			    </button>
			@endforeach
			</div>
		@endslot
		@endcomponent
	@endforeach
</div>
@endcomponent
