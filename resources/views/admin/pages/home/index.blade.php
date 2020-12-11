@extends('admin.layouts.app')

@section('content')

  <div class="content-wrapper">
    <div class="container-fluid mb-3">

      @include('admin.components.page.title', ['icon' => 'home', 'title' => 'Dashboard', 'subtitle' => 'Here\'s an overview of how things are going today.'])

      @include('admin.pages.home.onthisday')

      @manager
      <div class="container-fluid">
        <div class="row mb-3">
        @foreach($counts as $stat)
          @include('admin.pages.home.card')
        @endforeach        
        </div>
      </div>
{{--       <div class="row">
        <div class="col-6">
          <div class="p-4 bg-light">
            <h6>iOS</h6>
            @php($iosValue = $iosUsers ? ($iosMembers*50)/$iosUsers : 0)
            <p>Number of users: {{$iosUsers}}</p>
            <p>Number of members: {{$iosMembers}}</p>
            <p>For every {{$iosMembers ? round($iosUsers/$iosMembers) : 0}} iOS users we get 1 membership</p>
            <p>Each member spends on average $50 (guess)</p>
            <p>Based on the guess above, each signup is worth about ${{number_format((float)$iosValue, 2, '.', '')}}</p>
          </div>
        </div>
        <div class="col-6">
          <div class="p-4 bg-light">
            <h6>WebApp</h6>
            @php($webappValue = $webappUsers ? ($webappMembers*50)/$webappUsers : 0)
            <p>Number of users: {{$webappUsers}}</p>
            <p>Number of members: {{$webappMembers}}</p>
            <p>For every {{$webappMembers ? round($webappUsers/$webappMembers) : 0}} WebApp users we get 1 membership</p>
            <p>Each member spends on average ${{$webappMembers ? $webappAveragePayment/$webappMembers : 0}}</p>
            <p>Each signup is worth about ${{number_format((float)$webappValue, 2, '.', '')}}</p>
          </div>
        </div>
      </div> --}}
    
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
