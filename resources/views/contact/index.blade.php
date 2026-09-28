@extends('layouts.app')

@section('content')
<div class="container mb-6">
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-8 col-12 mx-auto">
            <h3 class="accent-bottom mb-4">Contact us</h3>
            <p class="mb-4">If you wish to ask us a question or just say hello, send us an email!</p>
            <a href="mailto:{{config('app.emails.general')}}">@icon('mail', ['mr' => 2]){{config('app.emails.general')}}</a>
        </div>
    </div>
</div>

@endsection
