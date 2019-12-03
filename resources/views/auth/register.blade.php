@extends('layouts.app')

@section('content')
<div class="container mb-4">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <h3 class="accent-bottom mb-4">Sign up for free</h3>
            <p>Once signed up you can log into the app at any time using the email and password given below. Enjoy!</p>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <input required type="text" name="first_name" placeholder="First name" class="form-control w-100 input-light" value="{{ old('first_name') }}">
                    @include('components/form/error', ['field' => 'first_name'])
                </div>
                <div class="form-group">
                    <input required type="text" name="last_name" placeholder="Last name" class="form-control w-100 input-light" value="{{ old('last_name') }}">
                    @include('components/form/error', ['field' => 'last_name'])
                </div>
                <div class="form-group">
                    <input required type="email" name="email" placeholder="Email" class="form-control w-100 input-light" value="{{ old('email') }}">
                    @include('components/form/error', ['field' => 'email'])
                </div>
                <div class="form-group">
                    <input required type="password" name="password" placeholder="Password" class="form-control w-100 input-light" value="{{ old('password') }}">
                    @include('components/form/error', ['field' => 'password'])
                </div>
                <div class="form-group">
                    <input required type="password" name="password_confirmation" placeholder="Confirm your password" class="form-control w-100 input-light" value="{{ old('password') }}">
                    @include('components/form/error', ['field' => 'password'])
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary shadow btn-block mb-2">Register</button>
                    <div class="mb-4"><small>By continuing you indicate that you've read and agree to our <a href="" class="link-blue">Terms of Service</a> and <a href="" class="link-blue">Privacy Policy</a>.</small></div>
                    <h6><a class="link-none" href="{{ route('login') }}">Already have an account?</a></h6>
                </div>

            </form>
        </div>
    </div>
</div>

<div class="container mb-6">
    @include('components.sections.youtube')
</div>
@endsection
