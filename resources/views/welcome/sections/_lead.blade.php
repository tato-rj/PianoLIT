<section class="container mb-5">
	<div class="row">
		<div class="col-lg-9 col-sm-10 col-12 mx-auto text-center">
			<h1 class="mb-4"><strong>Find music that inspires you.</strong></h1>
			<p class="text-muted mb-5">Where pianists discover new pieces and find inspiration to play only what they love.</p>
			
			<a href="#"><img src="{{asset('images/apple/coming_up.svg')}}" height="50" class="mb-4"></a>
			<div class="row">
				<div class="col-lg-6 col-md-8 col-10 mx-auto"> 
				@include('components.form.subscription', ['label' => 'Let me know when it\'s out!'])
				</div>
			</div>	
		</div>
		<div id="phone-display" class="col-12 text-center">
			<img src="{{asset('images/mockup/main.png')}}" style="max-width: 720px">
		</div>

		<div class="col-12 text-center">
			<div class="my-5">
				<p>We're working hard on this project! <strong>PianoLIT</strong> will be out in</p>
				<div id="clock" class="d-flex flex-wrap flex-center"></div>
			</div>
			<div class="row mb-4">
				<div class="col-12">
					<p class="text-center mx-auto">In the mean time, subscribe to our <a href="{{route('youtube')}}" target="_blank" class="link-blue">Youtube Channel</a> and enjoy daily videos of awesome piano pieces and tips!</p>
				</div>
				@foreach($videos as $video)
				<div class="col-lg-4 col-md-4 col-12 p-3">
					<iframe class="w-100" height="260" src="https://www.youtube.com/embed/{{$video}}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
				@endforeach
			</div>
			<a href="{{route('youtube')}}" target="_blank" class="btn btn-primary btn-wide shadow show-overlay"><i class="fab fa-lg fa-youtube mr-3"></i>Visit our channel</a>
		</div>
	</div>
</section>