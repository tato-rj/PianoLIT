@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">

    <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Topics',
        'subtitle' => 'How the ' . $topicsCount . ' topics are distributed in the database.',
        'id' => 'topicsChart',
        'col' => '12',
        'data' => $topicStats])
    </div>
    <div class="row">
      <div class="col-12">
        <div class="border py-4 px-3">
          <div class="ml-2 mb-4">
            <h4 class="mb-1"><strong>Quiz results</strong></h4>
            <p class="text-muted">Number of quizzes completed per day</p>
          </div>
          <canvas id="results_graph" class="w-100" height="300" data-records="{{$results_graph}}"></canvas>
        </div>
      </div>
    </div>
    <div class="row my-3">
        @include('admin.pages.stats.quizzes.ranking')
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#quizzes-table').DataTable({
        aaSorting: [],
        columnDefs: [{
                    targets: [5],
                    orderable: false
                }]
    });
} );
</script>

<script type="text/javascript">
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
let topics_quizzes_count = [];
let topics_count = topicsRecords.length;

for (var i=0; i < topics_count; i++) {
  topics.push(topicsRecords[i].name);
  topics_quizzes_count.push(topicsRecords[i].quizzes_count);
}

console.log(getStepSize(topics_quizzes_count));
var topicsChartElement = document.getElementById("topicsChart").getContext('2d');
var topicsChart = new Chart(topicsChartElement, {
    type: 'bar',
    data: {
        labels: topics,
        datasets: [{
            data: topics_quizzes_count,
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
                    stepSize: getStepSize(topics_quizzes_count)
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
</script>
<script type="text/javascript">
let piecesChartElement = document.getElementById("results_graph").getContext('2d');
let piecesData = JSON.parse($('#results_graph').attr('data-records'));
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
        label: 'New results',
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
