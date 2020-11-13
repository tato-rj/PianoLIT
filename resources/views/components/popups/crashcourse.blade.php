@component('components.modal', [
	'id' => 'modal-subscription', 
	'cookie' => 'crashcourse15-popup',
])

@slot('header')
PianoLIT Crashcourses
@endslot

@slot('body')

<div class="position-relative mb-4 border-bottom" style="height: 280px; overflow-y: hidden;">
	<div class="position-absolute" style="top: -32px">
	<img src="{{asset('images/mockup/crashcourse.png')}}" class="w-100">
	</div>
</div>
<div class="text-center px-4">
    <div class="mb-4">
      <p class="text-warning text-uppercase"><strong>...by signing up to this {{$highlightedCrashcourse->lessons_count}}-day course you'll learn about:</strong></p>
      <h4>{{$highlightedCrashcourse->title}}</h4>
      <p class="m-0 text-muted"><i class="fas fa-envelope-open-text mr-2"></i>This course has {{$highlightedCrashcourse->lessons_count}} {{ str_plural('lesson', $highlightedCrashcourse->lessons_count) }}</p>
    </div>
        <form method="POST" id="crashcourse-form" action="{{route('crashcourses.signup', $highlightedCrashcourse)}}" class="cc-form">
          @csrf
          @include('components.form.subscription.hidden', ['id' => 'crashcourse-form'])
          <input type="hidden" name="origin_url" value="{{url()->current()}}">
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'first_name', 'placeholder' => 'First name', 'limit' => 120])
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'email', 'placeholder' => 'Your email', 'limit' => 120])
            </div>
            <div class="my-2 text-center">
              @include('auth.fields.recaptcha')
              <button disable-on-submit type="submit" class="btn btn-primary btn-sm-block shadow btn-wide mb-2"><strong>START FREE COURSE!</strong></button>
              <div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
            </div>
          </div>
        </form>
</div>
@endslot
@endcomponent