@if($piece->hasAudio())
<div class="d-inline position-relative">
	<a href="{{storage($piece->audio_path)}}" target="_blank" class="text-brand mr-1"><i class="fas fa-microphone"></i></a>
	@if($piece->recordingsAvailable == 3)
	<div class="position-absolute rounded-circle bg-success text-white font-weight-bold d-flex align-items-center justify-content-center" style="bottom: 0; right: 2px; width: 10px; height: 10px; font-size: .4em" title="This piece has all three recordings!"><i class="fas fa-check"></i></div>	
	@else
	<div class="position-absolute rounded-circle bg-secondary text-white font-weight-bold d-flex align-items-center justify-content-center" style="bottom: 0; right: 2px; width: 10px; height: 10px; font-size: .5em" title="This pieces has only {{$piece->recordingsAvailable}} {{str_plural('recording', $piece->recordingsAvailable)}}">{{$piece->recordingsAvailable}}</div>
	@endif
</div>
@else
	<a href="#" title="This piece has no recordings" class="text-muted mr-1"><i class="fas fa-microphone"></i></a>
@endif