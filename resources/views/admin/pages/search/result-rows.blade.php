@foreach($pieces as $piece)
<a href="{{route('admin.pieces.edit', $piece->id)}}" title="Click to edit" class="py-2 border-0 list-group-item list-group-item-action"><small>
	<span class="mr-1 bg-{{$piece->level_name}}" style="    
	width: 10px;
    height: 10px;
    display: inline-block;
    border-radius: 50%;
"></span>
	{{$piece->medium_name_with_composer}}
</small></a>
@endforeach