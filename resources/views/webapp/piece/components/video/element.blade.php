<video class="w-100" id="piece-video-{{$tutorial->id}}"
    @unless($hasMediaAccess) data-media-preview="{{$previewSeconds}}" controlslist="nodownload" disablepictureinpicture @endunless>
	<source src="{{$tutorial->video_url}}" type="video/mp4">
	Your browser does not support the video tag.
</video>
