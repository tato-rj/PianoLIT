<label class="switch cursor-pointer">
	{{-- Used for sorting --}}
	<span class="position-absolute invisible">{{$toggle ? 1 : 0}}</span>

	<input class="status-toggle" type="checkbox" {{$toggle ? 'checked' : null}} data-url="{{$route}}">
	
	<span class="slider round"></span>
</label>