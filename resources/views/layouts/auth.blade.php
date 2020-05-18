@extends('layouts.raw')

@section('content')
<div class="container-fluid">
    <div class="row h-100vh">
        <div class="col-lg-4 col-md-6 col-12 d-flex flex-center py-4">
            <div class="mx-auto" style="max-width: 400px">
                <a class="navbar-brand mb-2" href="{{route('home')}}">
                    <img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 60px">
                </a>
                <h3 class="accent-bottom mb-4">{{$title}}</h3>
                <p>{{$subtitle}}</p>
                {{$slot}}

            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-12 d-flex flex-center">
            
        </div>
    </div>
</div>
@endsection
