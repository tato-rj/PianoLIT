<div class="row mb-4">
  <div class="col-lg-4 col-md-4 col-sm-8 col-12 p-3">
    <a href="mailto:{{$user->email}}" class="link-none">
      <div class="bg-elementary p-4 rounded">
        <p class="mb-2">
          <strong><i class="fas fa-envelope mr-2"></i>Contact User</strong>
        </p>
        <span><small>Send an email to {{$user->email}}</small></span>
      </div>
    </a>
  </div>

  <div class="col-lg-4 col-md-4 col-sm-8 col-12 p-3">
    <a href="" data-name="{{$user->full_name}}" data-url="{{route('admin.users.destroy', $user->id)}}" data-toggle="modal" data-target="#delete-modal" class="link-none">
      <div class="bg-intermediate p-4 rounded">
        <p class="mb-2">
          <strong><i class="fas fa-trash-alt mr-2"></i>Delete account</strong>
        </p>
        <span><small>Keeps activity logs</small></span>
      </div>
    </a>
  </div>

  <div class="col-lg-4 col-md-4 col-sm-8 col-12 p-3">
    <a href="" data-name="{{$user->full_name}}" data-url="{{route('admin.users.purge', $user->id)}}" data-toggle="modal" data-target="#delete-modal" class="link-none">
      <div class="bg-advanced p-4 rounded">
        <p class="mb-2">
          <strong><i class="fas fa-skull mr-2"></i>Purge account</strong>
        </p>
        <span><small>This will remove all activity logs</small></span>
      </div>
    </a>
  </div>
</div>