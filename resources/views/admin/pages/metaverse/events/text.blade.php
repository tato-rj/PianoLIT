@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'fire', 
      'title' => 'Metaverse', 
      'subtitle' => 'Manage all the events on the metaverse.',
      'action' => ['label' => 'Add a new event', 'modal' => 'add-modal']
    ])

    <div class="row">
      <div class="col-6"> 
        @textarea(['name' => 'text', 'bag' => 'default', 'placeholder' => '50 characters per line'])

        <div data-max="45" class="rounded bg-light p-4 split-text">
          
        </div>
      </div>
      <div class="col-6"> 
        @textarea(['name' => 'text', 'bag' => 'default', 'placeholder' => '66 characters per line'])

        <div data-max="60" class="rounded bg-light p-4 split-text">
          
        </div>
      </div>
    </div>
  </div>
</div>


@endsection

@section('scripts')

<script type="text/javascript">
function getWords(str) {
  return str.split(' ');
}


$('textarea[name="text"]').on('keyup', function() {
  let $result = $(this).parent().siblings('.split-text');

  let wordsArray = getWords($(this).val());
  let string = '';

  $result.html('');

  if (wordsArray.length) {
    for (var i=0; i<wordsArray.length; i++) {
      string += wordsArray[i] + ' ';

      if (string.length > $result.data('max')) {
        $result.append(`<p>`+string+`</p>`);
        string = '';        
      }
    }

    $result.append(`<p>`+string+`</p>`);
  }
});
</script>
@endsection