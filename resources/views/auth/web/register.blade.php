@extends('layouts.app')

@push('header')
<script>
</script>
@endpush

@section('content')
<div class="container mb-4">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <h3 class="accent-bottom mb-4">Sign up for free</h3>
            <p>Once signed up you can log into the app at any time using the email and password given below. Enjoy!</p>
            <form method="POST" action="{{ route('register') }}" id="register-form" disable-on-submit>
                @csrf
    
                @include('auth.fields.register')

                <div class="form-group text-center">
                    @include('auth.fields.register-button')
                    <p class="text-muted mb-0">Already have an account? <a class="fw-semibold text-decoration-underline text-nowrap" href="{{ route('login') }}">Log in</a></p>
                </div>
            </form>
            @include('auth.google.button')
        </div>
    </div>
</div>

@endsection
