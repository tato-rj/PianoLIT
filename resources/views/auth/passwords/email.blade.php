@extends('layouts.app')

@section('content')
<div class="container mb-4">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <h4 class="mb-4">Forgot your password?</h4>
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <input required type="email" name="email" placeholder="Email" class="form-control w-100 input-light" value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary shadow btn-block mb-4">Send Password Reset Link</button>

                    <p>You will receive an email with a link to change your password. Just click on that link, type out your new password and that's it!</p>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mb-6">
    @include('components.sections.youtube')
</div>
@endsection
