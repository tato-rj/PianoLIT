@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['title' => 'Activity analytics', 'subtitle' => 'A closer look at how often people explore PianoLIT.'])

    <div class="row"> 
      <div class="col-12 mb-4">
        @chart([
          'url' => route('admin.analytics.logs'),
          'chart' => 'bar',
          'type' => 'range',
          'title' => 'Logs by range',
          'subtitle' => 'Total number of logs per range',
          'height' => '35vh'
        ])
      </div>
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
var quickchart = new QuickChart;

$(document).ready(function() {
    quickchart.setup({
      element: '#analytics-range',
      url: "{{route('admin.analytics.logs', ['type' => 'range'])}}"
    }).make('bar');
});
</script>
@endsection
