<div class="my-2">
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
					<div><i class="fas fa-check text-green mr-2"></i> {{$crashcourse->lessons_count}} lessons included in this course</div>
					<div><i class="fas fa-check text-green mr-2"></i> Receive the course in your inbox</div>
					<div><i class="fas fa-check text-green mr-2"></i> Learn with bite sized content</div>
				</div>
				<form method="POST" id="crashcourse-form" disable-on-submit action="{{route('crashcourses.signup', $crashcourse)}}" class="cc-form">
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
</div>