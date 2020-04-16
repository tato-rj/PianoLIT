{{-- @if(local() || request()->has('dev'))
<section class="container-fluid">
	@include('welcome.sections.search')		
</section>
<section class="container mb-5">
	@include('components.sections.youtube')	
</section>
@else --}}
<section class="container mb-5">
	<div class="row">		
		<div class="col-lg-8 col-sm-10 col-12 mx-auto text-center">
			<h1 class="mb-3"><strong>Find music that inspires you.</strong></h1>
			<p class="text-muted mb-4" style="font-size: 110%">Where pianists discover new pieces and find inspiration<br>to play only what they love.</p>
			
			@include('components.buttons.download')

			{{-- <a href="#"><img src="{{asset('images/apple/coming_up.svg')}}" height="50" class="mb-4"></a> --}}
{{-- 			<div class="row">
				<div class="col-lg-6 col-md-8 col-10 mx-auto"> 
				@include('components.form.subscription', ['label' => 'Let me know when it\'s out!'])
				</div>
			</div> --}}
		</div>

		<div id="phone-display" class="col-12 text-center d-flex flex-center">
			<img src="{{asset('images/mockup/main-view.png')}}" style="max-width: 720px">
		</div>
	</div>
</section>
{{-- @endif --}}