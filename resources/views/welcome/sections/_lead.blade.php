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

			@include('components.sections.youtube')
		</div>
	</div>
</section>