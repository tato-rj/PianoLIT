@extends('layouts.raw', ['title' => 'PianoLIT WebApp', 
    'shareable' => [
        'keywords' => '',
        'title' => 'PianoLIT WebApp',
        'description' => 'Discover new pieces and find inspiration to play only what you love.',
        'thumbnail' => asset('images/webapp/thumbnail.jpg'),
        'created_at' => carbon('5/28/2020')->format(DateTime::ISO8601),
        'updated_at' => carbon('5/28/2020')->format(DateTime::ISO8601)
    ]])

@section('content')
<div class="container-fluid">
    <div class="row h-100vh">
        <div class="col-lg-6 col-md-12 col-12 d-flex flex-center py-3">
            <div style="max-width: 400px" class="{{$classes ?? null}}">
            	<a class="navbar-brand mb-2" href="{{config('app.url')}}">
            		<img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 60px">
            	</a>
                <h3>{{$title}}</h3>
                <p>{!! $subtitle !!}</p>
                
                {{$slot}}

            </div>
        </div>

        <div class="col-lg-6 col-md-12 col-12 bg-light">
            <div class="d-flex flex-center h-100">
                <div class="py-4">
                    <div class="text-center px-2">
                        <h3 class="hide-on-sm">Find music that inspires you</h3>
                        <p class="text-muted mb-1" style="font-size: 110%">Discover new pieces and find inspiration to play only what you love.</p>
                    </div>
                    <img src="{{asset('images/webapp/devices.png')}}" class="w-100">
                    @include('layouts.footer.social')
                    {{-- @include('layouts.footer.links') --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection