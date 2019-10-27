@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Infographs',
    'description' => 'Manage the infographs'])

    <div class="row">
      <div class="col-12">
      @include('admin.pages.infographs.create')
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="infographs-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Name</th>
              <th class="border-0" scope="col">Type</th>
              <th class="border-0" scope="col">Downloads</th>
              <th class="border-0" scope="col">Score</th>
              <th class="border-0" scope="col">Published</th>
              <th class="border-0" scope="col">Gift</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($infographs as $infograph)
            @include('admin.pages.infographs.row')
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'infograph'])
@include('admin.pages.infographs.preview')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
$('#infograph-preview').on('show.bs.modal', function (e) {
  let image = $(e.relatedTarget).attr('data-image');
  let thumbnail = $(e.relatedTarget).attr('data-thumbnail');
  let $previewImage = $(e.target).find('img.image');
  let $previewThumbnail = $(e.target).find('img.thumbnail');

  $previewImage.attr('src', image);
  $previewThumbnail.attr('src', thumbnail);

  $previewImage.on('load', function() {
    $(e.target).find('.legend-image h1').text($(this).prop("naturalWidth") + ' x ' + $(this).prop("naturalHeight"));
  });

  $previewThumbnail.on('load', function() {
    $(e.target).find('.legend-thumbnail h1').text($(this).prop("naturalWidth") + ' x ' + $(this).prop("naturalHeight"));
  });
});

$('.delete').on('click', function (e) {
  $infograph = $(this);
  name = $infograph.attr('data-name');
  url = $infograph.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
})

$(document).ready(function(){
  $('#infographs-table').DataTable({
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [4,5] } ],
  });
});

$('input.status-toggle').on('change', function() {
  let $input = $(this);
  let $label = $($input.attr('data-target'));

  $label.addClass('text-muted').removeClass('text-warning text-success');
  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      if ($input.is(':checked')) {
        $label.text('Published').toggleClass('text-muted text-success');
      } else {
        $label.text('Unpublished').toggleClass('text-muted text-warning');
      }
    }
  });
});
</script>
@endsection