  <div class="col-{{$col}} p-3">
  	<div class="border py-4 px-3">
  		<div class="ml-2 mb-4">
		    <h4 class="mb-1"><strong>{{$title}}</strong></h4>
		    <p class="text-muted">{{$subtitle ?? null}}</p>
		</div>
	    <canvas id="{{$id}}" class="w-100" height="300" data-records="{{$data}}"></canvas>
	</div>
  </div>
