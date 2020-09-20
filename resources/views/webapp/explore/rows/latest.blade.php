@component('webapp.explore.rows.row', ['data' => $row])
<div class="">
	@foreach($row['collection'] as $tutorial)
	<a class="link-none" href="">
		<div class="{{$loop->last ? null : 'border-bottom'}} px-3 py-2 d-flex d-apart">
			<div class="flex-grow mr-4">
				<div><strong>{{$tutorial->piece->medium_name}}</strong></div>
				<div><i>by {{$tutorial->piece->composer->name}}</i></div>
			</div>
			<div>
				<div class="text-muted">Watch tutorial</div>
			</div>
		</div>
	</a>
	@endforeach
</div>
@endcomponent