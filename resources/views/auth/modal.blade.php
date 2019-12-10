<div class="modal fade" id="modal-auth" tabindex="-1" role="dialog">
  <div class="modal-dialog border-0 modal-dialog-centered" role="document">
    <div class="modal-content border-0" style="max-width: 392px;">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="tab-pane fade show" id="panel-login">
        <div class="modal-body text-center px-5">
          <h5 class="mb-4 border-bottom border-blue d-inline-block px-3 pb-2 border-1x">Log in to PianoLIT</h5>
          <p>Before accessing this resource, just sign in or create an account!</p>
          <form method="POST" action="{{ route('login') }}">
              @csrf
              @include('auth.fields.login')

              <div class="form-group text-center">
                  <button type="submit" class="btn btn-primary shadow btn-block mb-3">Login</button>
                  <p><a class="link-blue" href="{{ route('password.request') }}">Forgot Your Password?</a></p>
              </div>
          </form>
        </div>

        <div class="modal-footer border-0 bg-light cursor-pointer" data-toggle="panel" href="#panel-register" href-parent="#panel-login">
          <h6 class="m-0 text-center w-100">Don't have an account yet?</h6>
        </div>
      </div>

      <div class="" id="panel-register" style="display: none;">
        <div class="modal-body text-center px-5">
          <h5 class="mb-4 border-bottom border-blue d-inline-block px-3 pb-2 border-1x">Sign up for PianoLIT</h5>
          <p>Before accessing this resource, just sign in or create an account!</p>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                @include('auth.fields.register')

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary shadow btn-block mb-2">Register</button>
                    <div class="mb-4"><small>By continuing you indicate that you've read and agree to our <a href="{{route('terms')}}" target="_blank" class="link-blue">Terms of Service</a> and <a href="{{route('privacy')}}" target="_blank" class="link-blue">Privacy Policy</a>.</small></div>
                </div>
            </form>
        </div>

        <div class="modal-footer border-0 bg-light cursor-pointer" data-toggle="panel" href="#panel-login" href-parent="#panel-register">
          <h6 class="m-0 text-center w-100">Already have an account?</h6>
        </div>
      </div>
    </div>
  </div>
</div>