<div class="position-absolute bg-white shadow-sm border px-2 pt-2 pb-1 rounded popup mb-3" style="top: 10px; display: none; z-index: 1; left: 0">
  @foreach($levels as $level)
  <div class="custom-control custom-radio level-element">
  	<small>
	    <input type="radio" id="level-{{$level->name}}-{{$piece->id}}" value="{{$level->id}}" name="level-{{$piece->id}}" {{($piece->level->name == $level->name) ? 'checked' : ''}} class="custom-control-input input-level" data-badge="#badge-level-{{$piece->id}}" data-url="{{route('admin.pieces.update-level', $piece->id)}}">
	    <label class="custom-control-label" for="level-{{$level->name}}-{{$piece->id}}">{{ucfirst($level->name)}}</label>
	</small>
  </div>
  @endforeach
</div>