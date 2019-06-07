<div>
	@foreach($levels as $level)
	<div class="custom-control custom-radio level-element">
		<small>
	    <input type="radio" id="level-{{$level->name}}-{{$piece->id}}" value="{{$level->id}}" name="level-{{$piece->id}}" {{($piece->level->name == $level->name) ? 'checked' : ''}} class="custom-control-input input-level" data-badge="#badge-level-{{$piece->id}}" data-url="{{route('admin.pieces.update-level', $piece->id)}}">
	    <label class="custom-control-label" for="level-{{$level->name}}-{{$piece->id}}">{{ucfirst($level->name)}}</label>
	</small>
	</div>
	@endforeach
</div>