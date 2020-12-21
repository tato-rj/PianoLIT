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
      <div class="row">
          <div class="col-12 position-relative" style="overflow-x: auto;">
              <div id="regions-div" data-url="{{route('admin.stats.load-map')}}" style="width: 100%; height: 500px; min-width: 600px"></div>
              <button id="reset-map" class="absolute-top-left btn btn-primary btn-sm" style="display: none;">Reset map</button>
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
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    var mapArray;
    // SETUP
    google.charts.load('current', {
        'packages':['geochart'],
        // Note: you will need to get a mapsApiKey for your project.
        // See: https://developers.google.com/chart/interactive/docs/basic_load_libs#load-settings
        'mapsApiKey': "{{env('GOOGLE_API_KEY')}}"
    });

    function loadMap(country = null) {
      let url = $('#regions-div').data('url');

      console.log(country ? 'Requesting a map for ' + country : 'Requesting global map');

      axios.get(url, {params: {country: country}})
       .then(function(response) {
          let data = response.data;
          mapArray = data.array;
          
          google.charts.setOnLoadCallback(function() {
              return drawRegionsMap(data.array, data.region);
          });

          if (country) {
            $('#reset-map').enable().show();
          } else {
            $('#reset-map').hide();
          }
          
          console.log('Data received');
          console.log(mapArray);
       }).catch(function(error) {
          alert('Something went wrong: '+error);
       });
    }

    function drawRegionsMap(array, region = null) {

        var data = google.visualization.arrayToDataTable(array);

        var options = {
            region: region,
            resolution: region ? 'provinces' : null,
            colorAxis: {colors: ['#D7E9E9', '#0055fe']}
        };

        var chart = new google.visualization.GeoChart(document.getElementById('regions-div'));

        chart.draw(data, options);

        google.visualization.events.addListener(chart, 'select', function() {
            var selection = chart.getSelection().length ? chart.getSelection()[0]['row'] + 1 : null;

            if (selection && ! region) {
              let country = mapArray[selection][0];
              loadMap(country);
            }
        });
    }
</script>
<script type="text/javascript">
    $(document).ready(function() {
      loadMap();
    });

    $('#reset-map').on('click', function() {
      $(this).disable();
      loadMap();
    })
</script>
@endsection