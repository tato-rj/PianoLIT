<div>
	<p class="mb-1 text-nowrap"><small><strong>{{$label}}</strong></small></p>
	@foreach($options as $text => $option)
	<div class="custom-control custom-radio">
	  <input type="radio" value="{{$option}}" id="{{$name}}-{{$option}}" data-filter="{{$name}}" name="filter" class="custom-control-input">
	  <label class="custom-control-label text-nowrap" for="{{$name}}-{{$option}}"><small>{{$text}}</small></label>
	</div>
	@endforeach
</div>