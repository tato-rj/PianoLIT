@extends('layouts.app')

@section('content')
<div class="container mb-4">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <h3 class="accent-bottom mb-4">Log in to PianoLIT</h3>
            <p>If you already have a profile on our app, you can use the same email and password to log in from here.</p>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <input required type="email" name="email" placeholder="Email" class="form-control w-100 input-light" value="{{ old('email') }}">
                </div>
                <div class="form-group">
                    <input required type="password" name="password" placeholder="Password" class="form-control w-100 input-light" value="{{ old('password') }}">
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary shadow btn-block mb-4">Login</button>
                    <h6><a class="link-none" href="{{ route('register') }}">Don't have an account yet?</a></h6>
                    <h6><a class="link-none" href="{{ route('password.request') }}">Forgot Your Password?</a></h6>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mb-6">
    @include('components.sections.youtube')
</div>
@endsection
