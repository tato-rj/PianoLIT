@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

  <div class="content-wrapper">
    <div class="container-fluid mb-3">
      @include('admin.components.breadcrumb', [
        'title' => 'Dashboard',
        'description' => 'PianoLIT Admin page '])
      
      <div>
        @foreach($birthdays as $composer)
        @include('admin.pages.home.alerts.birthday')
        @endforeach
        @foreach($deathdays as $composer)
        @include('admin.pages.home.alerts.deathday')
        @endforeach
      </div>

      @manager
      <!-- Icon Cards-->
      <div class="row mb-3">
        <div class="col-lg-6 col-md-6 col-12 row no-gutters"> 
        @include('admin.pages.home.card', [
          'color' => 'advanced',
          'icon' => 'music',
          'label' => $pieces_count . ' Pieces',
          'url' => route('admin.api.search', ['api'])])

        @include('admin.pages.home.card', [
          'color' => 'beginner',
          'icon' => 'address-card',
          'label' => $composers_count . ' Composers',
          'url' => route('api.composers')])

        @include('admin.pages.home.card', [
          'color' => 'elementary',
          'icon' => 'tags',
          'label' => $tags_count . ' Tags',
          'url' => route('api.tags')])

        @include('admin.pages.home.card', [
          'color' => 'intermediate',
          'icon' => 'users',
          'label' => $users_count . ' Users',
          'url' => route('api.users')])

        @include('admin.pages.home.card', [
          'color' => 'beginner',
          'icon' => 'at',
          'label' => $subscriptions_count . ' Subscribers',
          'url' => route('admin.api.search', ['api'])])

        @include('admin.pages.home.card', [
          'color' => 'advanced',
          'icon' => 'newspaper',
          'label' => $blog_count . ' Blog posts',
          'url' => route('api.composers')])
        </div>
  
        <div class="col-lg-6 col-md-6 col-12 row no-gutters">
          <div class="col-lg-6 col-xs-12">
            <div class="h-100 px-2 py-3">
              <div class="mb-4">
                <h4 class="mb-1"><strong>Consistency</strong></h4>
                <p class="text-muted m-0">Over the past 15 days we've added on average</p>
              </div>
              <div class="text-center">
                @if($pieces_avg)
                <h1 class="display-2 font-weight-bold m-0">{{$pieces_avg}}</h1>
                <h3 class="m-0">{{$pieces_avg == 1 ? 'piece' : 'pieces'}}/day</h3>
                @else
                <h1 class="display-2 font-weight-bold m-0">&#8734;</h1>
                <h3 class="m-0">Not enough data</h3>
                @endif
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-xs-12">
            <div class="h-100 px-2 py-3">
              <div class="mb-4">
                <h4 class="mb-1"><strong>Milestone</strong></h4>
                <p class="text-muted m-0">Time to reach the next goal of <strong class="text-dark">{{$milestone['goal']}} pieces</strong></p>
              </div>
              <div class="text-center">
                @if(!empty($milestone['days_left']))
                <h1 class="display-2 font-weight-bold m-0">{{$milestone['days_left']}}</h1>
                <h3 class="m-0">{{ str_plural('day', $milestone['days_left']) }}</h3>
                @else
                <h1 class="display-2 font-weight-bold m-0">&#8734;</h1>
                <h3 class="m-0">Not enough data</h3>
                @endif
              </div>
            </div>
          </div>
        </div>
    
      </div>
    
      <div class="row">
          <div class="col-12">
            <div class="border py-4 px-3">
              <div class="ml-2 mb-4">
                <h4 class="mb-1"><strong>Our progress</strong></h4>
                <p class="text-muted">Number of pieces added per day over the past 15 days</p>
              </div>
              <canvas id="pieces_graph" class="w-100" height="300" data-records="{{$pieces_graph}}"></canvas>
            </div>
          </div>
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

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
let piecesChartElement = document.getElementById("pieces_graph").getContext('2d');
let piecesData = JSON.parse($('#pieces_graph').attr('data-records'));
let labels = [];
let data = [];

for (var i=0; i < piecesData.length; i++) {
  labels.push(piecesData[i].month + '/' + piecesData[i].day);
  data.push(piecesData[i].count);
}

console.log(labels);
console.log(data);
let piecesGraph = new Chart(piecesChartElement, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'New pieces',
        borderColor: '#1876f6',
        data: data,
        fill: false,
      }]
    },
      options: {
        legend: {
          display: false
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: getStepSize(data)
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        }
      }
});
</script>
@endsection