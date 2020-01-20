@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Emails lists',
    'description' => 'Manage the emails lists'])

    <div class="row">
      <div class="col-12 mb-4">
        <div class="mb-4">
        @include('admin.pages.subscriptions.lists.create')
        </div>
      </div>
    </div>

    <div class="row">
      @foreach($lists as $list)
      <div class="col-lg-3 col-md-4 col-sm-6 col-12">
        <div class="rounded border p-3">
          <h5 class="m-0"><strong>{{$list->name}}</strong></h5>
          <p ><small>{{$list->description}}</small></p>
          <p><i class="fas fa-users"></i> {{$list->subscribers_count}} {{str_plural('subscriber', $list->subscribers_count)}}</p>
          <div class="d-flex d-apart">
            <div class="d-flex">
              <a href="{{route('admin.subscriptions.lists.edit', $list)}}" class="btn btn-default btn-sm px-3 mr-2">Edit</a>
              <a href="#" 
                  data-preview-url="{{route('admin.subscriptions.lists.preview', $list)}}"
                  data-send-url="{{route('admin.subscriptions.lists.send', $list)}}"
                  data-send-to-url="{{route('admin.subscriptions.lists.send-to', $list)}}"
                 title="Send" data-toggle="modal" data-target="#emails-send-modal" class="btn btn-warning btn-sm px-3">Actions</a>
             </div>
            <div>
              <a href="#" data-url="{{route('admin.subscriptions.lists.destroy', $list)}}" title="Delete" data-toggle="modal" data-target="#delete-modal" class="delete text-danger">
                <i class="far fa-trash-alt align-middle"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.components.modals.emails-send')

@endsection

@section('scripts')
<script type="text/javascript">
$('#emails-send-modal').on('shown.bs.modal', function(e) {
  let preview = $(e.relatedTarget).attr('data-preview-url');
  let sendTo = $(e.relatedTarget).attr('data-send-to-url');
  let send = $(e.relatedTarget).attr('data-send-url');

  $(this).find('a#preview-url').attr('href', preview);
  $(this).find('form#send-to-url').attr('action', sendTo);
  $(this).find('form#send-url').attr('action', send);
});
</script>
@endsection