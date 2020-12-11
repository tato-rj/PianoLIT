@extends('admin.layouts.app')

@section('content')

  <div class="content-wrapper">
    <div class="container-fluid mb-3">

      @include('admin.components.page.title', ['icon' => 'home', 'title' => 'Dashboard', 'subtitle' => 'Here\'s an overview of how things are going today.'])

      @include('admin.pages.home.onthisday')

      @manager
      <div class="container-fluid px-2">
        <div class="row no-gutters mb-4" id="user-stats-overview">
          <div class="col-lg-4 col-md-4 col-12 bg-primary text-white d-flex flex-center p-4">
            <div class="text-center">
              <div class="opacity-6">Total number of users</div>
              <h1 style="font-size: 3.6em;" class="my-3 mx-auto">{{number_format($userStats['all']['counts'][1])}}</h1>
              <div>
                @if($userStats['all']['counts'][0] == $userStats['all']['counts'][1])
                @fa(['icon' => 'exclamation-circle'])Same as last week
                @elseif($userStats['all']['counts'][0] > $userStats['all']['counts'][1])
                @fa(['icon' => 'arrow-down'])Down {{$userStats['all']['counts'][0] - $userStats['all']['counts'][1]}} from last week
                @else
                @fa(['icon' => 'arrow-up'])Up {{$userStats['all']['counts'][1] - $userStats['all']['counts'][0]}} from last week
                @endif
              </div>
            </div>
          </div>
          <div class="col-lg-8 col-md-8 col-12 d-flex flex-column alert-grey">
            @foreach($userStats['platforms'] as $platform)
            <div class="p-4 d-flex align-items-center d-flex flex-wrap d-apart {{$loop->iteration == 2 ? 'border-y' : null}}" style="flex: 1">
              <div class="mr-3">@fa($platform['icon']){{$platform['counts'][1]}} {{$platform['label']}} Users</div>
              <div>
                @if($platform['counts'][0] == $platform['counts'][1])
                <small class="text-warning text-nowrap">@fa(['icon' => 'exclamation-circle'])Same as last week</small>
                @elseif($platform['counts'][0] > $platform['counts'][1])
                <small class="text-red text-nowrap">@fa(['icon' => 'arrow-down'])Down {{$platform['counts'][0] - $platform['counts'][1]}} from last week</small>
                @else
                <small class="text-green text-nowrap">@fa(['icon' => 'arrow-up'])Up {{$platform['counts'][1] - $platform['counts'][0]}} from last week</small>
                @endif
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
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
