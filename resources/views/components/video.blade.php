@if(!empty($moments))<div class="piece-moments-player">@endif
<video class="{{$classes ?? null}}" id="{{$id ?? null}}" @if(!empty($moments)) data-video-moments="{{json_encode($moments)}}" data-moment-window="{{config('webapp.moment_display_seconds')}}" @endif data-position="{{$position ?? null}}" data-poster="{{$thumbnail ?? null}}"
    @if(! empty($previewSeconds)) data-media-preview="{{$previewSeconds}}" controlslist="nodownload" disablepictureinpicture @endif>
<source controls="{{$controls ?? true}}" src="{{$url}}" type="video/mp4">
Your browser does not support the video tag.
</video>

@if(!empty($moments))
</div>
@include('webapp.piece.components.video.moments', ['videoId' => $id, 'moments' => $moments])
@endif
