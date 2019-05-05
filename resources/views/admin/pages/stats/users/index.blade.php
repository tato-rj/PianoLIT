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
        <div class="col-12 p-3">
            <div class="border py-4 px-3">
                <div id="carouselRecords" class="carousel carousel-fade">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-center"><strong>Flow of users over time</strong></h4>
                        <div class="select-btn-group btn-group btn-group-sm">
                          <button data-target="#carouselRecords" data-slide-to="0" class="btn btn-blue">Daily</button>
                          <button data-target="#carouselRecords" data-slide-to="1"  class="btn btn-light">Monthly</button>
                          <button data-target="#carouselRecords" data-slide-to="2"  class="btn btn-light">Yearly</button>
                        </div>
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
    </div>

    <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Age groups',
        'subtitle' => 'Number of users per age group.',
        'id' => 'ageChart',
        'col' => '4',
        'data' => $usersAge])

      @include('admin.pages.stats.row', [
        'title' => 'Occupation',
        'subtitle' => 'Number of users by occupation.',
        'id' => 'occupationChart',
        'col' => '4',
        'data' => $usersOccupation])

      @include('admin.pages.stats.row', [
        'title' => 'Experience',
        'subtitle' => 'Number of users by experience.',
        'id' => 'experienceChart',
        'col' => '4',
        'data' => $usersExperience])
{{--       @include('admin.pages.stats.row', [
        'title' => 'Levels',
        'subtitle' => 'Number of pieces per level.',
        'id' => 'levelsChart',
        'col' => '4',
        'data' => $levelsStats])

      @include('admin.pages.stats.row', [
        'title' => 'Recordings count',
        'subtitle' => 'Pieces by number of recordings.',
        'id' => 'recChart',
        'col' => '4',
        'data' => $recStats]) --}}
    </div>

    <div class="row">
        @include('admin.pages.stats.users.ranking')
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#users-table').DataTable({
        aaSorting: [],
        columnDefs: [{
                    targets: [3],
                    orderable: false
                }]
    });
} );
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
let ageRecords = JSON.parse($('#ageChart').attr('data-records'));
let ages = [];
let age_users_count = [];

for (var i=0; i < ageRecords.length; i++) {
  ages.push(ageRecords[i].age);
  age_users_count.push(ageRecords[i].count);
}

var ageChartElement = document.getElementById("ageChart").getContext('2d');
var ageChart = new Chart(ageChartElement,{
    type: 'pie',
    data: {
        labels: ages,
        datasets: [{
            data: age_users_count,
            backgroundColor: getRandom(colors, 6)
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        }
    }
});

let occupationRecords = JSON.parse($('#occupationChart').attr('data-records'));
let occupations = [];
let occupation_users_count = [];

for (var i=0; i < occupationRecords.length; i++) {
  occupations.push(occupationRecords[i].occupation);
  occupation_users_count.push(occupationRecords[i].count);
}

var occupationChartElement = document.getElementById("occupationChart").getContext('2d');
var occupationChart = new Chart(occupationChartElement,{
    type: 'pie',
    data: {
        labels: occupations,
        datasets: [{
            data: occupation_users_count,
            backgroundColor: getRandom(colors, 6)
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        }
    }
});

let experienceRecords = JSON.parse($('#experienceChart').attr('data-records'));
let experiences = [];
let experience_users_count = [];

for (var i=0; i < experienceRecords.length; i++) {
  experiences.push(experienceRecords[i].experience);
  experience_users_count.push(experienceRecords[i].count);
}

var experienceChartElement = document.getElementById("experienceChart").getContext('2d');
var experienceChart = new Chart(experienceChartElement,{
    type: 'pie',
    data: {
        labels: experiences,
        datasets: [{
            data: experience_users_count,
            backgroundColor: getRandom(colors, 6)
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        }
    }
});
</script>
@endsection
