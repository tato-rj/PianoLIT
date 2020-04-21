@extends('layouts.app')

@push('header')
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
$('#request-password-change').click(function() {
  $(this).hide();
  $('#change-password').show();
});
</script>
@endpush