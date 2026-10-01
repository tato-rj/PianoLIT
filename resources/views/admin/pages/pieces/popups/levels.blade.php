<div>
	@foreach($levels as $level)
	<div class="form-check level-element">
		<small>
	    <input type="radio" id="level-{{$level->name}}-{{$piece->id}}" value="{{$level->id}}" name="level-{{$piece->id}}" {{($piece->level->name == $level->name) ? 'checked' : ''}} class="form-check-input input-level" data-badge="#badge-level-{{$piece->id}}" data-url="{{route('admin.pieces.update-level', $piece->id)}}">
	    <label class="form-check-label" for="level-{{$level->name}}-{{$piece->id}}">{{ucfirst($level->name)}}</label>
	</small>
	</div>
	@endforeach
</div>