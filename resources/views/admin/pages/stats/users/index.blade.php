@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Users statistics',
    'description' => 'Data analytics for the users'])

    <div class="row"> 
        <div class="col-12 p-3 mb-4">
            <div class="border py-4 px-3">
                <h4 class="mb-1 text-center"><strong>Flow of users over time</strong></h4>
                <div id="carouselRecords" class="carousel carousel-fade">
                    <div class="select-btn-group btn-group btn-group-sm mb-4">
                      <button data-target="#carouselRecords" data-slide-to="0" class="btn btn-blue">Daily</button>
                      <button data-target="#carouselRecords" data-slide-to="1"  class="btn btn-light">Monthly</button>
                      <button data-target="#carouselRecords" data-slide-to="2"  class="btn btn-light">Yearly</button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <canvas id="day-chart" 
                                    data-records="{{json_encode($usersDaily)}}" height="100"></canvas>
                        </div>

                        <div class="carousel-item">
                            <canvas id="month-chart" 
                                    data-records="{{json_encode($usersMonthly)}}" height="100"></canvas>
                        </div>

                        <div class="carousel-item">
                            <canvas id="year-chart" 
                                    data-records="{{json_encode($usersYearly)}}" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
{{--       @include('admin.pages.stats.row', [
        'title' => 'Topics',
        'subtitle' => 'How the ' . $topicsCount . ' topics are distributed in the database.',
        'id' => 'topicsChart',
        'col' => '12',
        'data' => $topicStats]) --}}
    </div>

    <div class="row my-3">
        {{-- @include('admin.pages.stats.blog.ranking') --}}
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
// $(document).ready( function () {
//     $('#posts-table').DataTable({
//         aaSorting: [],
//         columnDefs: [{
//                     targets: [3],
//                     orderable: false
//                 }]
//     });
// } );
</script>
<script type="text/javascript">
$('.select-btn-group .btn').on('click', function() {
    $(this).siblings().removeClass('btn-blue').addClass('btn-light');
    $(this).removeClass('btn-light').addClass('btn-blue');
});

createLineChart('day');
createLineChart('month');
createLineChart('year');
</script>
{{-- <script type="text/javascript">
var colors = ['#5eb58a', '#f5c86d', '#f3686f', '#9a40d5', '#e3342f', '#f6993f', '#38c172', '#4dc0b5', '#3490dc', '#6574cd', '#9561e2', '#f66d9b'];
function getRandom(arr, n = 1) {
    var requests = n;
    var result = new Array(n),
        len = arr.length,
        taken = new Array(len);
    if (n > len)
        throw new RangeError("getRandom: more elements taken than available");
    while (n--) {
        var x = Math.floor(Math.random() * len);
        result[n] = arr[x in taken ? taken[x] : x];
        taken[x] = --len in taken ? taken[len] : len;
    }

    return requests == 1 ? result[0] : result;
}
function getElements(arr, n) {
    return arr.slice(0, n);
}
</script>
<script type="text/javascript">

let topicsRecords = JSON.parse($('#topicsChart').attr('data-records'));
console.log(topicsRecords);
let topics = [];
let topics_posts_count = [];
let topics_count = topicsRecords.length;

for (var i=0; i < topics_count; i++) {
  topics.push(topicsRecords[i].name);
  topics_posts_count.push(topicsRecords[i].posts_count);
}
var topicsChartElement = document.getElementById("topicsChart").getContext('2d');
var topicsChart = new Chart(topicsChartElement, {
    type: 'bar',
    data: {
        labels: topics,
        datasets: [{
            data: topics_posts_count,
            backgroundColor: getRandom(colors)
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
                    stepSize: 2
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        },
    }
});
</script> --}}
@endsection
