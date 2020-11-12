@extends('layouts.app')

@section('content')
<div class="container mb-4">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <h4 class="mb-4">Reset your password below</h4>
            <form id="password-reset-form" method="POST" action="{{ route('password.update') }}" disable-on-submit>
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <input required type="email" name="email" placeholder="Email" class="form-control w-100 input-light" value="{{ old('email') }}">
                </div>
                <div class="form-group">
                    <input required type="password" name="password" placeholder="New password" class="form-control w-100 input-light" value="{{ old('password') }}">
                </div>
                <div class="form-group">
                    <input required type="password" name="password_confirmation" placeholder="Confirm your new password" class="form-control w-100 input-light" value="{{ old('password') }}">
                </div>
                
                @env('production')
                @include('auth.fields.recaptcha')
                @endenv

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary shadow btn-block mb-4">Reset my password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mb-6">
    @include('components.sections.youtube')
</div>
@endsection
