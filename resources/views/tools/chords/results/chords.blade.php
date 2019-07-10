@foreach($request['results']['content'] as $chord)
	<h2>
		<span class="text-grey"></span> 
		<i class="fas fa-long-arrow-alt-right"></i> 
		<strong>{{$chord['name']}}</strong>
	</h2>
@endforeach
