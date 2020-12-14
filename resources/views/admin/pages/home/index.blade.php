@extends('admin.layouts.app')

@section('content')

  <div class="content-wrapper">
    <div class="container-fluid mb-3">

      @include('admin.components.page.title', ['icon' => 'home', 'title' => 'Dashboard', 'subtitle' => 'Here\'s an overview of how things are going today.'])

      @include('admin.pages.home.onthisday')

      @manager
      <div class="container-fluid px-0">
        @include('admin.pages.home.highlights')
      </div>
      <div class="row mb-3">
        @foreach($counts as $stat)
          @include('admin.pages.home.card')
        @endforeach
      </div>   
      @else
      <div class="row p-4">
        <div class="col-12 mb-4">
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
