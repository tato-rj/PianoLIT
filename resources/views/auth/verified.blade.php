@extends('layouts.app')

@section('content')
<div class="container mb-4">
    <div class="row mb-6">
        <div class="col-lg-4 col-md-6 col-sm-8 col-12 mx-auto">
            <div class="text-center">
                <div class="mb-3">
                    <i class="fas fa-3x fa-check-circle text-green"></i>
                </div>
                <h4 class="mb-4">Your Email has been verified!</h4>

                <div>
                    <img style="display: block; margin-left: auto; margin-right: auto;" class="w-100" src="https://media.giphy.com/media/hZj44bR9FVI3K/source.gif">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-6">
    @include('components.sections.youtube')
</div>
@endsection
