@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

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

      <div class="row">
        <div class="col-6">
          <div class="border text-center py-4 px-3 mb-4">
            <h4 class="m-0"><strong>We've been adding</strong></h4>
            <h1 class="display-2 font-weight-bold">{{$pieces_avg}}</h1>
            <p class="m-0">{{$pieces_avg == 1 ? 'piece' : 'pieces'}} average on the past 15 days</p>
          </div>
        </div>
        <div class="col-6">
          <div class="border text-center py-4 px-3 mb-4">
            <h4 class="m-0"><strong>We have about</strong></h4>
            @if(!empty($milestone['days_left']))
            <h1 class="display-2 font-weight-bold">{{$milestone['days_left']}}</h1>
            <p class="m-0">{{ str_plural('day', $milestone['days_left']) }}</strong> left to reach <span class="text-brand"><strong>{{$milestone['goal']}}</strong></span> pieces</p>
            @else
            <h1 class="display-2">:/</h1>
            <p class="text-muted m-0">It's been a while since we added any new pieces!</p>
            @endif
          </div>
        </div>
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
                    stepSize: 1
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