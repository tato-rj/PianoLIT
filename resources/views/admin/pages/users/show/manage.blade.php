<div class="row mb-4">
  <div class="col-lg-3 col-md-4 col-sm-8 col-12 p-3">
    <button data-name="{{$user->full_name}}" data-url="{{route('admin.users.destroy', $user->id)}}" data-bs-toggle="modal" data-bs-target="#delete-modal" class="bg-intermediate p-4 rounded btn-raw d-block w-100 text-start">
        <p class="mb-2">
          <strong>@icon('trash-2', ['mr' => 2])Delete account</strong>
        </p>
        <span><small>Keeps activity logs</small></span>
    </button>
  </div>

  <div class="col-lg-3 col-md-4 col-sm-8 col-12 p-3">
    <button data-name="{{$user->full_name}}" data-url="{{route('admin.users.purge', $user->id)}}" data-bs-toggle="modal" data-bs-target="#delete-modal" class="bg-advanced p-4 rounded btn-raw d-block w-100 text-start">
      <p class="mb-2">
        <strong>@icon('skull', ['mr' => 2])Purge account</strong>
      </p>
      <span><small>This will remove all activity logs</small></span>
    </button>
  </div>
  @if(app()->environment() == 'local')
  @include('admin.pages.users.show.sandbox')
  @endif
</div>