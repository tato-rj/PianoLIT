@if(!empty($moments) && !empty($splitMoments))<div class="piece-about-media">@endif
@if(!empty($moments))<div class="piece-moments-player">@endif
<video @if(!empty($accessPrompt)) data-access-prompt="{{ $accessPrompt }}" @endif playsinline webkit-playsinline class="{{$classes ?? null}}" id="{{$id ?? null}}" @if(!empty($moments)) data-video-moments="{{json_encode($moments)}}" @endif data-position="{{$position ?? null}}" data-poster="{{$thumbnail ?? null}}"
    @isset($previewSeconds) data-media-preview="{{$previewSeconds}}" controlslist="nodownload" disablepictureinpicture @endisset>
<source controls="{{$controls ?? true}}" src="{{$url}}" type="video/mp4">
Your browser does not support the video tag.
</video>

@if(!empty($moments))
</div>
@include('webapp.piece.components.video.moments', ['videoId' => $id, 'moments' => $moments])
@endif
@if(!empty($moments) && !empty($splitMoments))</div>@endif
