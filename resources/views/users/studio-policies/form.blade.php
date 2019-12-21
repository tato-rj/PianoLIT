<div class="mb-4 pb-4 border-bottom">
	@foreach(\App\StudioPolicy::sections() as $step)
	@include('users.studio-policies.create.steps.' . $step, ['loop' => $loop])
	@endforeach
</div>

<div class="mb-5">
	<h6 class="text-center mb-3">Choose a theme</h6>
	<div class="row">
		@foreach(\App\StudioPolicy::themes() as $theme => $info)
		@include('users.studio-policies.theme')
		@endforeach
	</div>
</div>