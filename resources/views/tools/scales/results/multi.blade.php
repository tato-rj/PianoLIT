<div class="row">
	<div class="col-lg-8 col-md-10 col-12 mx-auto">
		<ul class="nav justify-content-center nav-tabs mb-4" id="pills-tab" role="tablist">
			@foreach($scale['notes'] as $mode => $notes)
			<li class="nav-item">
				<a class="nav-link {{$loop->first ? 'active' : null}}" id="pills-{{str_slug($mode)}}-tab" data-toggle="pill" href="#pills-{{str_slug($mode)}}" role="tab" aria-controls="pills-home" aria-selected="true">{{ucfirst($mode)}}</a>
			</li>
			@endforeach
		</ul>
	</div>
</div>

<div class="tab-content" id="pills-tabContent">
	@foreach($scale['notes'] as $mode => $notes)
		@include('tools.scales.results.mode')
	@endforeach
</div>

<div class="row my-6">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer lead">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
</div>