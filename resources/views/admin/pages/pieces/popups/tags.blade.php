<div class="position-absolute bg-white shadow-sm border p-2 rounded popup mb-3" style="top: 10px; display: none; z-index: 2; right: 0; width: 720px">
    <div class="d-flex flex-wrap">
      @foreach($tagsByType as $type => $tags)
      <label class="mb-2 text-center bg-light rounded w-100"><strong>{{ucfirst($type)}}</strong></label>
        @foreach($tags as $tag)
        <div class="custom-control custom-checkbox mx-1 mb-1">
        	<small>
        		<input type="checkbox" 
        			class="custom-control-input input-tag" 
        			data-url="{{route('admin.pieces.update-tag', $piece->id)}}"
        			data-badge="#badge-tag-{{$piece->id}}" 
        			value="{{$tag->id}}" 
        			id="{{$piece->id}}-{{$tag->name}}"
        			{{($piece->tags->contains($tag->id)) ? 'checked' : ''}}>
        		<label class="custom-control-label" for="{{$piece->id}}-{{$tag->name}}">{{$tag->name}}</label>
        	</small>
        </div>
        @endforeach
      @endforeach
    </div>
</div>