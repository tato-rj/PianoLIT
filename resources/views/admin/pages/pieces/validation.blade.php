<ul>
@foreach($results as $piece)
	<li class="mb-2">{{$piece->long_name}}</li>
@endforeach
</ul>