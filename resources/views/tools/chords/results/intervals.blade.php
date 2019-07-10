@foreach($request['results']['content'] as $current => $interval)
	@php ($other = $current == 0 ? 1 : 0)
	<h3>
		<span class="text-grey">{{$request['notes'][$current]}} to {{$request['notes'][$other]}}</span> 
		<i class="fas fa-long-arrow-alt-right"></i> 
		<strong>{{$interval['full']}}</strong>
	</h3>
@endforeach
