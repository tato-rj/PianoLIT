@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
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

    <div class="row my-3">
        @include('admin.pages.stats.blog.ranking')
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#posts-table').DataTable({
        aaSorting: [],
        columnDefs: [{
                    targets: [3],
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
                    stepSize: getStepSize(topics_posts_count)
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
@endsection
