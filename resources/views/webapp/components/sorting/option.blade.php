<div>
	<p class="mb-1 text-nowrap"><small><strong>{{$label}}</strong></small></p>
	@foreach($options as $text => $option)
	<div class="form-check custom-{{$type}}">
	  <input type="{{$type}}" value="{{$option}}" id="{{$name}}-{{$option}}" data-filter="{{$name}}" name="filter" class="form-check-input" @if($type === 'checkbox' && in_array($option, $selectedFilterNames ?? [], true)) checked @endif>
	  <label class="form-check-label text-nowrap" for="{{$name}}-{{$option}}"><small>{{$text}}</small></label>
	</div>
	@endforeach
</div>