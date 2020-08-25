@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">

    <div class="row"> 
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.stats.pieces'),
            'chart' => 'line',
            'type' => 'freepick',
            'title' => 'Freepick',
            'subtitle' => 'Views from the freepicks',
            'height' => '35vh',
            'xAxis' => 'hide',
            'select' => [
              'take' => [
                ['label' => 'Last 10', 'value' => 10],
                ['label' => 'Last 20', 'value' => '20'],
                ['label' => 'Show all', 'value' => \App\Piece::freepicks()->count()]
              ]
            ],
          ])
        </div>
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.stats.pieces'),
            'chart' => 'bar',
            'type' => 'level',
            'title' => 'Level',
            'subtitle' => 'Pieces by level',
            'height' => '35vh'
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.stats.pieces'),
            'chart' => 'pie',
            'type' => 'gender',
            'title' => 'Gender',
            'subtitle' => 'Pieces by composers gender',
            'height' => '35vh'
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.stats.pieces'),
            'chart' => 'pie',
            'type' => 'period',
            'title' => 'Periods',
            'subtitle' => 'Pieces by period',
            'height' => '35vh'
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.stats.pieces'),
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
            'url' => route('admin.stats.pieces'),
            'type' => 'favorites',
            'title' => 'Top favorited',
            'subtitle' => '10 most favorited pieces',
            'headers' => ['Count', 'Piece'],
            'collection' => $favorites,
            'row' => 'admin.pages.stats.pieces.rows.favorites'
          ])
      </div>
      <div class="col-lg-6 col-md-6 col-12 mb-4">
          @ranking([
            'url' => route('admin.stats.pieces'),
            'type' => 'views',
            'title' => 'Top viewed',
            'subtitle' => '10 most viewed pieces',
            'headers' => ['Count', 'Piece'],
            'collection' => $views,
            'row' => 'admin.pages.stats.pieces.rows.views'
          ])
      </div>
    </div>
{{--     <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Periods',
        'subtitle' => 'Number of pieces in each period.',
        'id' => 'periodsChart',
        'col' => '4',
        'data' => $periodsStats])

      @include('admin.pages.stats.row', [
        'title' => 'Levels',
        'subtitle' => 'Number of pieces per level.',
        'id' => 'levelsChart',
        'col' => '4',
        'data' => $levelsStats])

      @include('admin.pages.stats.row', [
        'title' => 'Copyright',
        'subtitle' => 'Number of pieces by copyright.',
        'id' => 'copyrightChart',
        'col' => '4',
        'data' => $publicDomainCount])
    </div>

    <div class="row">
      @include('admin.pages.stats.row', [
        'title' => 'Gender',
        'subtitle' => 'Male vs female composers.',
        'id' => 'genderChart',
        'col' => '4',
        'data' => $genderStats])

      @include('admin.pages.stats.row', [
        'title' => 'Videos',
        'subtitle' => 'Pieces by videos.',
        'id' => 'videosChart',
        'col' => '4',
        'data' => $videosCount])

      @include('admin.pages.stats.row', [
        'title' => 'iTunes recordings',
        'subtitle' => 'Pieces by itunes recordings.',
        'id' => 'itunesChart',
        'col' => '4',
        'data' => $itunesCount])
    </div>

    <div class="row my-3">
        @include('admin.pages.stats.pieces.ranking')
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
      element: '#stats-freepick', 
      url: "{{route('admin.stats.pieces', ['type' => 'freepick', 'take' => 5])}}"
    }).make('line');

    quickchart.setup({
      element: '#stats-gender', 
      url: "{{route('admin.stats.pieces', ['type' => 'gender'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#stats-level', 
      url: "{{route('admin.stats.pieces', ['type' => 'level'])}}"
    }).make('bar');

    quickchart.setup({
      element: '#stats-period', 
      url: "{{route('admin.stats.pieces', ['type' => 'period'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#stats-ethnicity', 
      url: "{{route('admin.stats.pieces', ['type' => 'ethnicity'])}}"
    }).make('pie');
});
</script>
@endsection
