@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">

    <div class="row"> 
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.pieces'),
            'chart' => 'line',
            'type' => 'freepick',
            'title' => 'Freepick',
            'subtitle' => 'Views from the freepicks',
            'height' => '35vh',
            'xAxis' => 'hide',
            'select' => [
              'take' => [
                ['label' => 'Last 5', 'value' => 5],
                ['label' => 'Last 15', 'value' => '10'],
                ['label' => 'Show all', 'value' => \App\Piece::freepicks()->count()]
              ]
            ],
          ])
        </div>
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.pieces'),
            'chart' => 'bar',
            'type' => 'level',
            'title' => 'Level',
            'subtitle' => 'Pieces by level',
            'height' => '35vh'
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.pieces'),
            'chart' => 'pie',
            'type' => 'gender',
            'title' => 'Gender',
            'subtitle' => 'Pieces by composers gender',
            'height' => '35vh'
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.pieces'),
            'chart' => 'pie',
            'type' => 'period',
            'title' => 'Periods',
            'subtitle' => 'Pieces by period',
            'height' => '35vh'
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.pieces'),
            'chart' => 'pie',
            'type' => 'ethnicity',
            'title' => 'Ethnicity',
            'subtitle' => 'Pieces by composers ethnicity',
            'height' => '35vh'
          ])
        </div>
    </div>

    <div class="row">
      <div class="col-lg-6 col-md-6 col-12 mb-4">
          @ranking([
            'url' => route('admin.analytics.pieces'),
            'type' => 'favorites',
            'title' => 'Top favorited',
            'subtitle' => '10 most favorited pieces',
            'headers' => ['Count', 'Piece'],
            'collection' => $favorites,
            'row' => 'admin.pages.analytics.pieces.rows.favorites'
          ])
      </div>
      <div class="col-lg-6 col-md-6 col-12 mb-4">
          @ranking([
            'url' => route('admin.analytics.pieces'),
            'type' => 'views',
            'title' => 'Top viewed',
            'subtitle' => '10 most viewed pieces',
            'headers' => ['Count', 'Piece'],
            'collection' => $views,
            'row' => 'admin.pages.analytics.pieces.rows.views'
          ])
      </div>
    </div>
{{--     <div class="row"> 
      @include('admin.pages.analytics.row', [
        'title' => 'Periods',
        'subtitle' => 'Number of pieces in each period.',
        'id' => 'periodsChart',
        'col' => '4',
        'data' => $periodsAnalytics])

      @include('admin.pages.analytics.row', [
        'title' => 'Levels',
        'subtitle' => 'Number of pieces per level.',
        'id' => 'levelsChart',
        'col' => '4',
        'data' => $levelsAnalytics])

      @include('admin.pages.analytics.row', [
        'title' => 'Copyright',
        'subtitle' => 'Number of pieces by copyright.',
        'id' => 'copyrightChart',
        'col' => '4',
        'data' => $publicDomainCount])
    </div>

    <div class="row">
      @include('admin.pages.analytics.row', [
        'title' => 'Gender',
        'subtitle' => 'Male vs female composers.',
        'id' => 'genderChart',
        'col' => '4',
        'data' => $genderAnalytics])

      @include('admin.pages.analytics.row', [
        'title' => 'Videos',
        'subtitle' => 'Pieces by videos.',
        'id' => 'videosChart',
        'col' => '4',
        'data' => $videosCount])

      @include('admin.pages.analytics.row', [
        'title' => 'iTunes recordings',
        'subtitle' => 'Pieces by itunes recordings.',
        'id' => 'itunesChart',
        'col' => '4',
        'data' => $itunesCount])
    </div>

    <div class="row my-3">
        @include('admin.pages.analytics.pieces.ranking')
    </div> --}}

  </div>
</div>

{{-- @component('admin.components.modals.results', ['title' => 'We found the following pieces'])
@endcomponent --}}

@endsection

@section('scripts')
<script type="text/javascript">
var quickchart = new QuickChart;

$(document).ready(function() {
    quickchart.setup({
      element: '#analytics-freepick',
      url: "{{route('admin.analytics.pieces', ['type' => 'freepick', 'take' => 5])}}"
    }).make('line');

    quickchart.setup({
      element: '#analytics-gender',
      url: "{{route('admin.analytics.pieces', ['type' => 'gender'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#analytics-level',
      url: "{{route('admin.analytics.pieces', ['type' => 'level'])}}"
    }).make('bar');

    quickchart.setup({
      element: '#analytics-period',
      url: "{{route('admin.analytics.pieces', ['type' => 'period'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#analytics-ethnicity',
      url: "{{route('admin.analytics.pieces', ['type' => 'ethnicity'])}}"
    }).make('pie');
});
</script>
@endsection
