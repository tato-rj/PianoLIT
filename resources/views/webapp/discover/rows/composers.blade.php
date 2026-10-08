<div class="discover-composers mb-4">
	<div class="d-flex d-apart mb-3">
		<h5 class="m-0">Composers</h5>
		<a href="{{route('webapp.composers.index')}}" class="btn-raw link-primary d-inline-flex align-items-center gap-2">View all @icon('arrow-right', ['mr' => 0])</a>
	</div>

	@include('webapp.components.grids.circles', [
		'collection' => $row['content'],
		'composerLinks' => true,
		'composerRow' => true,
		'name' => 'name',
		'image' => 'cover_image',
		'count' => 'pieces_count'])
</div>

@include('webapp.discover.rows.link-rail-script')
