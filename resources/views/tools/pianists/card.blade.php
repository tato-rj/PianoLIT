<div class="col-lg-2 col-md-4 col-6 px-3 py-2 text-center">
	<a class="link-none" href="{{route('tools.pianists.show', $pianist->slug)}}">
		<img src="{{storage($pianist->cover_path)}}" style="width: 100px" class="rounded-circle mb-2 shadow">
		<p class="m-0"><strong>{{$pianist->name}}</strong></p>
		<p class="text-grey">{{$pianist->nationality}}</p>
	</a>
</div>