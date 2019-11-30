@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center mb-6">
        <div class="col-md-8">
            <div>
                <div class="px-3 py-2 alert-red mb-2"><i class="fas fa-exclamation-circle mr-1"></i><strong>Verify Your Email Address</strong></div>

                <div class="px-3 py-2">
                    {{ __('Before proceeding, please check your email for a verification link.') }}
                    {{ __('If you did not receive the email') }}, <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>.
                </div>
            </div>
        </div>
    </div>
</div>

@if (session('resent'))
    @include('components/alerts/success', ['message' => 'A fresh verification link has been sent to your email address.'])
@endif
@endsection
