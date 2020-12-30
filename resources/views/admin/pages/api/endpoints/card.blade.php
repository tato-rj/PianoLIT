<div class="col-lg-4 col-md-6 col-12 mb-3 endpoint-card">
	<div class="bg-light rounded p-3">
	    <div class="mb-2 {{! empty($depricated) ? 'text-danger' : null}}">{{$title}}</div>
	    @if(! empty($depricated))
	    <div class="bg-white badge text-danger absolute-top-right" style="right: 2em;">deprecated</div>
	    @endif
	    <div class="mb-1 pb-2 border-bottom d-flex align-items-center">
		    <div class="badge alert-{{$colors[strtolower($type)]}} mr-1">{{$type}}</div>
		    @if($type == 'GET')
		    <div>
			    <a href="{{$route}}" target="_blank" class="link-{{$colors[strtolower($type)]}}"><small>{{strtok($route, '?')}}</small></a>
			</div>
		    @else
		    <form method="POST" action="{{$route}}" target="_blank">
		    	<button class="btn-raw text-{{$colors[strtolower($type)]}}"><small>{{strtok($route, '?')}}</small></button>
		    </form>
		    @endif
		</div>
	    <div>
	    	<span class="badge text-muted border">ARGS</span>
	    	@forelse($args as $arg)
	    	<span class="badge alert-yellow">{{$arg}}</span>
	    	@empty
	    	<small class="text-muted"><i>No arguments</i></small>
	    	@endforelse
	    </div>
	</div>
</div>