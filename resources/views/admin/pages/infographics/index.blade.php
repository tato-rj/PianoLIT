@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'pencil-ruler', 'title' => 'Infographics', 'subtitle' => 'Manage the infographics available on the website.'])

    <div class="row">
      <div class="col-12 mb-4">
      @include('admin.pages.infographics.create')
      </div>
    </div>

    @datatable(['table' => 'infographs', 'columns' => ['Date', 'Name', 'Downloads', 'Score', 'Published', 'Gift', '']])
  
  </div>
</div>

@include('admin.components.modals.delete')

@include('admin.pages.infographics.preview')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
$('input#customFile').change(function() {
    var fr = new FileReader;
    fr.onload = function() {
        var img = new Image;
        img.onload = function() {
          $('input[name="width"]').val(img.width);
          $('input[name="height"]').val(img.height);
        };
        img.src = fr.result;
    };
    fr.readAsDataURL(this.files[0]);
});

$('#item-preview').on('show.bs.modal', function (e) {
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

(new DataTable('#infographs-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'name', name: 'infographs.name', class: 'dataTables_main_column'},
  {data: 'downloads', name: 'infographs.downloads'},
  {data: 'score', name: 'infographs.score'},
  {data: 'published', name: 'infographs.published_at'},
  {data: 'gift', name: 'infographs.giftable_at'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection