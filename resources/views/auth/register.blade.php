@extends('layouts.app')

@section('content')
<div class="container mb-4">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <h3 class="accent-bottom mb-4">Sign up for free</h3>
            <p>Once signed up you can log into the app at any time using the email and password given below. Enjoy!</p>
            <form method="POST" action="{{ route('register') }}">
                @csrf
    
                @include('auth.fields.register')

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary shadow btn-block mb-2">Register</button>
                    <div class="mb-4"><small>By continuing you indicate that you've read and agree to our <a href="{{route('terms')}}" target="_blank" class="link-blue">Terms of Service</a> and <a href="{{route('privacy')}}" target="_blank" class="link-blue">Privacy Policy</a>.</small></div>
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
