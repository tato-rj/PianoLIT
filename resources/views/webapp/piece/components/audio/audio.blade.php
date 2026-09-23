@foreach($piece->audioArray as $key => $audio)
<audio preload="metadata" controls class="w-100 audio-control {{$loop->first ? null : 'd-none'}}" id="{{$key}}-player"
    @unless($hasMediaAccess) data-media-preview="{{$previewSeconds}}" controlslist="nodownload" @endunless>
	<source src="{{$audio}}" type="audio/mp3">
</audio>
@endforeach
