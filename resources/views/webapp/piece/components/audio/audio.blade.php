@foreach($piece->audioArray as $key => $audio)
<audio data-access-prompt="{{ app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'audio', $piece) }}" preload="metadata" controls class="w-100 audio-control {{$loop->first ? null : 'd-none'}}" id="{{$key}}-player"
    @unless($hasAudioAccess) data-media-preview="{{$audioPreviewSeconds}}" controlslist="nodownload" @endunless>
	<source src="{{$audio}}" type="audio/mp3">
</audio>
@endforeach
