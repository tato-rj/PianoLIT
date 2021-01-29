@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row"> 
      <div class="col-12 mb-4">
        @chart([
          'url' => route('admin.stats.logs'),
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
      element: '#stats-range', 
      url: "{{route('admin.stats.logs', ['type' => 'range'])}}"
    }).make('bar');
});
</script>
@endsection
