@component('components.modal', [
  'id' => 'modal-auth',
  'options' => [
    'body' => ['padding' => 0]
  ]
])
@slot('body')
      <div class="tab-pane fade show" id="panel-login">
        <div class="modal-body text-center px-5">
          <h5 class="mb-4 border-bottom border-primary d-inline-block px-3 pb-2 border-1x">Log in to PianoLIT</h5>
          <p>Before accessing this resource, just sign in or create an account!</p>
          <form method="POST" action="{{ route('login') }}">
              @csrf
              @include('auth.fields.login')

              <div class="form-group text-center">
                  <button type="submit" class="btn btn-primary shadow btn-block mb-3">Login</button>
                  <p><a href="{{ route('password.request') }}">Forgot Your Password?</a></p>
              </div>
          </form>
        </div>

        <div class="modal-footer border-0 bg-light cursor-pointer" data-toggle="panel" href="#panel-register" href-parent="#panel-login">
          <h6 class="m-0 text-center w-100">Don't have an account yet?</h6>
        </div>
      </div>

      <div class="" id="panel-register" style="display: none;">
        <div class="modal-body text-center px-5">
          <h5 class="mb-4 border-bottom border-primary d-inline-block px-3 pb-2 border-1x">Sign up for PianoLIT</h5>
          <p>Before accessing this resource, just sign in or create an account!</p>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                @include('auth.fields.register')

                <div class="form-group text-center">
                    @include('auth.fields.register-button')
                </div>
            </form>
        </div>

        <div class="modal-footer border-0 bg-light cursor-pointer" data-toggle="panel" href="#panel-login" href-parent="#panel-register">
          <h6 class="m-0 text-center w-100">Already have an account?</h6>
        </div>
      </div>
@endslot
@endcomponent
