	<div class="mb-4 text-center">
		@if(!empty($version))
    	<div class="text-grey"><small>version {{$version}}</small></div>
    	@endif
	    <h3>{{$title}}</h3>
		<div id="subtitle" class="text-grey">
		    <div class="mx-auto" style="max-width: {{$width ?? '500px'}}">{{$subtitle}}</div>
		</div>
	</div>
