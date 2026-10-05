@component('webapp.explore.rows.row', ['data' => $row])
<a href="{{ route('webapp.composers.show', $row['collection']) }}" class="link-none">
	<div class="border rounded row g-0 mb-3">
		<div class="col-lg-8 col-md-8 col-8 p-3">
			<div class="d-flex flex-wrap justify-content-between">
				<div class="me-2">
					<h5 class="mb-0 clamp-2"><strong>{{$row['collection']->name}}</strong></h5>
				</div>
				<div class="">
					<span class="flag-icon flag-icon-{{$row['collection']->country->flag_code}} rounded-1 shadow-center me-1"></span>
					<strong class="text-muted">{{$row['collection']->country->name}}</strong>
				</div>
			</div>
			<div class="mb-2"><small>{{$row['collection']->lifespan}}</small></div>
			<div data-clamp="4">{{$row['collection']->curiosity}}</div>
		</div>
		<div class="col-lg-4 col-md-4 col-4 bg-align-center rounded-end" style="background-image: url({{$row['collection']->cover_image}});"></div>
	</div>
</a>

@endcomponent
