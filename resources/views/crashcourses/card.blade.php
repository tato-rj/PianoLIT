    <div class="mx-auto position-relative cc-card" style="{{$hidePhoneOnOverflow ? 'overflow-y: hidden;' : null}}">
      <div class="position-absolute cc-phone {{empty($hidePhoneOnMobile) ? null : 'hide-on-sm'}}" style="top: {{$phoneXpos ?? '32px'}}">
        <img src="{{asset('images/mockup/crashcourse.png')}}" class="w-100">
      </div>
      <div class="bg-white pt-6 pr-6 pb-4 rounded cc-body">
        <div class="mb-4">
          <p class="text-warning text-uppercase"><strong>...by signing up to this {{$crashcourse->lessons_count}}-day course you'll learn about:</strong></p>
          <h4>{{$crashcourse->title}}</h4>
          <p class="m-0 text-muted"><i class="fas fa-envelope-open-text mr-2"></i>This course has {{$crashcourse->lessons_count}} {{ str_plural('lesson', $crashcourse->lessons_count) }}</p>
        </div>

        <form method="POST" id="crashcourse-form" disable-on-submit action="{{route('crashcourses.signup', $crashcourse)}}" class="cc-form">
          @csrf
          @include('components.form.subscription.hidden', ['id' => 'crashcourse-form'])
          <input type="hidden" name="origin_url" value="{{url()->current()}}">
            <div class="form-row">
              <div class="col-lg-6 col-md-6 col-sm-6 col-12"> 
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'first_name', 'placeholder' => 'First name', 'limit' => 120])
              </div>
              <div class="col-lg-6 col-md-6 col-sm-6 col-12"> 
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'email', 'placeholder' => 'Your email', 'limit' => 120])
              </div>
            </div>
            <div class="my-2">
              @include('auth.fields.recaptcha')
              <button type="submit" class="btn btn-primary btn-sm-block shadow btn-wide mb-2"><strong>START FREE COURSE!</strong></button>
              <div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
            </div>
          </div>
        </form>
      </div>
    </div>