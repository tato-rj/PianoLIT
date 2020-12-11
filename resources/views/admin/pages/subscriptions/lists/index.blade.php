@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'users', 'title' => 'Email Lists', 'subtitle' => 'Send out mass emails with the email lists.'])

    <div class="row">
      <div class="col-12 mb-4">
        <div class="mb-4">
        @include('admin.pages.subscriptions.lists.create')
        </div>
      </div>
    </div>

    <div class="row">
      @foreach($lists as $list)
        @include('admin.pages.subscriptions.lists.card')
      @endforeach
    </div>
  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript">
$(document).on('keyup', 'input[name="subject-input"]', function() {
  $('input[name="subject"]').val($(this).val());
});
</script>
@endsection