@foreach($piece->audioArray as $key => $audio)
<audio preload controls class="w-100 audio-control {{$loop->first ? null : 'd-none'}}" id="{{$key}}-player">
	<source src="{{$audio}}" type="audio/mp3">
</audio>
@endforeach