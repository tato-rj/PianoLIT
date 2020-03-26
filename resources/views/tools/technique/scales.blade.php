@extends('layouts.app', [
	'title' => 'Scales Tutor | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'scale,arpeggio,music theory,fingering',
		'title' => 'Scales Tutor',
		'description' => 'All you need to know about the main scales at any time',
		'thumbnail' => asset('images/misc/thumbnails/scales.jpg'),
		'created_at' => carbon('29-08-2019'),
		'updated_at' => carbon('29-08-2019')
	]])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}

@-webkit-keyframes heartBeat {
  0% {
    -webkit-transform: scale(1);
    transform: scale(1);
  }

  14% {
    -webkit-transform: scale(1.3);
    transform: scale(1.3);
  }

  28% {
    -webkit-transform: scale(1);
    transform: scale(1);
  }

  42% {
    -webkit-transform: scale(1.3);
    transform: scale(1.3);
  }

  70% {
    -webkit-transform: scale(1);
    transform: scale(1);
  }
}

@keyframes heartBeat {
  0% {
    -webkit-transform: scale(1);
    transform: scale(1);
  }

  14% {
    -webkit-transform: scale(1.3);
    transform: scale(1.3);
  }

  28% {
    -webkit-transform: scale(1);
    transform: scale(1);
  }

  42% {
    -webkit-transform: scale(1.3);
    transform: scale(1.3);
  }

  70% {
    -webkit-transform: scale(1);
    transform: scale(1);
  }
}

.heartBeat {
  -webkit-animation-name: heartBeat;
  animation-name: heartBeat;
  -webkit-animation-duration: .75s;
  animation-duration: .75s;
  -webkit-animation-timing-function: ease-in-out;
  animation-timing-function: ease-in-out;
}


#pills-tab .nav-link {
	color: #b8c2cc;
}

#pills-tab .active {
	color: #343a40!important;
	font-weight: bold;
}
</style>
@endpush

@section('content')

@include('components.title', [
	'version' => '2.0',
	'title' => 'Scales Tutor', 
	'subtitle' => 'Select below the scale you need and we\'ll show the notes and fingering for each hand'])

<div class="container mb-4" id="key-container">
	@include('tools.technique.components.input')
	@include('tools.technique.components.key')
	<div class="row">
		@include('tools.technique.components.submit', ['type' => 'scales'])
		<div class="col-12 mb-5">
			<p>This tool will help you learn the notes and fingering for every scale in any key or mode. You will also find information about the different types of minor keys and special modes.</p>
			<p>Just select the key and mode you need and we will show you all the resources you need!</p>
		</div>
    <div class="col-12 text-center mb-4">
      <h6>Need help with <strong>Arpeggios</strong>? <a href="{{route('tools.arpeggios.index')}}">Click here</a></h6>
    </div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('tools.chords.error')
@include('components.overlays.subscribe.crashcourse')
@endsection

@push('scripts')
@include('components.addthis')
@include('tools.technique.js')
<script type="text/javascript">
$("#crashcourse-overlay").showAfter(5);
</script>
@endpush