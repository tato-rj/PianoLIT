@component('webapp.explore.rows.row', ['data' => $row])
<div>
<a href="{{route('webapp.blog.show', $row['collection'])}}" class="link-none">
	<div class="row">
		<div class="col-lg-3 col-md-4 col-12 py-1">
			<div class="bg-align-center position-relative h-100 rounded" style="background-image: url({{$row['collection']->cover_image()}}); min-height: 140px"></div>
		</div>
		<div class="col-lg-9 col-md-8 col-12 py-1">
			@include('webapp.blog.components.topics', ['post' => $row['collection']])
			<h4 class="mb-0 clamp-2"><strong>{{$row['collection']->title}}</strong></h4>
			<div class="hide-on-sm mt-2" style="line-height: 1.2; font-size: 92%">{{$row['collection']->description}}</div>
		</div>
	</div>
</a>
</div>
@endcomponent