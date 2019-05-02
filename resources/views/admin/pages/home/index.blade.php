@extends('admin.layouts.app')

@section('content')

  <div class="content-wrapper">
    <div class="container-fluid">
      @include('admin.components.breadcrumb', [
        'title' => 'Dashboard',
        'description' => 'PianoLIT Admin page '])
        
      @manager
      <!-- Icon Cards-->
      <div class="row">
        <div class="col-xl-3 col-sm-6 mb-3">
          <div class="card bg-advanced o-hidden h-100">
            <div class="card-body">
              <div class="card-body-icon">
                <i class="fas fa-music"></i>
              </div>
              <div class="mr-5">{{$pieces_count}} Pieces</div>
            </div>
            <a class="card-footer color-inherit clearfix small z-1" target="_blank" href="{{route('admin.api.search', ['api'])}}">
              <span class="float-left">See JSON response</span>
              <span class="float-right">
                <i class="fa fa-angle-right"></i>
              </span>
            </a>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
          <div class="card bg-beginner o-hidden h-100">
            <div class="card-body">
              <div class="card-body-icon">
                <i class="fas fa-fw fa-address-card"></i>
              </div>
              <div class="mr-5">{{$composers_count}} Composers</div>
            </div>
            <a class="card-footer color-inherit clearfix small z-1" target="_blank" href="{{route('api.composers')}}">
              <span class="float-left">See JSON response</span>
              <span class="float-right">
                <i class="fa fa-angle-right"></i>
              </span>
            </a>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
          <div class="card bg-elementary o-hidden h-100">
            <div class="card-body">
              <div class="card-body-icon">
                <i class="fas fa-fw fa-tags"></i>
              </div>
              <div class="mr-5">{{$tags_count}} Tags</div>
            </div>
            <a class="card-footer color-inherit clearfix small z-1" target="_blank" href="{{route('api.tags')}}">
              <span class="float-left">See JSON response</span>
              <span class="float-right">
                <i class="fa fa-angle-right"></i>
              </span>
            </a>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
          <div class="card bg-intermediate o-hidden h-100">
            <div class="card-body">
              <div class="card-body-icon">
                <i class="fa fa-fw fa-users"></i>
              </div>
              <div class="mr-5">{{$users_count}} Users</div>
            </div>
            <a class="card-footer color-inherit clearfix small z-1" target="_blank" href="{{route('api.users')}}">
              <span class="float-left">See JSON response</span>
              <span class="float-right">
                <i class="fa fa-angle-right"></i>
              </span>
            </a>
          </div>
        </div>
      </div>
      @else
      <div class="row p-4">
        <div class="col-12">
          <p>Welcome <strong>{{auth()->user()->name}}</strong>!</p>
          <p>So far you have created 
            {{auth()->user()->pieces_count}} {{str_plural('piece', auth()->user()->pieces_count) }} and 
          {{auth()->user()->composers_count}} {{str_plural('composer', auth()->user()->composers_count) }}. <a href="">Click here</a> to see how your pieces are doing in the app.</p>
        <p>Thank you for your contribution <i class="fas fa-smile text-warning"></i></p>
        </div>
        
      </div>
      @endmanager
    </div>
@endsection