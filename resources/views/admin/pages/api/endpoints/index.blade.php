@extends('admin.layouts.app')

@section('head')
<style type="text/css">
.mark, mark {
  padding: 0 !important;
}
</style>
@endsection

@section('content')

@php($colors = ['get' => 'blue', 'post' => 'green'])

<div class="content-wrapper">  
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'code', 'title' => 'Endpoints', 'subtitle' => 'Reference to all the endpoints used by the api.'])
    <div class="row mb-4">
      <div class="col-12">
        <input type="text" name="search-endpoint" placeholder="Search here" class="form-control ">
      </div>
    </div>
    <div class="row" id="endpoints" style="display: none;">
      @include('admin.pages.api.endpoints.groups.users')
      @include('admin.pages.api.endpoints.groups.auth')
      @include('admin.pages.api.endpoints.groups.favorites')
      @include('admin.pages.api.endpoints.groups.piece')
      @include('admin.pages.api.endpoints.groups.search')
      @include('admin.pages.api.endpoints.groups.tabs')
      @include('admin.pages.api.endpoints.groups.tutorials')
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.es6.min.js"></script>
<script type="text/javascript">
$('.endpoint-card').sort(function(a, b) {
  return a.textContent < b.textContent ? -1 : 1;
}).appendTo('#endpoints');
$('#endpoints').show();
</script>

<script type="text/javascript">
var marker = new Mark('div#endpoints');

$('input[name="search-endpoint"]').on('keyup', function() {
  let val = $(this).val().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  $('.endpoint-card').unmark();

  if (val.length > 2) {
    console.log('Find names with: '+val);
    $('.endpoint-card').each(function() {
      let $card = $(this);
      let text = this.textContent;

      if (text.includes(val)) {
        $card.show();
        $card.mark(val);
      } else {
        if ($card.has('mark').length == 0) {
          $card.hide();
        }
      }
    });
  } else {
    console.log('Show all');
    $('#endpoints .endpoint-card').show();
    $('.endpoint-card').unmark();
  }
});

</script>
<script type="text/javascript">
$('.endpoint-card form a').on('click', function(e) {
  e.preventDefault();
  $(this).closest('form').submit();
});
</script>
@endsection