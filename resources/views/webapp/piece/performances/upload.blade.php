@php($canUpload = ! auth()->user()->performances()->last30days()->exists())

<div class="text-center">
	<div class="p-3 rounded alert-grey mb-4">
		<p class="font-weight-bold text-center mb-2">Do you play this piece?</p>
		<p class="small m-0">@fa(['icon' => 'cloud-upload-alt', 'mr' => 1])Upload a video of your performance</p>
		<p class="small">@fa(['icon' => 'hands-clapping', 'mr' => 1])See the reactions from PianoLIT users from all around the world!</p>

		@if($canUpload)
		<button class="btn rounded-pill btn-outline-secondary btn-wide" id="upload-performance">@fa(['icon' => 'cloud-upload-alt'])Share my performance</button>
		@else
		<button class="btn rounded-pill btn-outline-secondary btn-wide" data-toggle="modal" data-target="#no-credits-modal">@fa(['icon' => 'cloud-upload-alt'])Share my performance</button>
		@endif
		
		<div class="small text-center opacity-8 mt-1" style="font-size:80%">The video must be <strong>100mb</strong> or less</div>
	</div>
</div>

@if($canUpload)

	@component('components.modal', ['id' => 'upload-performance-modal'])
	@slot('body')
	<div class="mb-2">
		<video id="preview-performance" class="w-100" height="240" controls>
		  <source src="" type="video/mp4">
		</video>
	</div>

	<form method="POST" action="{{route('webapp.users.performances.store', $piece)}}" enctype="multipart/form-data" disable-on-submit>
	    @csrf
	    <div class="text-center">
		    <p class="text-muted m-0">My performance of</p>
		    <p class="mb-2"><strong>{{$piece->long_name_with_composer}}</strong></p>
		</div>
		
		@input(['label' => 'Name of the performer', 'placeholder' => auth()->user()->first_name, 'bag' => 'default', 'name' => 'display_name', 'limit' => 200, 'required' => false])
	    <input type="file" name="user-performance-video" style="display: none">
	    <button type="submit" class="btn btn-primary w-100">Upload</button>
	</form>
	@endslot
	@endcomponent

@else

	@component('components.modal', ['id' => 'no-credits-modal', 'header' => 'No credits left'])
	@slot('body')
		<p class="m-0">Sorry, you can only upload <u>one video per month</u>. Your next upload will be enabled in <strong>{{auth()->user()->performances()->latest()->approved()->first()->created_at->addMonth()->diffForHumans()}}</strong>.</p>
	@endslot
	@endcomponent

@endif