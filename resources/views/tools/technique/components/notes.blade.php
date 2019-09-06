<div class="col-12 text-center">
	<p>{!! $label !!}</p>
	<div class="d-flex flex-wrap justify-content-center" id="notes-{{str_slug($result['name'])}}">
		@php($octave = 3)
		@foreach($notes as $index => $note)
			@if(! $loop->first && in_array(noteToHumans($note), ['Cbb', 'Cb', 'C', 'C#', 'C##']))
				@php($octave = 4)
			@endif
			<button class="btn btn-teal-outline play-note btn-xl m-1" data-name="{{noteToMachine($note)}}" data-octave="{{$octave}}">
				<strong>{{noteToHumans($note)}}</strong>
			</button>
		@endforeach
	</div>
</div>