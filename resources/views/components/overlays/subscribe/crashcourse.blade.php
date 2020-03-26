@component('layouts.overlay', ['name' => 'crashcourse', 'light' => false, 'position' => 'center', 'background' => '0,0,0,0.8'])
<div class="cc-container">
  @include('crashcourses.card', [
  	'crashcourse' => $highlightedCrashcourse,
  	'phoneXpos' => '-14px',
  	'hidePhoneOnMobile' => true,
  	'hidePhoneOnOverflow' => false,
  ])
</div>
{{-- <div class="mx-3" style="max-width: 512px">
	<div class="rounded text-center shadow-light bg-white pt-4">
		<img src="{{storage($gift->thumbnail_path)}}" class="rounded-top border" style="width: 210px">
		<div class="px-4 py-3 bg-white rounded-bottom">
			<div class="mb-4">
				<div>Subscribe today and get a <strong><u>FREE</u></strong> poster in your inbox!</div>
			</div>
			
			@include('components.form.subscription', ['gift_url' => route('infographs.download', $gift->slug)])
		</div>
	</div>
</div> --}}
@endcomponent