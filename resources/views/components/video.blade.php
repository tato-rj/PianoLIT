<video class="{{$classes ?? null}}" id="{{$id ?? null}}" data-position="{{$position ?? null}}" data-poster="{{$thumbnail ?? null}}"
    @if(! empty($previewSeconds)) data-media-preview="{{$previewSeconds}}" controlslist="nodownload" disablepictureinpicture @endif>
<source controls="{{$controls ?? true}}" src="{{$url}}" type="video/mp4">
Your browser does not support the video tag.
</video>
