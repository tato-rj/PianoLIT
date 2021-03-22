<div class="grid-item col-lg-4 col-md-6 col-12 p-2">
	<div class="card border-0 shadow-light w-100 t-2 rounded">
		<a class="link-none" href="{{route('crashcourses.show', $crashcourse->slug)}}" target="_blank">
			<div class="card-img-top rounded-top bg-align-center position-relative" style="background-image: url({{$crashcourse->cover_image()}}); height: 200px">
				
				@include('components.tags.new', ['is_new' => $crashcourse->is_new])

				<div class="card-overlay h-100 t-2" style="opacity: 0">
					<div class="text-white overlay-blue d-flex flex-center rounded-top"><i class="fas fa-eye fa-3x"></i></div>
				</div>
			</div>
			<div class="card-body rounded-bottom">
				<p class="text-muted mb-1"><small>@fa(['icon' => 'layer-group', 'color' => 'primary']){{$crashcourse->lessons_count}} lessons</small></p>
				<h5 class="card-title mb-2">{{$crashcourse->title}}</h5>
				<p class="card-text">{{$crashcourse->description}}</p>
			</div>
		</a>
	</div>
</div>

{{-- <div class="my-2">
	<div class="row mb-4">
		<div class="col-lg-6 col-md-6 col-12 bg-align-center px-5 py-4 position-relative" style="background-image: url({{$crashcourse->cover_image()}});">
			<div class="overlay-darkest"></div>
			<div class="pt-6 text-white z-10 position-relative">
				<h5 class="border-bottom mb-2 pb-2">{{$crashcourse->title}}</h5>
				<p class="m-0">{{$crashcourse->description}}</p>
			</div>
		</div>

		<div class="col-lg-6 col-md-6 col-12 bg-light d-flex align-items-end p-4">
			<div class="w-100">
				<div class="mb-4">
					<p class=""><strong>What's in it for me?</strong></p>
					<div><i class="fas fa-check text-green mr-2"></i> 100% FREE!</div>
					<div><i class="fas fa-check text-green mr-2"></i> <strong>{{$crashcourse->lessons_count}} lessons</strong> included in this course</div>
					<div><i class="fas fa-check text-green mr-2"></i> Receive the course in your inbox</div>
					<div><i class="fas fa-check text-green mr-2"></i> Learn with bite sized content</div>
				</div>
				<form method="POST" disable-on-submit action="{{route('crashcourses.signup', $crashcourse)}}" class="cc-form">
					@csrf
					@include('components.form.subscription.hidden', ['id' => 'crashcourse-form'])
					<div class="">
						@input(['styles' => 'border: none', 'classes' => 'border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'first_name', 'placeholder' => 'First name', 'limit' => 120])
						@input(['styles' => 'border: none', 'classes' => 'border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'email', 'placeholder' => 'Your email', 'limit' => 120])
						<div class="text-right mt-4">
							<button type="submit" class="btn btn-primary shadow btn-block">START LEARNING NOW</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div> --}}