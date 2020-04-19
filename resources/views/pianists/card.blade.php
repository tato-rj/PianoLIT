<div class="col-lg-2 col-md-4 col-6 text-center mb-3 pianist-card">
	<a class="link-none" href="{{route('resources.pianists.show', $pianist->slug)}}">
		<img src="{{storage($pianist->cover_path)}}" style="width: 100px" class="rounded-circle mb-2 shadow">
		<p class="m-0 text-truncate"><strong>{{$pianist->name}}</strong></p>
		<p class="text-grey">{{$pianist->country->name}}</p>
	</a>
</div>