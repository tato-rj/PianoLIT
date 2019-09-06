@if(count($collection) > 1)
<div class="col-lg-8 col-md-10 col-12 mx-auto">
	<ul class="nav justify-content-center nav-tabs mb-4" id="pills-tab" role="tablist">
		@foreach($collection as $result)
		<li class="nav-item">
			<a class="nav-link {{$loop->first ? 'active' : null}}" id="pills-{{str_slug($result['name'])}}-tab" data-toggle="pill" href="#pills-{{str_slug($result['name'])}}" role="tab" aria-controls="pills-home" aria-selected="true">{{ucfirst($result['name'])}}</a>
		</li>
		@endforeach
	</ul>
</div>
@endif