@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'music', 'title' => 'Playlists', 'subtitle' => 'Manage the playlists of pieces.'])
    <div class="row mb-3">
      <div class="col-12">
        @include('admin.pages.playlists.create')
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        @include('components.tabs', [
          'name' => 'playlists',
          'headers' => ['General', 'Journey'],
          'views' => ['admin.pages.playlists.rows.general', 'admin.pages.playlists.rows.journey']
        ])
      </div>
    </div>
  </div>
</div>

@include('admin.pages.playlists.overview')
@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript">
$('div.playlist-container').each(function() {
  let $tab = $(this);
  $tab.sortable({
    handle: '.sort-handle',
    update: function(element) {
      let url = $tab.attr('data-url-reorder');
      let ids = $tab.find('.ordered').attrToArray('data-id');

      axios.patch(url, {ids: ids})
      .then(function(response) {
        $('.alert-container').remove();

        $('body').append(response.data);
        
        setTimeout(function() {
          $('.alert-temporary').fadeOut(function() {
            $(this).remove();
          });
        }, 2000);
      })
      .catch(function(error) {
        alert('Something went wrong...');
        console.log(error)
      })
    }
  });
});
</script>
@endsection