@extends('layouts.app')

@push('header')
<style type="text/css">
.list-group-item.active {
	color: #495057 !important;
	background-color: #f3f5f7;
}
</style>
@endpush

@section('content')
<div class="container mb-6">
	<div class="row">
	  <div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-4">
		@include('users.profile.menu')
	  </div>
	  <div class="col-lg-8 col-md-8 col-sm-6 col-12">
		<div class="tab-content" id="nav-tabContent">
			@include('users.profile.tabs.profile')
			@include('users.profile.tabs.preferences')
			@include('users.profile.tabs.delete')
		</div>
	  </div>
	</div>
</div>

<div class="modal fade" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-0">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        You are about to delete your account!
        <p class="text-danger"><small>This action cannot be undone</small></p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="{{route('users.destroy', auth()->user()->id)}}">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, I am sure</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$('input.status-toggle').on('change', function() {
  let $input = $(this);

  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      alert('Your update was successful!');
    },
    error: function(xhr,status,error) {
    	alert('Something went wrong: ' + error);
    }
  });
});
</script>
<script type="text/javascript">
$(function(){
  var hash = window.location.hash;
  hash && $('.list-group .list-group-item[href="' + hash + '"]').tab('show');

  $('.list-group-item').click(function (e) {
    window.location.hash = this.hash;
  });
});
</script>
@endpush